<?php

namespace App\Services;

use App\Utils\Util;
use App\Variation;
use Illuminate\Support\Facades\Log;

/**
 * Native Zebra ZPL for the 800×140, 3-up ZD230 label.
 * Preview coordinates in the browser use the same layout keys.
 */
class BarcodeLabelService
{
    public const SETTINGS_KEY = 'vkposBarcodeAlignmentV1';

    public const PRINT_WIDTH = 800;

    public const PRINT_LENGTH = 140;

    public const DARKNESS = 25;

    public const COLUMNS = 3;

    private Util $util;

    public function __construct(Util $util)
    {
        $this->util = $util;
    }

    public function defaultLayout(): array
    {
        return [
            'col1X' => 5,
            'col2X' => 271,
            'col3X' => 537,
            'barcode' => ['x' => 20, 'y' => 15, 'width' => 1.5, 'height' => 35],
            'sku' => ['x' => 65, 'y' => 60],
            'price' => ['x' => 30, 'y' => 85],
            'vertical' => ['x' => 250, 'y' => 15],
        ];
    }

    public function normalizeLayout($input): array
    {
        $defaults = $this->defaultLayout();
        $input = is_array($input) ? $input : [];

        $barcode = is_array($input['barcode'] ?? null) ? $input['barcode'] : [];
        $sku = is_array($input['sku'] ?? null) ? $input['sku'] : [];
        $price = is_array($input['price'] ?? null) ? $input['price'] : [];
        $vertical = is_array($input['vertical'] ?? null) ? $input['vertical'] : [];

        return [
            'col1X' => $this->clampInt($input['col1X'] ?? null, $defaults['col1X'], 0, 790),
            'col2X' => $this->clampInt($input['col2X'] ?? null, $defaults['col2X'], 0, 790),
            'col3X' => $this->clampInt($input['col3X'] ?? null, $defaults['col3X'], 0, 790),
            'barcode' => [
                'x' => $this->clampInt($barcode['x'] ?? null, $defaults['barcode']['x'], -50, 400),
                'y' => $this->clampInt($barcode['y'] ?? null, $defaults['barcode']['y'], 0, 130),
                'width' => $this->clampFloat($barcode['width'] ?? null, $defaults['barcode']['width'], 1, 10, 2),
                'height' => $this->clampInt($barcode['height'] ?? null, $defaults['barcode']['height'], 10, 120),
            ],
            'sku' => [
                'x' => $this->clampInt($sku['x'] ?? null, $defaults['sku']['x'], -50, 400),
                'y' => $this->clampInt($sku['y'] ?? null, $defaults['sku']['y'], 0, 130),
            ],
            'price' => [
                'x' => $this->clampInt($price['x'] ?? null, $defaults['price']['x'], -50, 400),
                'y' => $this->clampInt($price['y'] ?? null, $defaults['price']['y'], 0, 130),
            ],
            'vertical' => [
                'x' => $this->clampInt($vertical['x'] ?? null, $defaults['vertical']['x'], -20, 400),
                'y' => $this->clampInt($vertical['y'] ?? null, $defaults['vertical']['y'], 0, 130),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(int $businessId, string $term): array
    {
        $term = trim($term);
        if ($term === '' || mb_strlen($term) > 80) {
            return [];
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        $rows = Variation::query()
            ->join('products as p', 'p.id', '=', 'variations.product_id')
            ->join('product_variations as pv', 'pv.id', '=', 'variations.product_variation_id')
            ->where('p.business_id', $businessId)
            ->where('p.is_inactive', 0)
            ->whereNull('variations.deleted_at')
            ->where(function ($query) use ($like) {
                $query->where('p.name', 'like', $like)
                    ->orWhere('p.sku', 'like', $like)
                    ->orWhere('variations.sub_sku', 'like', $like);
            })
            ->orderBy('p.name')
            ->limit(20)
            ->get([
                'variations.id as variation_id',
                'variations.sub_sku',
                'variations.name as variation_name',
                'p.id as product_id',
                'p.name as product_name',
                'p.sku as product_sku',
                'pv.name as pv_name',
                'pv.is_dummy',
            ]);

        return $rows->map(function ($row) {
            return [
                'variation_id' => (int) $row->variation_id,
                'product_id' => (int) $row->product_id,
                'name' => $this->displayName($row),
                'sku' => (string) $row->sub_sku,
                'product_code' => (string) $row->product_sku,
                'barcode' => (string) $row->sub_sku,
            ];
        })->all();
    }

    public function findVariation(int $businessId, int $variationId): ?array
    {
        $row = Variation::query()
            ->join('products as p', 'p.id', '=', 'variations.product_id')
            ->join('product_variations as pv', 'pv.id', '=', 'variations.product_variation_id')
            ->where('p.business_id', $businessId)
            ->where('variations.id', $variationId)
            ->whereNull('variations.deleted_at')
            ->first([
                'variations.id as variation_id',
                'variations.sub_sku',
                'variations.name as variation_name',
                'variations.sell_price_inc_tax',
                'variations.default_sell_price',
                'p.id as product_id',
                'p.name as product_name',
                'p.sku as product_sku',
                'p.barcode_type',
                'pv.name as pv_name',
                'pv.is_dummy',
            ]);

        if (! $row) {
            return null;
        }

        $barcode = trim((string) $row->sub_sku);
        $price = $row->sell_price_inc_tax;
        $error = $this->validateBarcode($barcode);
        if ($error === null && ($price === null || $price === '')) {
            $error = 'Selected product does not have a selling price.';
        }

        return [
            'variation_id' => (int) $row->variation_id,
            'product_id' => (int) $row->product_id,
            'name' => $this->displayName($row),
            'sku' => $barcode,
            'product_code' => (string) $row->product_sku,
            'barcode' => $barcode,
            'barcode_type' => (string) $row->barcode_type,
            'price' => $price === null ? null : (float) $price,
            'price_formatted' => $price === null || $price === '' ? '' : $this->formatPrice($price, false),
            'printable' => $error === null,
            'error' => $error,
        ];
    }

    public function validateBarcode(?string $barcode): ?string
    {
        $barcode = trim((string) $barcode);
        if ($barcode === '') {
            return 'Selected product does not have a valid barcode.';
        }
        if (strlen($barcode) > 40) {
            return 'Barcode is too long for this label. Use 40 characters or fewer.';
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $barcode) || ! preg_match('/\A[\x20-\x7E]+\z/', $barcode)) {
            return 'Barcode contains characters that CODE128 cannot print.';
        }

        return null;
    }

    public function sanitizeText(?string $text, int $max = 80): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', (string) $text);
        $text = preg_replace('/[\x00-\x1F\x7F]/', '', $text) ?? '';
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($text) > $max) {
            $text = mb_substr($text, 0, $max);
        }

        return $text;
    }

    public function formatPrice($amount, bool $withPrefix = false): string
    {
        try {
            $formatted = $this->util->num_f($amount, true);
        } catch (\Throwable $e) {
            Log::warning('Barcode price format failed: '.$e->getMessage());
            $formatted = number_format((float) $amount, 2, '.', ',');
        }

        $formatted = $this->printerText($formatted);

        if ($withPrefix) {
            return 'Price '.$formatted;
        }

        return $formatted;
    }

    /**
     * Zebra font 0 prints ASCII. Keep the POS amount, swap symbols the printer cannot draw.
     */
    public function printerText(string $text): string
    {
        $text = strtr($text, [
            '₨' => 'Rs.',
            '₹' => 'Rs.',
            '€' => 'EUR ',
            '£' => 'GBP ',
            '¥' => 'JPY ',
        ]);
        $text = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';

        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }

    public function testPayload(bool $withPricePrefix = true): array
    {
        return [
            'barcode' => 'TEST123456',
            'sku' => 'TEST123456',
            'price_text' => $withPricePrefix ? $this->formatPrice(1000, true) : $this->formatPrice(1000, false),
            'vertical_text' => 'TEST LABEL',
            'show_barcode' => true,
            'show_sku' => true,
            'show_price' => true,
            'show_vertical' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $label
     */
    public function buildZpl(array $label, int $quantity, string $mode, array $layout, int $copies = 1): string
    {
        $layout = $this->normalizeLayout($layout);
        $quantity = max(1, min(500, $quantity));
        $copies = max(1, min(20, $copies));
        $mode = $mode === 'individual' ? 'individual' : 'row';

        $rows = [];
        if ($mode === 'row') {
            $full = [$label, $label, $label];
            for ($i = 0; $i < $quantity; $i++) {
                $rows[] = $full;
            }
        } else {
            $remaining = $quantity;
            while ($remaining > 0) {
                $row = [null, null, null];
                for ($col = 0; $col < self::COLUMNS && $remaining > 0; $col++) {
                    $row[$col] = $label;
                    $remaining--;
                }
                $rows[] = $row;
            }
        }

        $job = [];
        for ($copy = 0; $copy < $copies; $copy++) {
            foreach ($rows as $row) {
                $job[] = $row;
            }
        }

        return $this->generateZpl($job, $layout);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function generateZpl(array $rows, array $layout): string
    {
        $layout = $this->normalizeLayout($layout);
        $blocks = ['~SD'.self::DARKNESS];

        foreach ($rows as $slots) {
            $blocks[] = implode("\n", [
                '^XA',
                '^PW'.self::PRINT_WIDTH,
                '^LL'.self::PRINT_LENGTH,
                '^LH0,0',
                $this->generateRow(is_array($slots) ? $slots : [], $layout),
                '^XZ',
            ]);
        }

        return implode("\n", $blocks)."\n";
    }

    /**
     * @param  array<int, mixed>  $slots
     */
    public function generateRow(array $slots, array $layout): string
    {
        $bases = [$layout['col1X'], $layout['col2X'], $layout['col3X']];
        $parts = [];
        for ($i = 0; $i < self::COLUMNS; $i++) {
            if (empty($slots[$i]) || ! is_array($slots[$i])) {
                continue;
            }
            $parts[] = $this->generateLabel((int) $bases[$i], $slots[$i], $layout);
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $label
     */
    public function generateLabel(int $baseX, array $label, array $layout): string
    {
        $lines = [];
        $barcode = $this->sanitizeText($label['barcode'] ?? $label['sku'] ?? '', 40);

        if (! empty($label['show_barcode']) && $barcode !== '') {
            $x = $baseX + (int) $layout['barcode']['x'];
            $y = (int) $layout['barcode']['y'];
            $height = (int) $layout['barcode']['height'];
            $module = $this->formatModule((float) $layout['barcode']['width']);
            $lines[] = '^FO'.$x.','.$y;
            $lines[] = '^BY'.$module.',2,'.$height;
            $lines[] = '^BCN,'.$height.',N,N,N';
            $lines[] = $this->fieldData($barcode);
        }

        if (! empty($label['show_sku']) && $barcode !== '') {
            $x = $baseX + (int) $layout['sku']['x'];
            $y = (int) $layout['sku']['y'];
            $lines[] = '^FO'.$x.','.$y;
            $lines[] = '^A0N,18,14';
            $lines[] = $this->fieldData($barcode);
        }

        $priceText = $this->sanitizeText($label['price_text'] ?? '', 40);
        if (! empty($label['show_price']) && $priceText !== '') {
            $x = $baseX + (int) $layout['price']['x'];
            $y = (int) $layout['price']['y'];
            $lines[] = '^FO'.$x.','.$y;
            $lines[] = '^A0N,22,16';
            $lines[] = $this->fieldData($priceText);
        }

        $vertical = $this->sanitizeText($label['vertical_text'] ?? '', 80);
        if (! empty($label['show_vertical']) && $vertical !== '') {
            $x = $baseX + (int) $layout['vertical']['x'];
            $y = (int) $layout['vertical']['y'];
            $lines[] = '^FO'.$x.','.$y;
            $lines[] = '^A0B,18,14';
            $lines[] = $this->fieldData($vertical);
        }

        return implode("\n", $lines);
    }

    public function fieldData(string $text): string
    {
        $needsHex = false;
        $encoded = '';
        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];
            $ord = ord($char);
            if ($char === '^' || $char === '~' || $char === '\\' || $ord < 32 || $ord > 126) {
                $needsHex = true;
                $encoded .= '\\'.strtoupper(str_pad(dechex($ord), 2, '0', STR_PAD_LEFT));
            } else {
                $encoded .= $char;
            }
        }

        if ($needsHex) {
            return "^FH\\\n^FD".$encoded.'^FS';
        }

        return '^FD'.$encoded.'^FS';
    }

    public function formatModule(float $width): string
    {
        $width = max(1, min(10, $width));
        if (abs($width - round($width)) < 0.001) {
            return (string) (int) round($width);
        }

        return rtrim(rtrim(number_format($width, 2, '.', ''), '0'), '.');
    }

    public function stickerCount(int $quantity, string $mode, int $copies): int
    {
        $perJob = $mode === 'individual' ? $quantity : ($quantity * self::COLUMNS);

        return $perJob * max(1, $copies);
    }

    private function displayName(object $row): string
    {
        $name = (string) $row->product_name;
        if (empty($row->is_dummy)) {
            $name .= ' ('.$row->pv_name.':'.$row->variation_name.')';
        }

        return $name;
    }

    private function clampInt($value, int $default, int $min, int $max): int
    {
        if (! is_numeric($value)) {
            $value = $default;
        }

        return max($min, min($max, (int) round((float) $value)));
    }

    private function clampFloat($value, float $default, float $min, float $max, int $decimals): float
    {
        if (! is_numeric($value)) {
            $value = $default;
        }

        $number = max($min, min($max, (float) $value));

        return round($number, $decimals);
    }
}
