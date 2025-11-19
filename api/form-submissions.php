<?php
// ========================================
// Form Submissions API Endpoint
// Public: Submit form data
// Admin: Review and manage submissions
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/helpers/form_service.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\FormSubmission;
use App\Models\Order;

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();
$db = new Database();

try {
    switch ($method) {
        case 'GET':
            // Admin: List submissions or get single submission
            requireAdminAuth();
            
            if (isset($_GET['id'])) {
                // Get single submission by ID
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid submission ID');
                }
                
                $submission = FormSubmission::with(['form', 'values.field', 'order'])->find($id);
                
                if (!$submission) {
                    ApiLogger::warning("Submission not found", ['id' => $id]);
                    ApiResponse::notFound('Submission not found');
                }
                
                // Format submission data
                $submissionData = [
                    'id' => $submission->id,
                    'form_id' => $submission->form_id,
                    'form_slug' => $submission->form_slug,
                    'form_name' => $submission->form ? $submission->form->name : null,
                    'submitted_data' => $submission->submitted_data,
                    'status' => $submission->status,
                    'ip_address' => $submission->ip_address,
                    'user_agent' => $submission->user_agent,
                    'submitted_at' => $submission->submitted_at->toIso8601String(),
                    'created_at' => $submission->created_at->toIso8601String(),
                    'updated_at' => $submission->updated_at->toIso8601String(),
                    'values' => $submission->values->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'field_name' => $value->field_name,
                            'field_label' => $value->field ? $value->field->label : $value->field_name,
                            'field_value' => $value->field_value,
                        ];
                    })->all(),
                    'order' => $submission->order ? [
                        'id' => $submission->order->id,
                        'order_number' => $submission->order->order_number,
                        'status' => $submission->order->status,
                    ] : null,
                ];
                
                ApiResponse::success(['submission' => $submissionData]);
                
            } else {
                // List submissions with filters and pagination
                $query = FormSubmission::with('form', 'order');
                
                // Apply filters
                if (isset($_GET['form_id'])) {
                    $formId = filter_var($_GET['form_id'], FILTER_VALIDATE_INT);
                    if ($formId) {
                        $query->where('form_id', $formId);
                    }
                }
                
                if (isset($_GET['form_slug'])) {
                    $query->where('form_slug', $_GET['form_slug']);
                }
                
                if (isset($_GET['status'])) {
                    $query->where('status', $_GET['status']);
                }
                
                if (isset($_GET['from_date'])) {
                    $fromDate = $_GET['from_date'];
                    if (strtotime($fromDate)) {
                        $query->where('submitted_at', '>=', $fromDate);
                    }
                }
                
                if (isset($_GET['to_date'])) {
                    $toDate = $_GET['to_date'];
                    if (strtotime($toDate)) {
                        $query->where('submitted_at', '<=', $toDate);
                    }
                }
                
                // Pagination
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : 50;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit === false || $limit < 1) $limit = 50;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $total = $query->count();
                
                $submissions = $query->orderBy('submitted_at', 'desc')
                                     ->skip($offset)
                                     ->take($limit)
                                     ->get()
                                     ->map(function ($submission) {
                                         return [
                                             'id' => $submission->id,
                                             'form_id' => $submission->form_id,
                                             'form_slug' => $submission->form_slug,
                                             'form_name' => $submission->form ? $submission->form->name : null,
                                             'status' => $submission->status,
                                             'submitted_at' => $submission->submitted_at->toIso8601String(),
                                             'order_id' => $submission->order ? $submission->order->id : null,
                                             'order_number' => $submission->order ? $submission->order->order_number : null,
                                             'summary' => self::generateSubmissionSummary($submission->submitted_data),
                                         ];
                                     })
                                     ->all();
                
                ApiResponse::success(
                    ['submissions' => $submissions],
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
            // Public: Submit form data
            $rateLimiter->apply('form_submission');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Validate form slug
            if (empty($data['form_slug']) || !is_string($data['form_slug'])) {
                ApiResponse::validationError('Form slug is required', ['form_slug' => 'Form slug is required']);
            }
            
            $formSlug = $data['form_slug'];
            $submittedData = $data['data'] ?? [];
            
            // Load form definition
            $formData = FormService::loadForm($formSlug, true);
            
            if (!$formData) {
                ApiLogger::warning("Form not found for submission", ['slug' => $formSlug]);
                ApiResponse::notFound('Form not found');
            }
            
            // Validate submission
            $validation = FormService::validateSubmission($formData, $submittedData);
            
            if (!$validation['valid']) {
                ApiLogger::validationError('POST /api/form-submissions.php', $validation['errors']);
                ApiResponse::unprocessableEntity('Validation failed', $validation['errors']);
            }
            
            // Process submission
            $metadata = [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ];
            
            $result = FormService::processSubmission($formData, $submittedData, $metadata, $db);
            
            if (!$result['success']) {
                ApiLogger::error("Failed to process form submission", [
                    'form_slug' => $formSlug,
                    'error' => $result['error']
                ]);
                ApiResponse::serverError('Failed to submit form. Please try again.');
            }
            
            ApiLogger::info("Form submission processed successfully", [
                'form_slug' => $formSlug,
                'submission_id' => $result['submission_id'],
                'order_id' => $result['order_id'],
            ]);
            
            // Build response
            $response = [
                'submission_id' => $result['submission_id'],
                'message' => $formData['success_message'] ?? 'Form submitted successfully',
            ];
            
            if ($result['order_id']) {
                $order = Order::find($result['order_id']);
                if ($order) {
                    $response['order_number'] = $order->order_number;
                    $response['order_id'] = $order->id;
                }
            }
            
            $meta = [];
            if ($formData['redirect_url']) {
                $meta['redirect_url'] = $formData['redirect_url'];
            }
            
            ApiResponse::created($response, $meta);
            break;
            
        case 'PUT':
            // Admin: Update submission status
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('form_submission_update');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Submission ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid submission ID');
            }
            
            // Check if submission exists
            $submission = FormSubmission::find($id);
            if (!$submission) {
                ApiLogger::warning("Attempt to update non-existent submission", ['id' => $id]);
                ApiResponse::notFound('Submission not found');
            }
            
            // Update status
            try {
                $updateData = [];
                
                if (isset($data['status'])) {
                    $allowedStatuses = [
                        FormSubmission::STATUS_PENDING,
                        FormSubmission::STATUS_PROCESSED,
                        FormSubmission::STATUS_ARCHIVED,
                    ];
                    
                    if (!in_array($data['status'], $allowedStatuses)) {
                        ApiResponse::validationError('Invalid status', ['status' => 'Status must be one of: ' . implode(', ', $allowedStatuses)]);
                    }
                    
                    $updateData['status'] = $data['status'];
                }
                
                if (empty($updateData)) {
                    ApiResponse::validationError('No valid fields to update');
                }
                
                $submission->update($updateData);
                
                ApiLogger::info("Submission updated successfully", [
                    'submission_id' => $submission->id,
                    'updated_fields' => array_keys($updateData),
                ]);
                
                ApiResponse::success([
                    'message' => 'Submission updated successfully',
                    'submission_id' => $submission->id
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error updating submission", ['submission_id' => $id, 'exception' => $e]);
                ApiResponse::serverError('Failed to update submission. Please try again.');
            }
            break;
            
        case 'DELETE':
            // Admin: Delete submission
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('form_submission_delete');
            
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Submission ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid submission ID');
            }
            
            // Check if submission exists
            $submission = FormSubmission::find($id);
            if (!$submission) {
                ApiLogger::warning("Attempt to delete non-existent submission", ['id' => $id]);
                ApiResponse::notFound('Submission not found');
            }
            
            try {
                $formSlug = $submission->form_slug;
                $submission->delete();
                
                ApiLogger::info("Submission deleted successfully", [
                    'submission_id' => $id,
                    'form_slug' => $formSlug,
                ]);
                
                ApiResponse::success([
                    'message' => 'Submission deleted successfully',
                    'submission_id' => $id
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error deleting submission", ['submission_id' => $id, 'exception' => $e]);
                ApiResponse::serverError('Failed to delete submission. Please try again.');
            }
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in form-submissions endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

// Helper function to generate submission summary
function generateSubmissionSummary($submittedData) {
    if (empty($submittedData)) {
        return '';
    }
    
    $summary = [];
    
    // Extract key fields for summary
    $keyFields = ['name', 'email', 'phone', 'subject', 'service'];
    
    foreach ($keyFields as $field) {
        if (isset($submittedData[$field]) && !empty($submittedData[$field])) {
            $summary[] = $submittedData[$field];
        }
    }
    
    return implode(' • ', array_slice($summary, 0, 3));
}

$db->close();
