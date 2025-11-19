<?php

namespace App\Models;

/**
 * FormField Model
 * 
 * Represents a field definition within a form.
 * 
 * @property int $id
 * @property int $form_id
 * @property string $name
 * @property string $label
 * @property string $type
 * @property string|null $placeholder
 * @property string|null $default_value
 * @property array|null $validation_rules
 * @property array|null $options
 * @property string|null $help_text
 * @property int $sort_order
 * @property bool $required
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FormField extends BaseModel
{
    protected $table = 'form_fields';
    
    protected $fillable = [
        'form_id',
        'name',
        'label',
        'type',
        'placeholder',
        'default_value',
        'validation_rules',
        'options',
        'help_text',
        'sort_order',
        'required',
        'active',
    ];
    
    protected $casts = [
        'form_id' => 'integer',
        'validation_rules' => 'array',
        'options' => 'array',
        'sort_order' => 'integer',
        'required' => 'boolean',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    const TYPE_TEXT = 'text';
    const TYPE_EMAIL = 'email';
    const TYPE_PHONE = 'phone';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_NUMBER = 'number';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RADIO = 'radio';
    const TYPE_FILE = 'file';
    const TYPE_HIDDEN = 'hidden';
    
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id', 'id');
    }
    
    public function submissionValues()
    {
        return $this->hasMany(FormSubmissionValue::class, 'form_field_id', 'id');
    }
    
    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }
    
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
