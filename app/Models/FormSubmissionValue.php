<?php

namespace App\Models;

/**
 * FormSubmissionValue Model
 * 
 * Represents an individual field value within a form submission.
 * Provides normalized storage for querying specific field values.
 * 
 * @property int $id
 * @property int $form_submission_id
 * @property int|null $form_field_id
 * @property string $field_name
 * @property string|null $field_value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FormSubmissionValue extends BaseModel
{
    protected $table = 'form_submission_values';
    
    protected $fillable = [
        'form_submission_id',
        'form_field_id',
        'field_name',
        'field_value',
    ];
    
    protected $casts = [
        'form_submission_id' => 'integer',
        'form_field_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function submission()
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id', 'id');
    }
    
    public function field()
    {
        return $this->belongsTo(FormField::class, 'form_field_id', 'id');
    }
    
    public function scopeByFieldName($query, $fieldName)
    {
        return $query->where('field_name', $fieldName);
    }
    
    public function scopeByFieldValue($query, $fieldValue)
    {
        return $query->where('field_value', 'LIKE', "%{$fieldValue}%");
    }
}
