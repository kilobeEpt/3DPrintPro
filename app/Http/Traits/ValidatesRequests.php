<?php

namespace App\Http\Traits;

/**
 * Validates Requests Trait
 * 
 * Provides validation helpers for API controllers.
 */
trait ValidatesRequests
{
    /**
     * Validate required fields
     * 
     * @param array $data Input data
     * @param array $rules Validation rules
     * @return array Validation errors (empty if valid)
     */
    protected function validate(array $data, array $rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleList = is_array($rule) ? $rule : explode('|', $rule);
            
            foreach ($ruleList as $r) {
                // Parse rule and parameters
                $ruleParts = explode(':', $r);
                $ruleName = $ruleParts[0];
                $ruleParams = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];
                
                // Apply validation rule
                $error = $this->applyValidationRule($field, $data, $ruleName, $ruleParams);
                
                if ($error) {
                    $errors[$field] = $error;
                    break; // Stop at first error for this field
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Apply a single validation rule
     * 
     * @param string $field
     * @param array $data
     * @param string $rule
     * @param array $params
     * @return string|null Error message or null if valid
     */
    protected function applyValidationRule($field, $data, $rule, $params = [])
    {
        $value = $data[$field] ?? null;
        
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0' && $value !== 0) {
                    return ucfirst($field) . ' is required';
                }
                break;
                
            case 'string':
                if ($value !== null && !is_string($value)) {
                    return ucfirst($field) . ' must be a string';
                }
                break;
                
            case 'integer':
            case 'int':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return ucfirst($field) . ' must be an integer';
                }
                break;
                
            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    return ucfirst($field) . ' must be numeric';
                }
                break;
                
            case 'email':
                if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ucfirst($field) . ' must be a valid email address';
                }
                break;
                
            case 'boolean':
            case 'bool':
                if ($value !== null && !is_bool($value) && $value !== '0' && $value !== '1' && $value !== 0 && $value !== 1) {
                    return ucfirst($field) . ' must be boolean';
                }
                break;
                
            case 'array':
                if ($value !== null && !is_array($value)) {
                    return ucfirst($field) . ' must be an array';
                }
                break;
                
            case 'min':
                if ($value !== null && strlen($value) < (int)$params[0]) {
                    return ucfirst($field) . ' must be at least ' . $params[0] . ' characters';
                }
                break;
                
            case 'max':
                if ($value !== null && strlen($value) > (int)$params[0]) {
                    return ucfirst($field) . ' must not exceed ' . $params[0] . ' characters';
                }
                break;
                
            case 'in':
                if ($value !== null && !in_array($value, $params)) {
                    return ucfirst($field) . ' must be one of: ' . implode(', ', $params);
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Validate ID parameter
     * 
     * @param mixed $id
     * @param string $name Resource name for error message
     * @return int Validated ID
     */
    protected function validateId($id, $name = 'resource')
    {
        $validId = filter_var($id, FILTER_VALIDATE_INT);
        
        if (!$validId) {
            ApiResponse::validationError("Invalid {$name} ID");
        }
        
        return $validId;
    }
}
