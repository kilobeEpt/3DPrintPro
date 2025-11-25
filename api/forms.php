<?php
// ========================================
// Forms API Endpoint
// Public: Retrieve form configurations
// Admin: CRUD operations on forms and fields
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/helpers/form_service.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Form;
use App\Models\FormField;

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

try {
    switch ($method) {
        case 'GET':
            // Public: Get form by slug (with caching headers)
            // Admin: List all forms with pagination and filters
            
            if (isset($_GET['slug'])) {
                // Public endpoint - get form configuration by slug
                $slug = $_GET['slug'];
                
                // Set cache headers for public form configs (5 minutes)
                header('Cache-Control: public, max-age=300');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
                
                $formData = FormService::loadForm($slug, true);
                
                if (!$formData) {
                    ApiLogger::warning("Form not found", ['slug' => $slug]);
                    ApiResponse::notFound('Form not found');
                }
                
                ApiResponse::success(['form' => $formData]);
                
            } elseif (isset($_GET['id'])) {
                // Admin: Get single form by ID
                // TODO: Re-enable auth when session/header auth is fixed
                // requireAdminAuth();
                
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid form ID');
                }
                
                $form = Form::with('fields')->find($id);
                
                if (!$form) {
                    ApiLogger::warning("Form not found", ['id' => $id]);
                    ApiResponse::notFound('Form not found');
                }
                
                // Convert to array with fields
                $formData = [
                    'id' => $form->id,
                    'name' => $form->name,
                    'slug' => $form->slug,
                    'description' => $form->description,
                    'settings' => $form->settings,
                    'notification_email' => $form->notification_email,
                    'success_message' => $form->success_message,
                    'redirect_url' => $form->redirect_url,
                    'sort_order' => $form->sort_order,
                    'active' => $form->active,
                    'created_at' => $form->created_at->toIso8601String(),
                    'updated_at' => $form->updated_at->toIso8601String(),
                    'fields' => $form->fields->map(function ($field) {
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
                    })->all()
                ];
                
                ApiResponse::success(['form' => $formData]);
                
            } else {
                // Admin: List all forms with pagination
                // TODO: Re-enable auth when session/header auth is fixed
                // requireAdminAuth();
                
                $query = Form::query();
                
                // Apply filters
                if (isset($_GET['active'])) {
                    $query->where('active', filter_var($_GET['active'], FILTER_VALIDATE_BOOLEAN));
                }
                
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = $_GET['search'];
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('slug', 'LIKE', "%{$search}%")
                          ->orWhere('description', 'LIKE', "%{$search}%");
                    });
                }
                
                // Pagination
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : 20;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit === false || $limit < 1) $limit = 20;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $total = $query->count();
                
                $forms = $query->orderBy('sort_order', 'asc')
                               ->orderBy('name', 'asc')
                               ->skip($offset)
                               ->take($limit)
                               ->get()
                               ->map(function ($form) {
                                   return [
                                       'id' => $form->id,
                                       'name' => $form->name,
                                       'slug' => $form->slug,
                                       'description' => $form->description,
                                       'settings' => $form->settings,
                                       'notification_email' => $form->notification_email,
                                       'success_message' => $form->success_message,
                                       'redirect_url' => $form->redirect_url,
                                       'sort_order' => $form->sort_order,
                                       'active' => $form->active,
                                       'created_at' => $form->created_at->toIso8601String(),
                                       'updated_at' => $form->updated_at->toIso8601String(),
                                       'fields_count' => $form->fields()->count(),
                                   ];
                               })
                               ->all();
                
                ApiResponse::success(
                    ['forms' => $forms],
                    [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                );
            }
            break;
            
        case 'POST':
            // Admin: Create new form
            // TODO: Re-enable auth when session/header auth is fixed
            // requireAdminAuthWithCsrf();
            $rateLimiter->apply('forms_create');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Validate required fields
            $validationErrors = [];
            
            if (empty($data['name']) || !is_string($data['name'])) {
                $validationErrors['name'] = 'Form name is required';
            }
            
            if (empty($data['slug']) || !is_string($data['slug'])) {
                $validationErrors['slug'] = 'Form slug is required';
            } elseif (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
                $validationErrors['slug'] = 'Form slug must contain only lowercase letters, numbers, and hyphens';
            }
            
            // Check if slug is unique
            if (empty($validationErrors['slug'])) {
                $existingForm = Form::where('slug', $data['slug'])->first();
                if ($existingForm) {
                    $validationErrors['slug'] = 'Form slug already exists';
                }
            }
            
            if (!empty($validationErrors)) {
                ApiLogger::validationError('POST /api/forms.php', $validationErrors);
                ApiResponse::validationError('Validation failed', $validationErrors);
            }
            
            // Create form
            try {
                $form = Form::create([
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'description' => $data['description'] ?? null,
                    'settings' => $data['settings'] ?? null,
                    'notification_email' => $data['notification_email'] ?? null,
                    'success_message' => $data['success_message'] ?? null,
                    'redirect_url' => $data['redirect_url'] ?? null,
                    'sort_order' => $data['sort_order'] ?? 0,
                    'active' => $data['active'] ?? true,
                ]);
                
                ApiLogger::info("Form created successfully", [
                    'form_id' => $form->id,
                    'slug' => $form->slug,
                ]);
                
                ApiResponse::created([
                    'form_id' => $form->id,
                    'slug' => $form->slug,
                    'message' => 'Form created successfully'
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error creating form", ['exception' => $e]);
                ApiResponse::serverError('Failed to create form. Please try again.');
            }
            break;
            
        case 'PUT':
            // Admin: Update form
            // TODO: Re-enable auth when session/header auth is fixed
            // requireAdminAuthWithCsrf();
            $rateLimiter->apply('forms_update');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Form ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid form ID');
            }
            
            // Check if form exists
            $form = Form::find($id);
            if (!$form) {
                ApiLogger::warning("Attempt to update non-existent form", ['id' => $id]);
                ApiResponse::notFound('Form not found');
            }
            
            // Validate slug uniqueness if changed
            if (isset($data['slug']) && $data['slug'] !== $form->slug) {
                if (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
                    ApiResponse::validationError('Form slug must contain only lowercase letters, numbers, and hyphens', ['slug' => 'Invalid format']);
                }
                
                $existingForm = Form::where('slug', $data['slug'])->where('id', '!=', $id)->first();
                if ($existingForm) {
                    ApiResponse::validationError('Form slug already exists', ['slug' => 'Slug must be unique']);
                }
            }
            
            // Update form
            try {
                $updateData = [];
                $allowedFields = ['name', 'slug', 'description', 'settings', 'notification_email', 
                                 'success_message', 'redirect_url', 'sort_order', 'active'];
                
                foreach ($allowedFields as $field) {
                    if (array_key_exists($field, $data)) {
                        $updateData[$field] = $data[$field];
                    }
                }
                
                $form->update($updateData);
                
                ApiLogger::info("Form updated successfully", [
                    'form_id' => $form->id,
                    'updated_fields' => array_keys($updateData),
                ]);
                
                ApiResponse::success([
                    'message' => 'Form updated successfully',
                    'form_id' => $form->id
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error updating form", ['form_id' => $id, 'exception' => $e]);
                ApiResponse::serverError('Failed to update form. Please try again.');
            }
            break;
            
        case 'DELETE':
            // Admin: Delete form
            // TODO: Re-enable auth when session/header auth is fixed
            // requireAdminAuthWithCsrf();
            $rateLimiter->apply('forms_delete');
            
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Form ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid form ID');
            }
            
            // Check if form exists
            $form = Form::find($id);
            if (!$form) {
                ApiLogger::warning("Attempt to delete non-existent form", ['id' => $id]);
                ApiResponse::notFound('Form not found');
            }
            
            // Check if form has submissions
            $submissionsCount = $form->submissions()->count();
            if ($submissionsCount > 0 && !isset($_GET['force'])) {
                ApiResponse::validationError(
                    'Form has ' . $submissionsCount . ' submissions. Use force=1 to delete anyway.',
                    ['submissions_count' => $submissionsCount]
                );
            }
            
            try {
                $slug = $form->slug;
                $form->delete();
                
                ApiLogger::info("Form deleted successfully", [
                    'form_id' => $id,
                    'slug' => $slug,
                    'had_submissions' => $submissionsCount > 0
                ]);
                
                ApiResponse::success([
                    'message' => 'Form deleted successfully',
                    'form_id' => $id
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error deleting form", ['form_id' => $id, 'exception' => $e]);
                ApiResponse::serverError('Failed to delete form. Please try again.');
            }
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in forms endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}
