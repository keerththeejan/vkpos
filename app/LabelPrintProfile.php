<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LabelPrintProfile extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_by'];

    protected $casts = [
        'printer_dpi' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'columns' => 'integer',
        'col1_x' => 'integer',
        'col2_x' => 'integer',
        'col3_x' => 'integer',
        'barcode_x' => 'integer',
        'barcode_y' => 'integer',
        'barcode_width' => 'float',
        'barcode_height' => 'integer',
        'sku_x' => 'integer',
        'sku_y' => 'integer',
        'sku_font_size' => 'integer',
        'price_x' => 'integer',
        'price_y' => 'integer',
        'price_font_size' => 'integer',
        'vertical_x' => 'integer',
        'vertical_y' => 'integer',
        'vertical_font_size' => 'integer',
        'product_name_x' => 'integer',
        'product_name_y' => 'integer',
        'product_name_font_size' => 'integer',
        'product_name_font_width' => 'integer',
        'product_name_max_width' => 'integer',
        'product_name_max_lines' => 'integer',
        'show_product_name' => 'boolean',
        'product_name_wrap' => 'boolean',
        'label_gap_mm' => 'float',
        'top_offset_mm' => 'float',
        'left_offset_mm' => 'float',
        'is_default' => 'boolean',
    ];

    /**
     * 30 mm × 15 mm, 3-up, 203 DPI. Dot positions come from ZebraLabelGeometry
     * so the saved profile matches the ZPL ^PW, ^LL, and column origins.
     */
    public static function defaultAttributes(): array
    {
        $dots = app(\App\Services\ZebraLabelGeometry::class)->defaultProfileDots();

        return array_merge($dots, [
            'name' => 'Default Product Label',
            'printer_name' => (string) config('zebra_label.printer_name', 'ZDesigner ZD220-203dpi ZPL'),
            'sku_font_weight' => 'bold',
            'vertical_text' => '',
            'product_name_font_weight' => 'bold',
            'product_name_align' => 'center',
            'show_product_name' => true,
            'product_name_wrap' => false,
            'is_default' => true,
        ]);
    }

    public static function ensureDefaultForBusiness(int $businessId, $userId)
    {
        $exists = static::where('business_id', $businessId)->exists();

        if (! $exists) {
            static::create(array_merge(static::defaultAttributes(), [
                'business_id' => $businessId,
                'created_by' => $userId,
                'is_default' => true,
            ]));
        } elseif (! static::where('business_id', $businessId)->where('is_default', 1)->exists()) {
            $first = static::where('business_id', $businessId)->orderBy('id')->first();
            if ($first) {
                $first->is_default = true;
                $first->save();
            }
        }

        return static::where('business_id', $businessId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }
}
