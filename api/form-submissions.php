<?php
// ========================================
// Form Submissions API Endpoint
// Admin: View, filter, and manage form submissions
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\FormSubmission;
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
                // Get single submission with full details
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid submission ID');
                }
                
                $submission = FormSubmission::with(['form', 'values.field', 'order'])->find($id);
                
                if (!$submission) {
                    ApiResponse::notFound('Submission not found');
                }
                
                ApiResponse::success(['submission' => [
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
                            'field_name' => $value->field_name,
                            'field_label' => $value->field ? $value->field->label : $value->field_name,
                            'field_value' => $value->field_value,
                        ];
                    })->all(),
                    'order' => $submission->order ? [
                        'id' => $submission->order->id,
                        'order_number' => $submission->order->order_number,
                        'status' => $submission->order->status,
                        'amount' => $submission->order->amount,
                    ] : null,
                ]]);
                
            } else {
                // List submissions with filters and pagination
                $query = FormSubmission::with(['form', 'order']);
                
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
                
                if (isset($_GET['date_from'])) {
                    $query->where('submitted_at', '>=', $_GET['date_from']);
                }
                
                if (isset($_GET['date_to'])) {
                    $query->where('submitted_at', '<=', $_GET['date_to'] . ' 23:59:59');
                }
                
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = $_GET['search'];
                    $query->where(function($q) use ($search) {
                        $q->where('submitted_data', 'LIKE', "%{$search}%")
                          ->orWhere('ip_address', 'LIKE', "%{$search}%");
                    });
                }
                
                // Count by status for metadata
                $statusCounts = [
                    'pending' => FormSubmission::where('status', FormSubmission::STATUS_PENDING)->count(),
                    'processed' => FormSubmission::where('status', FormSubmission::STATUS_PROCESSED)->count(),
                    'archived' => FormSubmission::where('status', FormSubmission::STATUS_ARCHIVED)->count(),
                ];
                
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
                            'submitted_data' => $submission->submitted_data,
                            'status' => $submission->status,
                            'submitted_at' => $submission->submitted_at->toIso8601String(),
                            'has_order' => $submission->order !== null,
                            'order_id' => $submission->order ? $submission->order->id : null,
                        ];
                    })
                    ->all();
                
                ApiResponse::success(
                    ['submissions' => $submissions],
                    [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total,
                        'status_counts' => $statusCounts,
                    ]
                );
            }
            break;
            
        case 'PATCH':
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('submissions_update');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Submission ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid submission ID');
            }
            
            $submission = FormSubmission::find($id);
            if (!$submission) {
                ApiResponse::notFound('Submission not found');
            }
            
            try {
                if (isset($data['status'])) {
                    $submission->update(['status' => $data['status']]);
                    
                    logAdminAction('update_submission', 'form_submission', $id, [
                        'status' => $data['status'],
                    ]);
                }
                
                ApiLogger::info("Form submission updated", [
                    'submission_id' => $submission->id,
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
            
        case 'POST':
            // Bulk operations
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('submissions_bulk');
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || !isset($data['action']) || !isset($data['ids'])) {
                ApiResponse::validationError('Invalid data format. Expected action and ids.');
            }
            
            $action = $data['action'];
            $ids = $data['ids'];
            
            if (!is_array($ids) || empty($ids)) {
                ApiResponse::validationError('IDs must be a non-empty array');
            }
            
            try {
                $updated = 0;
                
                switch ($action) {
                    case 'archive':
                        $updated = FormSubmission::whereIn('id', $ids)
                            ->update(['status' => FormSubmission::STATUS_ARCHIVED]);
                        break;
                        
                    case 'process':
                        $updated = FormSubmission::whereIn('id', $ids)
                            ->update(['status' => FormSubmission::STATUS_PROCESSED]);
                        break;
                        
                    case 'delete':
                        $updated = FormSubmission::whereIn('id', $ids)->delete();
                        break;
                        
                    default:
                        ApiResponse::validationError('Invalid action: ' . $action);
                }
                
                logAdminAction('bulk_' . $action . '_submissions', 'form_submission', null, [
                    'count' => $updated,
                    'ids' => $ids,
                ]);
                
                ApiLogger::info("Bulk submission operation completed", [
                    'action' => $action,
                    'count' => $updated,
                ]);
                
                ApiResponse::success([
                    'message' => ucfirst($action) . ' operation completed',
                    'updated_count' => $updated
                ]);
                
            } catch (Exception $e) {
                ApiLogger::error("Error in bulk submission operation", ['exception' => $e]);
                ApiResponse::serverError('Failed to complete bulk operation. Please try again.');
            }
            break;
            
        case 'DELETE':
            requireAdminAuthWithCsrf();
            $rateLimiter->apply('submissions_delete');
            
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Submission ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid submission ID');
            }
            
            $submission = FormSubmission::find($id);
            if (!$submission) {
                ApiResponse::notFound('Submission not found');
            }
            
            try {
                $submission->delete();
                
                logAdminAction('delete_submission', 'form_submission', $id, []);
                
                ApiLogger::info("Form submission deleted", [
                    'submission_id' => $id,
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
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in form-submissions endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}
