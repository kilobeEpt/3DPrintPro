<?php

namespace App\Models;

/**
 * Order Note Model
 * 
 * Internal notes for orders.
 * 
 * @property int $id
 * @property int $order_id
 * @property string $note
 * @property int|null $created_by
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OrderNote extends BaseModel
{
    protected $table = 'order_notes';
    
    protected $fillable = [
        'order_id',
        'note',
        'created_by',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'order_id' => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
    
    public function createdBy()
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
    
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }
    
    public function scopeOrdered($query, $direction = 'asc')
{
    return $query->orderBy('created_at', $direction);
}
    
    public static function addNote($orderId, $note, $createdBy = null)
    {
        return static::create([
            'order_id' => $orderId,
            'note' => $note,
            'created_by' => $createdBy,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
