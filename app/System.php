<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    /**
     * Per-request property cache.
     *
     * @var array<string, mixed>
     */
    protected static $propertyCache = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Return the value of the property
     *
     * @param $key string
     * @return mixed
     */
    public static function getProperty($key)
    {
        if (array_key_exists($key, self::$propertyCache)) {
            return self::$propertyCache[$key];
        }

        $row = System::where('key', $key)
                ->first();

        $value = isset($row->value) ? $row->value : null;
        self::$propertyCache[$key] = $value;

        return $value;
    }

    /**
     * Return the value of the multiple properties
     *
     * @param $keys array
     * @return array
     */
    public static function getProperties($keys, $pluck = false)
    {
        $cacheKey = ($pluck ? 'pluck:' : 'get:').implode('|', $keys);
        if (array_key_exists($cacheKey, self::$propertyCache)) {
            return self::$propertyCache[$cacheKey];
        }

        if ($pluck == true) {
            $result = System::whereIn('key', $keys)
                ->pluck('value', 'key');
        } else {
            $result = System::whereIn('key', $keys)
                ->get()
                ->toArray();
        }

        self::$propertyCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Return the system default currency details
     *
     * @param void
     * @return object
     */
    public static function getCurrency()
    {
        $c_id = System::where('key', 'app_currency_id')
                ->first()
                ->value;

        $currency = Currency::find($c_id);

        return $currency;
    }

    /**
     * Set the property
     *
     * @param $key
     * @param $value
     * @return void
     */
    public static function setProperty($key, $value)
    {
        System::where('key', $key)
            ->update(['value' => $value]);
        self::$propertyCache[$key] = $value;
    }

    /**
     * Remove the specified property
     *
     * @param $key
     * @return void
     */
    public static function removeProperty($key)
    {
        System::where('key', $key)
            ->delete();
        unset(self::$propertyCache[$key]);
    }

    /**
     * Add a new property, if exist update the value
     *
     * @param $key
     * @param $value
     * @return void
     */
    public static function addProperty($key, $value)
    {
        System::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        self::$propertyCache[$key] = $value;
    }
}
