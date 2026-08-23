<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductSerialNumber extends Model
{
    protected $guarded = ['id'];

    protected $table = 'product_serial_numbers';

    public function product()
    {
        return $this->belongsTo(\App\Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class);
    }

    public function purchase()
    {
        return $this->belongsTo(\App\Transaction::class, 'purchase_id');
    }

    public function purchaseLine()
    {
        return $this->belongsTo(\App\PurchaseLine::class, 'purchase_line_id');
    }

    public function sellLine()
    {
        return $this->belongsTo(\App\TransactionSellLine::class, 'sell_line_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('business_id', $business_id);
    }
}
