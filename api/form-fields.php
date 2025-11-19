<?php
// ========================================
// Form Fields API Endpoint
// Admin: CRUD operations on form fields
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\FormField;
use App\Models\Form;

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

try {
    switch ($method) {
        case 'GET':
            requireAdminAuth();
            
            if (isset($_GET['id'])) {
                // Get single field
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid field ID');
                }
                
                $field = FormField::with('form')->find($id);
                
                if (!$field) {
                    ApiResponse::notFound('Field not found');
                }
                
                ApiResponse::success(['field' => [
                    'id' => $field->id,
                    'form_id' => $field->form_id,
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'placeholder' => $field->placeholder,
                    'default_value' => $field->default_value,
                    'validation_rules' => $field->validation_rules,
                    'options' => $field->options,
                    'help_text' => $field->help_text,
                    'sort_order' => $field->sort_order,
                    'required' => $field->required,
                    'active' => $field->active,
                    'created_at' => $field->created_at->toIso8601String(),
                    'updated_at' => $field->updated_at->toIso8601String(),
                ]]);
                
            } elseif (isset($_GET['form_id'])) {
                // Get all fields for a form
                $formId = filter_var($_GET['form_id'], FILTER_VALIDATE_INT);
                if (!$formId) {
                    ApiResponse::validationError('Invalid form ID');
                }
                
                $fields = FormField::where('form_id', $formId)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($field) {
                        return [
                            'id' => $field->id,
                            'form_id' => $field->form_id,
                            'name' => $field->name,
                            'label' => $field->label,
                            'type' => $field->type,
                            'placeholder' => $field->placeholder,
                            'default_value' => $field->default_value,
                            'validation_rules' => $field->validation_rules,
                            'options' => $field->options,
                            'help_text' => $field->help_text,
                            'sort_order' => $field->sort_order,
                            'required' => $field->required,
                            'active' => $field->active,
                            'created_at' => $field->created_at->toIso8601String(),
                            'updated_at' => $field->updated_at->toIso8601String(),
                        ];
                    })
                    ->all();
                
                ApiResponse::success(['fields' => $fields]);
            } else {
                ApiResponse::validationError('form_id parameter is required');
            }
            break;
            
        case 'POST':
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('form_fields_create');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Validate required fields
            $validationErrors = [];
            
            if (empty($data['form_id']) || !is_numeric($data['form_id'])) {
                $validationErrors['form_id'] = 'Form ID is required';
            } else {
                // Check if form exists
                $form = Form::find($data['form_id']);
                if (!$form) {
                    $validationErrors['form_id'] = 'Form not found';
                }
            }
            
            if (empty($data['name']) || !is_string($data['name'])) {
                $validationErrors['name'] = 'Field name is required';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['name'])) {
                $validationErrors['name'] = 'Field name must contain only letters, numbers, and underscores';
            }
            
            if (empty($data['label']) || !is_string($data['label'])) {
                $validationErrors['label'] = 'Field label is required';
            }
            
            if (empty($data['type']) || !is_string($data['type'])) {
                $validationErrors['type'] = 'Field type is required';
            }
            
            if (!empty($validationErrors)) {
                ApiResponse::validationError('Validation failed', $validationErrors);
            }
            
            try {
                // Get max sort_order for the form
                $maxSortOrder = FormField::where('form_id', $data['form_id'])->max('sort_order') ?? -1;
                
                $field = FormField::create([
                    'form_id' => $data['form_id'],
                    'name' => $data['name'],
                    'label' => $data['label'],
                    'type' => $data['type'],
                    'placeholder' => $data['placeholder'] ?? null,
                    'default_value' => $data['default_value'] ?? null,
                    'validation_rules' => $data['validation_rules'] ?? null,
                    'options' => $data['options'] ?? null,
                    'help_text' => $data['help_text'] ?? null,
                    'sort_order' => $data['sort_order'] ?? ($maxSortOrder + 1),
                    'required' => $data['required'] ?? false,
                    'active' => $data['active'] ?? true,
                ]);
                
                ApiLogger::info("Form field created", [
                    'field_id' => $field->id,
                    'form_id' => $field->form_id,
                    'name' => $field->name,
                ]);
                
                ApiResponse::created([
                    'field_id' => $field->id,
                    'message' => 'Field created successfully'
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error creating form field", ['exception' => $e]);
                ApiResponse::serverError('Failed to create field. Please try again.');
            }
            break;
            
        case 'PUT':
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('form_fields_update');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Field ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid field ID');
            }
            
            $field = FormField::find($id);
            if (!$field) {
                ApiResponse::notFound('Field not found');
            }
            
            try {
                $updateData = [];
                $allowedFields = ['name', 'label', 'type', 'placeholder', 'default_value', 
                                 'validation_rules', 'options', 'help_text', 'sort_order', 
                                 'required', 'active'];
                
                foreach ($allowedFields as $fieldName) {
                    if (array_key_exists($fieldName, $data)) {
                        $updateData[$fieldName] = $data[$fieldName];
                    }
                }
                
                $field->update($updateData);
                
                ApiLogger::info("Form field updated", [
                    'field_id' => $field->id,
                    'updated_fields' => array_keys($updateData),
                ]);
                
                ApiResponse::success([
                    'message' => 'Field updated successfully',
                    'field_id' => $field->id
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error updating form field", ['field_id' => $id, 'exception' => $e]);
                ApiResponse::serverError('Failed to update field. Please try again.');
            }
            break;
            
        case 'PATCH':
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('form_fields_reorder');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || !isset($data['fields']) || !is_array($data['fields'])) {
                ApiResponse::validationError('Invalid data format. Expected fields array.');
            }
            
            try {
                // Bulk update sort_order
                foreach ($data['fields'] as $fieldData) {
                    if (isset($fieldData['id']) && isset($fieldData['sort_order'])) {
                        FormField::where('id', $fieldData['id'])
                            ->update(['sort_order' => $fieldData['sort_order']]);
                    }
                }
                
                ApiLogger::info("Form fields reordered", [
                    'count' => count($data['fields']),
                ]);
                
                ApiResponse::success([
                    'message' => 'Fields reordered successfully',
                    'updated_count' => count($data['fields'])
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error reordering form fields", ['exception' => $e]);
                ApiResponse::serverError('Failed to reorder fields. Please try again.');
            }
            break;
            
        case 'DELETE':
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('form_fields_delete');
            
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Field ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid field ID');
            }
            
            $field = FormField::find($id);
            if (!$field) {
                ApiResponse::notFound('Field not found');
            }
            
            try {
                $fieldName = $field->name;
                $formId = $field->form_id;
                $field->delete();
                
                ApiLogger::info("Form field deleted", [
                    'field_id' => $id,
                    'form_id' => $formId,
                    'name' => $fieldName,
                ]);
                
                ApiResponse::success([
                    'message' => 'Field deleted successfully',
                    'field_id' => $id
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error deleting form field", ['field_id' => $id, 'exception' => $e]);
                ApiResponse::serverError('Failed to delete field. Please try again.');
            }
            break;
            
        default:
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in form-fields endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}
