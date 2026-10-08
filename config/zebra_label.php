<?php

/**
 * ZDesigner ZD220, 203 DPI, 3-up 30 mm × 15 mm barcode sticker roll.
 *
 * This file is the only place these media numbers are defined.
 * ZebraLabelGeometry::mmToDots() converts them for ^PW, ^LL, and field origins.
 * The horizontal gap is part of the column pitch. The vertical liner gap is left
 * to the printer gap sensor, so ^LL stays the label height and rows do not drift.
 */

return [
    'dpi' => 203,
    'label_width_mm' => 30,
    'label_height_mm' => 15,
    'label_columns' => 3,
    'label_gap_mm' => 2.0,
    'top_offset_mm' => 0.0,
    'left_offset_mm' => 0.0,
    'barcode_module_width' => 1,
    'barcode_height' => 40,
    'sku_font_height' => 14,
    'price_font_height' => 20,
    'price_font_width' => 12,
    'name_font_height' => 16,
    'name_font_width' => 9,
    'content_top' => 2,
    'content_gap' => 2,
    'max_print_width_dots' => 832,
    'printer_name' => 'ZDesigner ZD220-203dpi ZPL',
];
