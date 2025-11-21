<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AdminSession extends Model
{
    protected $table = 'admin_sessions';
    
    public $timestamps = true;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    protected $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'csrf_token',
        'expires_at',
        'last_activity_at',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'user_id' => 'integer',
    ];
    
    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }
    
    public function scopeBySessionId($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
    
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }
    
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }
    
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }
    
    public function isActive()
    {
        return !$this->isExpired();
    }
    
    public function updateActivity()
    {
        $this->last_activity_at = Carbon::now();
        $this->save();
    }
    
    public function extendExpiration($minutes = 30)
    {
        $this->expires_at = Carbon::now()->addMinutes($minutes);
        $this->save();
    }
    
    public function regenerateCsrfToken()
    {
        $this->csrf_token = bin2hex(random_bytes(32));
        $this->save();
        return $this->csrf_token;
    }
}
