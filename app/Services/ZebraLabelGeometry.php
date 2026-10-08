<?php

namespace App\Services;

use InvalidArgumentException;

class ZebraLabelGeometry
{
    public function mmToDots($mm, ?int $dpi = null): int
    {
        $dpi = $dpi ?: (int) config('zebra_label.dpi', 203);
        if ($dpi < 1) {
            $dpi = 203;
        }

        return (int) round(((float) $mm) * $dpi / 25.4);
    }

    public function media(array $overrides = []): array
    {
        $dpi = (int) ($overrides['printer_dpi'] ?? config('zebra_label.dpi', 203));
        if (! in_array($dpi, [203, 300], true)) {
            $dpi = 203;
        }

        $widthMm = (float) config('zebra_label.label_width_mm', 30);
        $heightMm = (float) config('zebra_label.label_height_mm', 15);
        $columns = (int) config('zebra_label.label_columns', 3);
        $gapMm = $this->boundedMm($overrides['label_gap_mm'] ?? null, (float) config('zebra_label.label_gap_mm', 2), 0, 8);
        $topMm = $this->boundedMm($overrides['top_offset_mm'] ?? null, (float) config('zebra_label.top_offset_mm', 0), -4, 4);
        $leftMm = $this->boundedMm($overrides['left_offset_mm'] ?? null, (float) config('zebra_label.left_offset_mm', 0), -4, 4);

        $labelW = $this->mmToDots($widthMm, $dpi);
        $labelH = $this->mmToDots($heightMm, $dpi);
        $gap = $this->mmToDots($gapMm, $dpi);
        $top = $this->signedDots($topMm, $dpi);
        $left = $this->signedDots($leftMm, $dpi);

        $origins = [];
        for ($i = 0; $i < $columns; $i++) {
            $origins[] = $i * ($labelW + $gap);
        }

        $printWidth = ($columns * $labelW) + (max(0, $columns - 1) * $gap);
        $pw = $printWidth + max(0, $left);
        $max = (int) config('zebra_label.max_print_width_dots', 832);
        if ($pw > $max || $printWidth > $max) {
            throw new InvalidArgumentException('The 3-up width including the label gap is wider than the ZD220 can print. Reduce the gap.');
        }

        $requestedHeight = (int) ($overrides['barcode_height'] ?? config('zebra_label.barcode_height', 40));
        $module = (float) ($overrides['barcode_width'] ?? config('zebra_label.barcode_module_width', 1));
        $stack = $this->stack($labelH, $requestedHeight);

        return [
            'dpi' => $dpi,
            'label_width_mm' => $widthMm,
            'label_height_mm' => $heightMm,
            'columns' => $columns,
            'label_gap_mm' => $gapMm,
            'top_offset_mm' => $topMm,
            'left_offset_mm' => $leftMm,
            'label_width' => $labelW,
            'label_height' => $labelH,
            'gap_dots' => $gap,
            'top_offset_dots' => $top,
            'left_offset_dots' => $left,
            'print_width' => $pw,
            'label_length' => $labelH,
            'origins' => $origins,
            'barcode_module' => $module,
            'stack' => $stack,
            'max_print_width_dots' => $max,
        ];
    }

    /**
     * Fixed vertical stack shared by every column. Barcode height can grow
     * until the price and name would leave the 15 mm label, then it is reduced.
     */
    public function stack(int $labelHeight, int $barcodeHeight): array
    {
        $top = (int) config('zebra_label.content_top', 2);
        $gap = (int) config('zebra_label.content_gap', 2);
        $skuH = (int) config('zebra_label.sku_font_height', 14);
        $priceH = (int) config('zebra_label.price_font_height', 20);
        $nameH = (int) config('zebra_label.name_font_height', 16);
        $bottom = 2;

        $barcodeHeight = max(24, $barcodeHeight);
        $reserved = $top + $gap + $skuH + $gap + $priceH + $gap + $nameH + $bottom;
        $maxBarcode = max(24, $labelHeight - $reserved);
        if ($barcodeHeight > $maxBarcode) {
            $barcodeHeight = $maxBarcode;
        }

        $barcodeY = $top;
        $skuY = $barcodeY + $barcodeHeight + $gap;
        $priceY = $skuY + $skuH + $gap;
        $nameY = $priceY + $priceH + $gap;

        return [
            'barcode_y' => $barcodeY,
            'barcode_height' => $barcodeHeight,
            'sku_y' => $skuY,
            'sku_font_size' => $skuH,
            'price_y' => $priceY,
            'price_font_size' => $priceH,
            'price_font_width' => (int) config('zebra_label.price_font_width', 12),
            'product_name_y' => $nameY,
            'product_name_font_size' => $nameH,
            'product_name_font_width' => (int) config('zebra_label.name_font_width', 9),
            'product_name_max_width' => 0,
        ];
    }

    public function previewPayload(): array
    {
        $media = $this->media([]);
        $stack = $media['stack'];
        $stack['product_name_max_width'] = $media['label_width'] - 8;

        return [
            'dpi' => $media['dpi'],
            'label_width_mm' => $media['label_width_mm'],
            'label_height_mm' => $media['label_height_mm'],
            'label_columns' => $media['columns'],
            'label_gap_mm' => $media['label_gap_mm'],
            'top_offset_mm' => $media['top_offset_mm'],
            'left_offset_mm' => $media['left_offset_mm'],
            'barcode_module_width' => (float) config('zebra_label.barcode_module_width', 1),
            'barcode_height' => $stack['barcode_height'],
            'sku_font_height' => $stack['sku_font_size'],
            'price_font_height' => $stack['price_font_size'],
            'price_font_width' => $stack['price_font_width'],
            'name_font_height' => $stack['product_name_font_size'],
            'name_font_width' => $stack['product_name_font_width'],
            'content_top' => (int) config('zebra_label.content_top', 2),
            'content_gap' => (int) config('zebra_label.content_gap', 2),
            'stack' => $stack,
            'print_width' => $media['print_width'],
            'label_length' => $media['label_length'],
            'origins' => $media['origins'],
            'label_width' => $media['label_width'],
            'label_height' => $media['label_height'],
            'gap_dots' => $media['gap_dots'],
            'max_print_width_dots' => $media['max_print_width_dots'],
            'printer_name' => (string) config('zebra_label.printer_name'),
        ];
    }

    public function defaultProfileDots(): array
    {
        $media = $this->media([]);
        $stack = $media['stack'];
        $nameWidth = $media['label_width'] - 8;

        return [
            'printer_dpi' => $media['dpi'],
            'width' => $media['print_width'],
            'height' => $media['label_length'],
            'columns' => $media['columns'],
            'col1_x' => $media['origins'][0],
            'col2_x' => $media['origins'][1],
            'col3_x' => $media['origins'][2],
            'label_gap_mm' => $media['label_gap_mm'],
            'top_offset_mm' => $media['top_offset_mm'],
            'left_offset_mm' => $media['left_offset_mm'],
            'barcode_x' => 0,
            'barcode_y' => $stack['barcode_y'],
            'barcode_width' => (float) config('zebra_label.barcode_module_width', 1),
            'barcode_height' => $stack['barcode_height'],
            'sku_x' => 4,
            'sku_y' => $stack['sku_y'],
            'sku_font_size' => $stack['sku_font_size'],
            'price_x' => 4,
            'price_y' => $stack['price_y'],
            'price_font_size' => $stack['price_font_size'],
            'vertical_x' => $media['label_width'] - 16,
            'vertical_y' => 2,
            'vertical_font_size' => 12,
            'product_name_x' => 4,
            'product_name_y' => $stack['product_name_y'],
            'product_name_font_size' => $stack['product_name_font_size'],
            'product_name_font_width' => $stack['product_name_font_width'],
            'product_name_max_width' => $nameWidth,
            'product_name_max_lines' => 1,
        ];
    }

    public function code128WidthDots(string $data, int $module): int
    {
        $chars = max(1, strlen($data));
        $modules = ($chars * 11) + 35;

        return $modules * max(1, $module);
    }

    /**
     * Integer module width only. Narrow the module until the bars and a quiet
     * zone fit. Never stretch the barcode. Data is not shortened here.
     */
    public function fitBarcode(string $data, float $requestedModule, int $labelWidth): array
    {
        $module = (int) max(1, min(10, (int) floor($requestedModule)));
        $reduced = false;
        $width = $this->code128WidthDots($data, $module);
        while ($module > 1 && ($width + (20 * $module)) > $labelWidth) {
            $module--;
            $reduced = true;
            $width = $this->code128WidthDots($data, $module);
        }

        $x = (int) floor(($labelWidth - $width) / 2);
        if ($x < 0) {
            $x = 0;
        }

        $quiet = 10 * $module;

        return [
            'module' => $module,
            'width' => $width,
            'x' => $x,
            'quiet' => $quiet,
            'reduced' => $reduced,
            'quiet_ok' => $x >= $quiet,
        ];
    }

    public function skuFont(string $sku, int $labelWidth): array
    {
        $length = max(1, strlen($sku));
        $available = max(20, $labelWidth - 8);
        $width = (int) floor($available / $length);
        $width = max(5, min(12, $width));
        $slot = (int) config('zebra_label.sku_font_height', 14);
        $height = min($slot, max(10, $width + 2));

        return [
            'height' => $height,
            'width' => $width,
            'fits' => ($width * $length) <= $available,
        ];
    }

    public function isLegacyLayout(array $layout, array $media): bool
    {
        $width = (int) ($layout['width'] ?? 0);
        $height = (int) ($layout['height'] ?? 0);
        $labelH = (int) $media['label_height'];
        $nameY = (int) ($layout['product_name_y'] ?? 0);
        $priceY = (int) ($layout['price_y'] ?? 0);

        if ($width === 800 && $height === 140) {
            return true;
        }

        return $nameY >= $labelH || $priceY >= $labelH;
    }

    private function boundedMm($value, float $default, float $min, float $max): float
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('The label layout contains a value that cannot be printed. Check the alignment numbers and try again.');
        }

        $number = round((float) $value, 2);
        if ($number < $min || $number > $max) {
            throw new InvalidArgumentException('The label layout contains a value that cannot be printed. Check the alignment numbers and try again.');
        }

        return $number;
    }

    private function signedDots(float $mm, int $dpi): int
    {
        $negative = $mm < 0;

        $dots = $this->mmToDots(abs($mm), $dpi);

        return $negative ? -$dots : $dots;
    }
}
