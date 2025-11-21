<?php

namespace App\Models;

use Illuminate\Support\Carbon;

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
        'form_submission_id',
        'form_slug',
        'status',
        'archived_at',
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
        'form_submission_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
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
    
    /**
     * Get the form submission associated with this order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function formSubmission()
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id', 'id');
    }
    
    /**
     * Get the status history for this order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id', 'id')->ordered();
    }
    
    /**
     * Get the notes for this order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany(OrderNote::class, 'order_id', 'id')->ordered();
    }
    
    /**
     * Scope a query to only include non-archived orders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }
    
    /**
     * Scope a query to only include archived orders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
    
    /**
     * Scope a query to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $query;
    }
    
    /**
     * Scope a query to search by customer contact info.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('phone', 'LIKE', "%{$search}%")
              ->orWhere('order_number', 'LIKE', "%{$search}%");
        });
    }
    
    /**
     * Check if this order is archived.
     *
     * @return bool
     */
    public function isArchived()
    {
        return !is_null($this->archived_at);
    }
    
    /**
     * Archive this order.
     *
     * @return bool
     */
    public function archive()
    {
        $this->archived_at = Carbon::now();
        return $this->save();
    }
    
    /**
     * Unarchive this order.
     *
     * @return bool
     */
    public function unarchive()
    {
        $this->archived_at = null;
        return $this->save();
    }
}
