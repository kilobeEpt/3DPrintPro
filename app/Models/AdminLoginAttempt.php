<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginAttempt extends Model
{
    protected $table = 'admin_login_attempts';
    
    public $timestamps = false;
    
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'success',
        'failure_reason',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'success' => 'boolean',
    ];
    
    const REASON_INVALID_CREDENTIALS = 'invalid_credentials';
    const REASON_ACCOUNT_LOCKED = 'account_locked';
    const REASON_ACCOUNT_INACTIVE = 'account_inactive';
    const REASON_RATE_LIMIT = 'rate_limit_exceeded';
    const REASON_LOCKOUT = 'temporary_lockout';
    
    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }
    
    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }
    
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }
    
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }
    
    public function scopeRecent($query, $minutes = 15)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
    
    public static function logSuccess($email, $ipAddress, $userAgent = null)
    {
        return static::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => true,
            'failure_reason' => null,
        ]);
    }
    
    public static function logFailure($email, $ipAddress, $reason, $userAgent = null)
    {
        return static::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => false,
            'failure_reason' => $reason,
        ]);
    }
}
