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
        'is_default' => 'boolean',
    ];

    /**
     * Proven Zebra ZD230 defaults from the reference label tool.
     * Positions are dots. Element X is added to each column base X.
     */
    public static function defaultAttributes(): array
    {
        return [
            'name' => 'Default Product Label',
            'printer_name' => 'ZDesigner ZD220-203dpi ZPL',
            'printer_dpi' => 203,
            'width' => 800,
            'height' => 140,
            'columns' => 3,
            'col1_x' => 5,
            'col2_x' => 271,
            'col3_x' => 537,
            'barcode_x' => 20,
            'barcode_y' => 15,
            'barcode_width' => 1.5,
            'barcode_height' => 35,
            'sku_x' => 65,
            'sku_y' => 60,
            'sku_font_size' => 20,
            'sku_font_weight' => 'bold',
            'price_x' => 30,
            'price_y' => 85,
            'price_font_size' => 24,
            'vertical_text' => '',
            'vertical_x' => 250,
            'vertical_y' => 15,
            'vertical_font_size' => 15,
            'product_name_x' => 25,
            'product_name_y' => 110,
            'product_name_font_size' => 20,
            'product_name_font_width' => 12,
            'product_name_font_weight' => 'bold',
            'product_name_max_width' => 220,
            'product_name_max_lines' => 1,
            'product_name_align' => 'center',
            'show_product_name' => true,
            'product_name_wrap' => false,
            'is_default' => true,
        ];
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
