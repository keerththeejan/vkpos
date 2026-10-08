<?php

namespace App\Services;

use App\Events\ProductsCreatedOrModified;
use App\LabelPrintProfile;
use App\Product;
use App\SellingPriceGroup;
use App\Utils\ProductUtil;
use App\Variation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ZebraLabelService
{
    protected $productUtil;

    protected $geometry;

    public function __construct(ProductUtil $productUtil, ZebraLabelGeometry $geometry)
    {
        $this->productUtil = $productUtil;
        $this->geometry = $geometry;
    }

    public function productPayload(int $businessId, int $variationId, ?int $priceGroupId = null): array
    {
        if ($variationId < 1) {
            throw new InvalidArgumentException('Select a product before printing.');
        }

        try {
            $details = $this->productUtil->getDetailsFromVariation($variationId, $businessId, null, false);
        } catch (ModelNotFoundException $e) {
            throw new InvalidArgumentException('The selected product could not be found.');
        }

        $amount = (float) $details->sell_price_inc_tax;

        if (! empty($priceGroupId)) {
            $ownsGroup = SellingPriceGroup::where('business_id', $businessId)
                ->where('id', $priceGroupId)
                ->exists();

            if (! $ownsGroup) {
                throw new InvalidArgumentException('The selected price group is not available.');
            }

            $group = $this->productUtil->getVariationGroupPrice($variationId, $priceGroupId, $details->tax_id);
            if (isset($group['price_inc_tax']) && $group['price_inc_tax'] !== '' && $group['price_inc_tax'] !== null) {
                $amount = (float) $group['price_inc_tax'];
            }
        }

        $barcode = trim((string) $details->sub_sku);
        $name = trim((string) ($details->product_name ?? ''));
        if ($name === '') {
            $name = trim((string) ($details->product_actual_name ?? ''));
        }
        $formatted = $this->productUtil->num_f($amount, true);
        $formatted = $this->formatLabelPrice($formatted);

        return [
            'product_id' => (int) $details->product_id,
            'variation_id' => (int) $details->variation_id,
            'name' => $name,
            'sku' => $barcode,
            'barcode' => $barcode,
            'price' => $formatted,
            'barcode_missing' => $barcode === '',
        ];
    }

    public function updateBarcode(int $businessId, int $variationId, string $raw): array
    {
        if ($variationId < 1) {
            throw new InvalidArgumentException('Select a product before printing.');
        }

        $sku = trim($raw);
        if ($sku === '') {
            throw new InvalidArgumentException('Barcode / SKU is required.');
        }
        if (mb_strlen($sku) > 255) {
            throw new InvalidArgumentException('Barcode / SKU must be 255 characters or fewer.');
        }
        if (preg_match('/[\x00-\x1F\x7F^~\\\\]/u', $sku)) {
            throw new InvalidArgumentException('Barcode / SKU contains a character that cannot be printed.');
        }

        $productModel = DB::transaction(function () use ($businessId, $variationId, $sku) {
            $variation = Variation::where('id', $variationId)->lockForUpdate()->first();
            if (! $variation) {
                throw new InvalidArgumentException('The selected product could not be found.');
            }

            $product = Product::where('id', $variation->product_id)
                ->where('business_id', $businessId)
                ->lockForUpdate()
                ->first();
            if (! $product) {
                throw new InvalidArgumentException('The selected product could not be found.');
            }

            $duplicateProduct = Product::where('business_id', $businessId)
                ->where('sku', $sku)
                ->where('id', '!=', $product->id)
                ->exists();

            $duplicateVariation = Variation::where('sub_sku', $sku)
                ->where('id', '!=', $variation->id)
                ->whereHas('product', function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->exists();

            if ($duplicateProduct || $duplicateVariation) {
                throw new InvalidArgumentException('Barcode / SKU already exists for another product.');
            }

            $changed = (string) $variation->sub_sku !== $sku;
            $variation->sub_sku = $sku;
            $variation->save();

            if (in_array($product->type, ['single', 'combo'], true) && (string) $product->sku !== $sku) {
                $product->sku = $sku;
                $changed = true;
            }
            if ($changed) {
                $product->save();
            }

            return $changed ? $product : null;
        });

        if ($productModel) {
            try {
                event(new ProductsCreatedOrModified($productModel, 'updated'));
            } catch (\Throwable $e) {
                \Log::warning('Barcode update notification failed: '.$e->getMessage());
            }
        }

        return $this->productPayload($businessId, $variationId);
    }

    public function rowsFromRequest($value): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null || ! is_numeric($value) || ((int) $value != (float) $value)) {
            throw new InvalidArgumentException('Please enter a valid print quantity.');
        }

        $rows = (int) $value;
        if ($rows < 1 || $rows > 100) {
            throw new InvalidArgumentException('Please enter a valid print quantity.');
        }

        return $rows;
    }

    public function profileName($value): string
    {
        $name = trim(strip_tags((string) $value));
        if ($name === '' || mb_strlen($name) > 80) {
            throw new InvalidArgumentException('Enter a profile name up to 80 characters.');
        }

        return $name;
    }

    public function layoutFromRequest($input): array
    {
        if (! is_array($input)) {
            throw new InvalidArgumentException('The label layout contains a value that cannot be printed. Check the alignment numbers and try again.');
        }

        $defaults = LabelPrintProfile::defaultAttributes();
        $skip = ['name', 'is_default', 'columns'];
        $layout = [];

        foreach ($defaults as $key => $default) {
            if (in_array($key, $skip, true)) {
                continue;
            }
            $layout[$key] = array_key_exists($key, $input) ? $input[$key] : $default;
        }

        $layout['columns'] = 3;
        foreach (['label_gap_mm', 'top_offset_mm', 'left_offset_mm'] as $mmKey) {
            if (! array_key_exists($mmKey, $layout) || $layout[$mmKey] === '' || $layout[$mmKey] === null) {
                $layout[$mmKey] = $defaults[$mmKey];
            }
        }
        $layout['vertical_text'] = trim(strip_tags((string) ($layout['vertical_text'] ?? '')));
        if (mb_strlen($layout['vertical_text']) > 80) {
            throw new InvalidArgumentException('Vertical text must be 80 characters or fewer.');
        }

        $this->assertLayout($layout);

        return $layout;
    }

    /**
     * One label format, repeated with ^PQ. Column X is label index × (width + gap).
     * ^PQ keeps row 1 and row 50 on the same coordinates, so the job cannot drift.
     */
    public function buildJob(array $layout, string $sku, string $price, string $vertical, int $rows, string $productName = ''): array
    {
        $sku = $this->zplSafe($sku, 120);
        $price = $this->zplSafe($this->formatLabelPrice($price), 40);
        $vertical = $this->zplSafe($vertical);
        $media = $this->geometry->media($layout);
        $placed = $this->placement($layout, $media, $sku, $productName);
        $warnings = $placed['warnings'];

        if ($sku === '') {
            throw new InvalidArgumentException('Barcode is missing for this product.');
        }

        $zpl = $this->formatHeader($media, $rows, 'product');
        foreach ($media['origins'] as $index => $origin) {
            $zpl .= $this->productFields($origin, $index, $media, $placed, $sku, $price, $vertical, $layout);
        }
        $zpl .= "^PQ{$rows},0,1,Y\n^XZ\n";

        return [
            'zpl' => $zpl,
            'warnings' => $warnings,
            'media' => $this->mediaSummary($media),
        ];
    }

    public function buildZpl(array $layout, string $sku, string $price, string $vertical, int $rows, string $productName = ''): string
    {
        return $this->buildJob($layout, $sku, $price, $vertical, $rows, $productName)['zpl'];
    }

    public function buildTestJob(array $layout, int $rows): array
    {
        $media = $this->geometry->media($layout);
        $barcodeValue = '123456789012';
        $fitted = $this->geometry->fitBarcode($barcodeValue, (float) $media['barcode_module'], (int) $media['label_width']);
        $labelW = (int) $media['label_width'];
        $labelH = (int) $media['label_height'];
        $boxW = max(1, $labelW - 1);
        $boxH = max(1, $labelH - 1);
        $barcodeY = 46;
        $barcodeH = min(40, $labelH - $barcodeY - 18);
        if ($barcodeH < 24) {
            $barcodeY = 36;
            $barcodeH = min(40, $labelH - $barcodeY - 16);
        }

        $zpl = $this->formatHeader($media, $rows, 'test');
        foreach ($media['origins'] as $index => $origin) {
            $n = $index + 1;
            $textW = $labelW - 8;
            $barcodeX = $origin + $fitted['x'];
            $zpl .= "^FX LABEL {$n} origin {$origin}\n";
            $zpl .= "^FO{$origin},0^GB{$boxW},{$boxH},1^FS\n";
            $zpl .= "^FO".($origin + 4).",2^A0N,22,16^FB{$textW},1,0,C,0^FD{$n}^FS\n";
            $zpl .= "^FO".($origin + 4).",26^A0N,14,10^FB{$textW},1,0,C,0^FDCENTER^FS\n";
            $zpl .= "^FO{$barcodeX},{$barcodeY}^BY{$fitted['module']}^BCN,{$barcodeH},N,N,N^FD{$barcodeValue}^FS\n";
            $zpl .= "^FO".($origin + 4).",".($barcodeY + $barcodeH + 1)."^A0N,12,8^FB{$textW},1,0,C,0^FD{$barcodeValue}^FS\n";
        }
        $zpl .= "^PQ{$rows},0,1,Y\n^XZ\n";

        return [
            'zpl' => $zpl,
            'warnings' => [],
            'media' => $this->mediaSummary($media),
        ];
    }

    /**
     * One Rs. prefix. 55.00 and an existing Rs. 55.00 both become Rs. 55.00.
     */
    public function formatLabelPrice(string $amount): string
    {
        $amount = str_replace(['₹', '₨'], ' ', $amount);
        $amount = trim(preg_replace('/\s+/', ' ', $amount) ?? '');
        $amount = preg_replace('/^(?:(?:rs\.?|lkr)\s*)+/iu', '', $amount) ?? $amount;
        $amount = trim($amount);
        if ($amount === '') {
            $amount = '0.00';
        }

        return 'Rs. '.$amount;
    }

    private function formatHeader(array $media, int $rows, string $kind): string
    {
        $pw = (int) $media['print_width'];
        $ll = (int) $media['label_length'];
        $ls = -1 * (int) $media['left_offset_dots'];
        $lt = (int) $media['top_offset_dots'];
        $gap = $this->formatMm((float) $media['label_gap_mm']);
        $origins = implode(',', $media['origins']);

        return "~SD25\n"
            ."^XA\n"
            ."^FX VKPOS {$kind} {$media['label_width_mm']}x{$media['label_height_mm']}mm 3-up {$media['dpi']}dpi gap {$gap}mm\n"
            ."^FX print width {$pw} label length {$ll} columns {$origins} rows {$rows}\n"
            ."^CI28\n"
            ."^MMT\n"
            ."^MNW\n"
            ."^PW{$pw}\n"
            ."^LL{$ll}\n"
            ."^LH0,0\n"
            ."^LS{$ls}\n"
            ."^LT{$lt}\n"
            ."^PR3,3,3\n";
    }

    private function placement(array $layout, array $media, string $sku, string $productName): array
    {
        $legacy = $this->geometry->isLegacyLayout($layout, $media);
        $stack = $media['stack'];
        $labelW = (int) $media['label_width'];
        $labelH = (int) $media['label_height'];
        $warnings = [];

        $barcodeHeight = $legacy
            ? (int) $stack['barcode_height']
            : (int) ($layout['barcode_height'] ?? $stack['barcode_height']);
        $resolved = $this->geometry->stack($labelH, $barcodeHeight);
        $useStack = $legacy;
        $barcodeY = $useStack ? (int) $resolved['barcode_y'] : (int) ($layout['barcode_y'] ?? $resolved['barcode_y']);
        $skuY = $useStack ? (int) $resolved['sku_y'] : (int) ($layout['sku_y'] ?? $resolved['sku_y']);
        $priceY = $useStack ? (int) $resolved['price_y'] : (int) ($layout['price_y'] ?? $resolved['price_y']);
        $nameY = $useStack ? (int) $resolved['product_name_y'] : (int) ($layout['product_name_y'] ?? $resolved['product_name_y']);
        $barcodeHeight = (int) $resolved['barcode_height'];

        $barcodeY = $this->clamp($barcodeY, 0, max(0, $labelH - $barcodeHeight));
        $skuY = $this->clamp($skuY, 0, max(0, $labelH - 10));
        $priceY = $this->clamp($priceY, 0, max(0, $labelH - 10));
        $nameY = $this->clamp($nameY, 0, max(0, $labelH - 10));

        $moduleRequest = (float) ($layout['barcode_width'] ?? $media['barcode_module']);
        $fitted = $this->geometry->fitBarcode($sku, $moduleRequest, $labelW);
        $nudge = $legacy ? 0 : (int) ($layout['barcode_x'] ?? 0);
        $barcodeX = $this->clamp($fitted['x'] + $nudge, 0, max(0, $labelW - 1));
        if ($fitted['reduced']) {
            $warnings[] = 'Barcode module width was reduced so the bars fit the 30 mm label without stretching.';
        }
        if (! $fitted['quiet_ok']) {
            $warnings[] = 'This barcode is long for a 30 mm label. The quiet zone is tighter than 10 modules.';
        }

        $skuFont = $this->geometry->skuFont($sku, $labelW);
        if (! $skuFont['fits']) {
            $warnings[] = 'The barcode number is very long. It is printed in full at the smallest readable font.';
        }

        $priceSize = $useStack
            ? (int) $resolved['price_font_size']
            : min(22, (int) ($layout['price_font_size'] ?? $resolved['price_font_size']));
        $priceWidth = (int) $resolved['price_font_width'];
        $nameSize = $useStack
            ? (int) $resolved['product_name_font_size']
            : min(18, (int) ($layout['product_name_font_size'] ?? $resolved['product_name_font_size']));
        $nameWidth = $useStack
            ? (int) $resolved['product_name_font_width']
            : min(14, max(8, (int) ($layout['product_name_font_width'] ?? $resolved['product_name_font_width'])));
        $nameMaxWidth = $labelW - 8;
        $nameLines = 1;
        if (! $useStack && ! empty($layout['product_name_wrap'])) {
            $nameLines = min(2, max(1, (int) ($layout['product_name_max_lines'] ?? 1)));
            if ($nameY + ($nameSize * $nameLines) > $labelH - 1) {
                $nameLines = 1;
            }
        }

        $layoutForName = $layout;
        $layoutForName['show_product_name'] = $layout['show_product_name'] ?? true;
        $layoutForName['product_name_wrap'] = $nameLines > 1;
        $layoutForName['product_name_max_width'] = $nameMaxWidth;
        $layoutForName['product_name_font_width'] = $nameWidth;
        $name = $this->productNameForZpl($productName, $layoutForName);

        return [
            'warnings' => $warnings,
            'barcode_x' => $barcodeX,
            'barcode_y' => $barcodeY,
            'barcode_height' => $barcodeHeight,
            'module' => (int) $fitted['module'],
            'sku_y' => $skuY,
            'sku_font_height' => (int) $skuFont['height'],
            'sku_font_width' => (int) $skuFont['width'],
            'price_y' => $priceY,
            'price_size' => $priceSize,
            'price_width' => $priceWidth,
            'name' => $name,
            'name_y' => $nameY,
            'name_size' => $nameSize,
            'name_width' => $nameWidth,
            'name_max_width' => $nameMaxWidth,
            'name_lines' => $nameLines,
            'text_width' => $labelW - 8,
        ];
    }

    private function productFields(int $origin, int $index, array $media, array $placed, string $sku, string $price, string $vertical, array $layout): string
    {
        $n = $index + 1;
        $textX = $origin + 4;
        $textW = (int) $placed['text_width'];
        $barcodeX = $origin + (int) $placed['barcode_x'];
        $zpl = "^FX LABEL {$n} origin {$origin}\n";
        $zpl .= "^FO{$barcodeX},{$placed['barcode_y']}^BY{$placed['module']}^BCN,{$placed['barcode_height']},N,N,N^FD{$sku}^FS\n";
        $zpl .= "^FO{$textX},{$placed['sku_y']}^A0N,{$placed['sku_font_height']},{$placed['sku_font_width']}^FB{$textW},1,0,C,0^FD{$sku}^FS\n";
        $zpl .= "^FO{$textX},{$placed['price_y']}^A0N,{$placed['price_size']},{$placed['price_width']}^FB{$textW},1,0,C,0^FD{$price}^FS\n";
        if ($placed['name'] !== '') {
            $zpl .= "^FO{$textX},{$placed['name_y']}^A0N,{$placed['name_size']},{$placed['name_width']}^FB{$textW},{$placed['name_lines']},0,C,0^FD{$placed['name']}^FS\n";
        }
        if ($vertical !== '') {
            $size = min(14, (int) ($layout['vertical_font_size'] ?? 12));
            $x = $origin + (int) $media['label_width'] - $size - 1;
            $y = 2;
            $zpl .= "^FO{$x},{$y}^A0B,{$size},{$size}^FD{$vertical}^FS\n";
        }

        return $zpl;
    }

    private function mediaSummary(array $media): array
    {
        return [
            'dpi' => $media['dpi'],
            'label_width_mm' => $media['label_width_mm'],
            'label_height_mm' => $media['label_height_mm'],
            'label_gap_mm' => $media['label_gap_mm'],
            'top_offset_mm' => $media['top_offset_mm'],
            'left_offset_mm' => $media['left_offset_mm'],
            'print_width' => $media['print_width'],
            'label_length' => $media['label_length'],
            'origins' => $media['origins'],
            'label_width' => $media['label_width'],
            'label_height' => $media['label_height'],
            'gap_dots' => $media['gap_dots'],
        ];
    }

    private function formatMm(float $mm): string
    {
        $formatted = number_format($mm, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function clamp(int $value, int $min, int $max): int
    {
        if ($max < $min) {
            return $min;
        }

        return max($min, min($max, $value));
    }

    private function assertLayout(array &$layout): void
    {
        $ints = [
            'width' => [200, 2000],
            'height' => [40, 800],
            'col1_x' => [-2000, 4000],
            'col2_x' => [-2000, 4000],
            'col3_x' => [-2000, 4000],
            'barcode_x' => [-2000, 4000],
            'barcode_y' => [-2000, 4000],
            'barcode_height' => [10, 400],
            'sku_x' => [-2000, 4000],
            'sku_y' => [-2000, 4000],
            'sku_font_size' => [8, 80],
            'price_x' => [-2000, 4000],
            'price_y' => [-2000, 4000],
            'price_font_size' => [8, 80],
            'vertical_x' => [-2000, 4000],
            'vertical_y' => [-2000, 4000],
            'vertical_font_size' => [8, 80],
            'product_name_x' => [-2000, 4000],
            'product_name_y' => [-2000, 4000],
            'product_name_font_size' => [8, 80],
            'product_name_font_width' => [8, 80],
            'product_name_max_width' => [20, 800],
            'product_name_max_lines' => [1, 6],
        ];

        foreach ($ints as $key => $range) {
            $layout[$key] = $this->intField($layout[$key] ?? null, $range[0], $range[1]);
        }

        $layout['barcode_width'] = $this->floatField($layout['barcode_width'] ?? null, 1, 10);
        $layout['printer_dpi'] = $this->intField($layout['printer_dpi'] ?? null, 203, 300);
        if (! in_array($layout['printer_dpi'], [203, 300], true)) {
            throw new InvalidArgumentException('Choose a printer resolution of 203 or 300 DPI.');
        }

        $weight = strtolower(trim((string) ($layout['sku_font_weight'] ?? 'bold')));
        if (! in_array($weight, ['bold', 'normal'], true)) {
            throw new InvalidArgumentException('Choose a SKU font weight of bold or normal.');
        }
        $layout['sku_font_weight'] = $weight;

        $nameWeight = strtolower(trim((string) ($layout['product_name_font_weight'] ?? 'bold')));
        if (! in_array($nameWeight, ['bold', 'normal'], true)) {
            throw new InvalidArgumentException('Choose a product name font weight of bold or normal.');
        }
        $layout['product_name_font_weight'] = $nameWeight;

        $align = strtolower(trim((string) ($layout['product_name_align'] ?? 'center')));
        if (! in_array($align, ['left', 'center', 'right'], true)) {
            throw new InvalidArgumentException('Choose a product name alignment of left, center, or right.');
        }
        $layout['product_name_align'] = $align;
        $layout['show_product_name'] = $this->boolField($layout['show_product_name'] ?? true);
        $layout['product_name_wrap'] = $this->boolField($layout['product_name_wrap'] ?? false);

        $printer = trim((string) ($layout['printer_name'] ?? ''));
        if ($printer === '' || mb_strlen($printer) > 120 || preg_match('/[\r\n\^~]/', $printer)) {
            throw new InvalidArgumentException('Enter the printer name exactly as it appears in the system.');
        }
        $layout['printer_name'] = $printer;
        $layout['columns'] = 3;

        $media = $this->geometry->media($layout);
        $layout['label_gap_mm'] = $media['label_gap_mm'];
        $layout['top_offset_mm'] = $media['top_offset_mm'];
        $layout['left_offset_mm'] = $media['left_offset_mm'];
        $layout['width'] = $media['print_width'];
        $layout['height'] = $media['label_length'];
        $layout['col1_x'] = $media['origins'][0];
        $layout['col2_x'] = $media['origins'][1];
        $layout['col3_x'] = $media['origins'][2];
        $layout['columns'] = $media['columns'];
    }

    private function intField($value, int $min, int $max): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null || ! is_numeric($value)) {
            throw new InvalidArgumentException('The label layout contains a value that cannot be printed. Check the alignment numbers and try again.');
        }

        $number = (int) round((float) $value);
        if ($number < $min || $number > $max) {
            throw new InvalidArgumentException('The label layout contains a value that cannot be printed. Check the alignment numbers and try again.');
        }

        return $number;
    }

    private function floatField($value, float $min, float $max): float
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null || ! is_numeric($value)) {
            throw new InvalidArgumentException('The label layout contains a value that cannot be printed. Check the alignment numbers and try again.');
        }

        $number = round((float) $value, 2);
        if ($number < $min || $number > $max) {
            throw new InvalidArgumentException('The label layout contains a value that cannot be printed. Check the alignment numbers and try again.');
        }

        return $number;
    }

    private function formatModuleWidth(float $width): string
    {
        $formatted = number_format($width, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '1' : $formatted;
    }

    private function fontWidth(int $size, string $weight): int
    {
        if ($weight === 'normal') {
            return max(8, (int) round($size * 0.7));
        }

        return $size;
    }

    private function boolField($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function zplAlign(string $align): string
    {
        if ($align === 'center') {
            return 'C';
        }
        if ($align === 'right') {
            return 'R';
        }

        return 'L';
    }

    /**
     * Product names may include punctuation or Tamil/Sinhala. Those characters
     * stay in the field data. ^, ~, and \ are removed so they cannot start a
     * new ZPL command. Font 0 prints Latin text; the rest of the job is unchanged.
     */
    private function productNameForZpl(string $name, array $layout): string
    {
        if (empty($layout['show_product_name'])) {
            return '';
        }

        $name = $this->zplSafe($name, 120);
        if ($name === '' || ! empty($layout['product_name_wrap'])) {
            return $name;
        }

        return $this->ellipsis(
            $name,
            (int) ($layout['product_name_max_width'] ?? 220),
            (int) ($layout['product_name_font_width'] ?? 12)
        );
    }

    private function ellipsis(string $value, int $maxWidth, int $fontWidth): string
    {
        $fontWidth = max(1, $fontWidth);
        $maxChars = max(1, (int) floor($maxWidth / $fontWidth));
        if (mb_strlen($value) <= $maxChars) {
            return $value;
        }
        if ($maxChars <= 3) {
            return mb_substr($value, 0, $maxChars);
        }

        return rtrim(mb_substr($value, 0, $maxChars - 3)).'...';
    }

    private function zplSafe(string $value, int $max = 80): string
    {
        $value = strip_tags($value);
        $value = str_replace(["\r", "\n", '^', '~', '\\'], ' ', $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';

        return mb_substr(trim($value), 0, $max);
    }
}
