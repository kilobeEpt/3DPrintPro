<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * SettingsAudit Model
 * 
 * Audit log for settings changes. Tracks who changed what and when.
 * 
 * @property int $id
 * @property string $setting_key
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string|null $changed_by
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class SettingsAudit extends BaseModel
{
    protected $table = 'settings_audit';
    
    public $timestamps = false;
    
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'setting_key',
        'old_value',
        'new_value',
        'changed_by',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
    ];
    
    public function scopeBySettingKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }
    
    public function scopeByChangedBy($query, $changedBy)
    {
        return $query->where('changed_by', $changedBy);
    }
    
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }
    
    public static function logChange($settingKey, $oldValue, $newValue, $changedBy = 'system')
    {
        return static::create([
            'setting_key' => $settingKey,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => $changedBy,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
