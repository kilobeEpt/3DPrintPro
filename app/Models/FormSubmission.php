<?php

namespace App\Models;

/**
 * FormSubmission Model
 * 
 * Represents a form submission record.
 * 
 * @property int $id
 * @property int $form_id
 * @property string $form_slug
 * @property array|null $submitted_data
 * @property string $status
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $submitted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FormSubmission extends BaseModel
{
    protected $table = 'form_submissions';
    
    protected $fillable = [
        'form_id',
        'form_slug',
        'submitted_data',
        'status',
        'ip_address',
        'user_agent',
        'submitted_at',
    ];
    
    protected $casts = [
        'form_id' => 'integer',
        'submitted_data' => 'array',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_ARCHIVED = 'archived';
    
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id', 'id');
    }
    
    public function values()
    {
        return $this->hasMany(FormSubmissionValue::class, 'form_submission_id', 'id');
    }
    
    public function order()
    {
        return $this->hasOne(Order::class, 'form_submission_id', 'id');
    }
    
    public function scopeByFormSlug($query, $slug)
    {
        return $query->where('form_slug', $slug);
    }
    
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }
    
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('submitted_at', '>=', now()->subDays($days));
    }
}
