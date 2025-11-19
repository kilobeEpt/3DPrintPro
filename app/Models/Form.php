<?php

namespace App\Models;

/**
 * Form Model
 * 
 * Represents a dynamic form definition.
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array|null $settings
 * @property string|null $notification_email
 * @property string|null $success_message
 * @property string|null $redirect_url
 * @property int $sort_order
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Form extends BaseModel
{
    protected $table = 'forms';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'settings',
        'notification_email',
        'success_message',
        'redirect_url',
        'sort_order',
        'active',
    ];
    
    protected $casts = [
        'settings' => 'array',
        'sort_order' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function fields()
    {
        return $this->hasMany(FormField::class, 'form_id', 'id');
    }
    
    public function activeFields()
    {
        return $this->hasMany(FormField::class, 'form_id', 'id')->where('active', true)->orderBy('sort_order');
    }
    
    public function submissions()
    {
        return $this->hasMany(FormSubmission::class, 'form_id', 'id');
    }
    
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}
