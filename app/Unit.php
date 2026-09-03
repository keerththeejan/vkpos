<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Return list of units for a business
     *
     * @param  int  $business_id
     * @param  bool  $show_none = true
     * @return array
     */
    public static function forDropdown($business_id, $show_none = false, $only_base = true, $include_unit_id = null)
    {
        $query = Unit::where('business_id', $business_id);
        if ($only_base) {
            $query->where(function ($q) use ($include_unit_id) {
                $q->whereNull('base_unit_id');
                if (! empty($include_unit_id)) {
                    $q->orWhere('id', $include_unit_id);
                }
            });
        }

        $units = $query->select(DB::raw('CONCAT(actual_name, " (", short_name, ")") as name'), 'id')->get();
        $dropdown = $units->pluck('name', 'id');
        if ($show_none) {
            $dropdown->prepend(__('messages.please_select'), '');
        }

        return $dropdown;
    }

    /**
     * Multiplier that converts 1 of this unit into the family base unit.
     * Base units return 1.
     */
    public function multiplierToBase()
    {
        if (! empty($this->base_unit_id) && ! empty($this->base_unit_multiplier) && (float) $this->base_unit_multiplier != 0.0) {
            return (float) $this->base_unit_multiplier;
        }

        return 1.0;
    }

    /**
     * Id of the base unit in this unit's family.
     */
    public function familyBaseUnitId()
    {
        return ! empty($this->base_unit_id) ? (int) $this->base_unit_id : (int) $this->id;
    }

    public function sub_units()
    {
        return $this->hasMany(\App\Unit::class, 'base_unit_id');
    }

    public function base_unit()
    {
        return $this->belongsTo(\App\Unit::class, 'base_unit_id');
    }
}
