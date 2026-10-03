<?php

namespace App\Services;

use App\LabelPrintProfile;
use App\SellingPriceGroup;
use App\Utils\ProductUtil;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class ZebraLabelService
{
    protected $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
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
        // Zebra font 0 does not draw rupee glyphs. Keep the VKPOS amount and separators.
        $formatted = str_replace(['₹', '₨'], 'Rs.', $formatted);

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
        $layout['vertical_text'] = trim(strip_tags((string) ($layout['vertical_text'] ?? '')));
        if (mb_strlen($layout['vertical_text']) > 80) {
            throw new InvalidArgumentException('Vertical text must be 80 characters or fewer.');
        }

        $this->assertLayout($layout);

        return $layout;
    }

    /**
     * One ^XA...^XZ block per row. Each row prints the same product in all 3 columns.
     * Coordinates are column base X + element offset, matching the reference tool.
     * Product name is the VKPOS product name, placed with the same column rule.
     */
    public function buildZpl(array $layout, string $sku, string $price, string $vertical, int $rows, string $productName = ''): string
    {
        $sku = $this->zplSafe($sku);
        $price = $this->zplSafe($price);
        $vertical = $this->zplSafe($vertical);
        $productName = $this->productNameForZpl($productName, $layout);

        if ($sku === '') {
            throw new InvalidArgumentException('Barcode is missing for this product.');
        }

        $width = (int) $layout['width'];
        $height = (int) $layout['height'];
        $bases = [(int) $layout['col1_x'], (int) $layout['col2_x'], (int) $layout['col3_x']];
        $module = $this->formatModuleWidth((float) $layout['barcode_width']);
        $barcodeHeight = (int) $layout['barcode_height'];
        $skuSize = (int) $layout['sku_font_size'];
        $skuWidth = $this->fontWidth($skuSize, (string) ($layout['sku_font_weight'] ?? 'bold'));
        $priceSize = (int) $layout['price_font_size'];
        $verticalSize = (int) $layout['vertical_font_size'];
        $nameSize = (int) ($layout['product_name_font_size'] ?? 20);
        $nameWidth = (int) ($layout['product_name_font_width'] ?? 12);
        $nameMaxWidth = (int) ($layout['product_name_max_width'] ?? 220);
        $nameLines = ! empty($layout['product_name_wrap'])
            ? (int) ($layout['product_name_max_lines'] ?? 1)
            : 1;
        $nameAlign = $this->zplAlign((string) ($layout['product_name_align'] ?? 'center'));

        $zpl = '';
        for ($row = 0; $row < $rows; $row++) {
            $zpl .= "~SD25\n^XA\n^PW{$width}\n^LL{$height}\n^LH0,0\n";

            foreach ($bases as $base) {
                $barcodeX = $base + (int) $layout['barcode_x'];
                $barcodeY = (int) $layout['barcode_y'];
                $nameX = $base + (int) ($layout['product_name_x'] ?? 25);
                $nameY = (int) ($layout['product_name_y'] ?? 110);
                $skuX = $base + (int) $layout['sku_x'];
                $skuY = (int) $layout['sku_y'];
                $priceX = $base + (int) $layout['price_x'];
                $priceY = (int) $layout['price_y'];
                $verticalX = $base + (int) $layout['vertical_x'];
                $verticalY = (int) $layout['vertical_y'];

                $zpl .= "^FO{$barcodeX},{$barcodeY}^BY{$module}^BCN,{$barcodeHeight},N,N,N^FD{$sku}^FS\n";
                if ($productName !== '') {
                    // ^FB centers or wraps inside this label's name box, not across the whole row.
                    $zpl .= "^FO{$nameX},{$nameY}^A0N,{$nameSize},{$nameWidth}^FB{$nameMaxWidth},{$nameLines},0,{$nameAlign},0^FD{$productName}^FS\n";
                }
                $zpl .= "^FO{$skuX},{$skuY}^A0N,{$skuSize},{$skuWidth}^FD{$sku}^FS\n";
                $zpl .= "^FO{$priceX},{$priceY}^A0N,{$priceSize},{$priceSize}^FD{$price}^FS\n";
                if ($vertical !== '') {
                    $zpl .= "^FO{$verticalX},{$verticalY}^A0B,{$verticalSize},{$verticalSize}^FD{$vertical}^FS\n";
                }
            }

            $zpl .= "^XZ\n";
        }

        return $zpl;
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
