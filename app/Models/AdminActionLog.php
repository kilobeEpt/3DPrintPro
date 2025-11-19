<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    protected $table = 'admin_action_logs';
    
    public $timestamps = false;
    
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'payload',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'user_id' => 'integer',
        'entity_id' => 'integer',
        'payload' => 'array',
    ];
    
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_EXPORT = 'export';
    
    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }
    
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
    
    public function scopeByEntity($query, $entityType, $entityId = null)
    {
        $query = $query->where('entity_type', $entityType);
        
        if ($entityId !== null) {
            $query->where('entity_id', $entityId);
        }
        
        return $query;
    }
    
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    public static function log($userId, $action, $entityType = null, $entityId = null, $payload = null, $ipAddress = null, $userAgent = null)
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => $payload,
            'ip_address' => $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
