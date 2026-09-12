<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceScheme extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * POS locations using this scheme for POS invoices.
     */
    public function posLocations()
    {
        return $this->hasMany(\App\BusinessLocation::class, 'invoice_scheme_id');
    }

    /**
     * Locations using this scheme for sale invoices.
     */
    public function saleLocations()
    {
        return $this->hasMany(\App\BusinessLocation::class, 'sale_invoice_scheme_id');
    }

    /**
     * Returns list of invoice schemes in array format
     */
    public static function forDropdown($business_id)
    {
        $dropdown = InvoiceScheme::where('business_id', $business_id)
                                ->pluck('name', 'id');

        return $dropdown;
    }

    /**
     * Retrieves the default invoice scheme
     */
    public static function getDefault($business_id)
    {
        $default = InvoiceScheme::where('business_id', $business_id)
                                ->where('is_default', 1)
                                ->first();

        return $default;
    }
}
