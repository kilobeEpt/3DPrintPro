<?php

namespace App\Models;

/**
 * Order Status History Model
 * 
 * Tracks status transitions for orders.
 * 
 * @property int $id
 * @property int $order_id
 * @property string|null $old_status
 * @property string $new_status
 * @property int|null $changed_by
 * @property string|null $comment
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class OrderStatusHistory extends BaseModel
{
    protected $table = 'order_status_history';
    
    public $timestamps = false;
    
    const CREATED_AT = 'created_at';
    
    protected $fillable = [
        'order_id',
        'old_status',
        'new_status',
        'changed_by',
        'comment',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'order_id' => 'integer',
        'changed_by' => 'integer',
        'created_at' => 'datetime',
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
    
    public function changedBy()
    {
        return $this->belongsTo(AdminUser::class, 'changed_by');
    }
    
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('new_status', $status);
    }
    
    public function scopeOrdered($query, $direction = 'asc')
{
    return $query->orderBy('created_at', $direction);
}
    
    public static function logStatusChange($orderId, $oldStatus, $newStatus, $changedBy = null, $comment = null)
    {
        return static::create([
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'comment' => $comment,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
