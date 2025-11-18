<?php

namespace App\Models;

/**
 * Order Model
 * 
 * Represents a customer order or contact form submission.
 * 
 * @property int $id
 * @property string $order_number
 * @property string $type
 * @property string $name
 * @property string|null $email
 * @property string $phone
 * @property string|null $telegram
 * @property string|null $service
 * @property string|null $subject
 * @property string|null $message
 * @property float $amount
 * @property array|null $calculator_data
 * @property string $status
 * @property bool $telegram_sent
 * @property string|null $telegram_error
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Order extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_number',
        'type',
        'name',
        'email',
        'phone',
        'telegram',
        'service',
        'subject',
        'message',
        'amount',
        'calculator_data',
        'status',
        'telegram_sent',
        'telegram_error',
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'telegram_sent' => 'boolean',
        'calculator_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];
    
    /**
     * The possible order types.
     */
    const TYPE_ORDER = 'order';
    const TYPE_CONTACT = 'contact';
    
    /**
     * The possible order statuses.
     */
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Scope a query to only include orders of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
    
    /**
     * Scope a query to only include orders with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope a query to only include orders where Telegram was not sent.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingTelegram($query)
    {
        return $query->where('telegram_sent', false);
    }
    
    /**
     * Check if this is an order type.
     *
     * @return bool
     */
    public function isOrder()
    {
        return $this->type === self::TYPE_ORDER;
    }
    
    /**
     * Check if this is a contact type.
     *
     * @return bool
     */
    public function isContact()
    {
        return $this->type === self::TYPE_CONTACT;
    }
}
