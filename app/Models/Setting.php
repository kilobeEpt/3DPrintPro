<?php

namespace App\Models;

/**
 * Setting Model
 * 
 * Represents application configuration settings stored in the database.
 * 
 * @property int $id
 * @property string $setting_key
 * @property mixed $setting_value
 * @property \Carbon\Carbon $updated_at
 */
class Setting extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings';
    
    /**
     * Indicates if the model should be timestamped.
     * Settings table only has updated_at, not created_at
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'setting_key',
        'setting_value',
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get a setting by key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        
        if (!$setting) {
            return $default;
        }
        
        // Try to decode JSON
        $decoded = json_decode($setting->setting_value, true);
        
        return $decoded !== null ? $decoded : $setting->setting_value;
    }
    
    /**
     * Set a setting value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public static function set($key, $value)
    {
        $jsonValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        
        return static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $jsonValue]
        );
    }
    
    /**
     * Get all settings as an associative array.
     *
     * @return array
     */
    public static function getAll()
    {
        $settings = static::all();
        $result = [];
        
        foreach ($settings as $setting) {
            $decoded = json_decode($setting->setting_value, true);
            $result[$setting->setting_key] = $decoded !== null ? $decoded : $setting->setting_value;
        }
        
        return $result;
    }
}
