<?php
// ========================================
// Testimonials API Endpoint
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/db.php';

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

try {
    switch ($method) {
        case 'GET':
            // Get all testimonials or single testimonial
            if (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid testimonial ID');
                }
                
                $testimonial = $db->getRecordById('testimonials', $id);
                
                if ($testimonial) {
                    ApiResponse::success(['testimonial' => $testimonial]);
                } else {
                    ApiLogger::warning("Testimonial not found", ['id' => $id]);
                    ApiResponse::notFound('Testimonial not found');
                }
            } else {
                // Get all testimonials with optional filters
                $where = [];
                if (isset($_GET['active'])) {
                    $where['active'] = $_GET['active'] === 'true' ? 1 : 0;
                }
                if (isset($_GET['approved'])) {
                    $where['approved'] = $_GET['approved'] === 'true' ? 1 : 0;
                }
                
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : null;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit !== null && ($limit === false || $limit < 1)) $limit = null;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $testimonials = $db->getRecords('testimonials', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('testimonials', $where);
                
                ApiResponse::success(
                    ['testimonials' => $testimonials],
                    ['total' => $total]
                );
            }
            break;
            
        case 'POST':
            // Require admin authentication and CSRF token
            requireAdminAuthWithCsrf();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('testimonials_create');
            
            // Create new testimonial
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Validate required fields
            $validationErrors = [];
            
            if (empty($data['name']) || !is_string($data['name'])) {
                $validationErrors['name'] = 'Name is required and must be a string';
            }
            
            if (empty($data['text']) || !is_string($data['text'])) {
                $validationErrors['text'] = 'Text is required and must be a string';
            }
            
            if (!empty($validationErrors)) {
                ApiLogger::validationError('POST /api/testimonials.php', $validationErrors);
                ApiResponse::validationError('Validation failed', $validationErrors);
            }
            
            try {
                $id = $db->insertRecord('testimonials', $data);
                ApiLogger::info("Testimonial created successfully", [
                    'testimonial_id' => $id,
                    'name' => $data['name']
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('INSERT', 'testimonials', $e, ['data_keys' => array_keys($data)]);
                ApiResponse::serverError('Failed to create testimonial. Please try again.');
            }
            
            ApiResponse::created([
                'id' => $id,
                'message' => 'Testimonial created successfully'
            ]);
            break;
            
        case 'PUT':
            // Require admin authentication and CSRF token
            requireAdminAuthWithCsrf();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('testimonials_update');
            
            // Update testimonial
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Testimonial ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid testimonial ID');
            }
            
            // Check if testimonial exists
            $existingTestimonial = $db->getRecordById('testimonials', $id);
            if (!$existingTestimonial) {
                ApiLogger::warning("Attempt to update non-existent testimonial", ['id' => $id]);
                ApiResponse::notFound('Testimonial not found');
            }
            
            unset($data['id']);
            
            try {
                $db->updateRecord('testimonials', $id, $data);
                ApiLogger::info("Testimonial updated successfully", [
                    'testimonial_id' => $id,
                    'updated_fields' => array_keys($data)
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('UPDATE', 'testimonials', $e, ['testimonial_id' => $id]);
                ApiResponse::serverError('Failed to update testimonial. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Testimonial updated successfully',
                'testimonial_id' => $id
            ]);
            break;
            
        case 'DELETE':
            // Require admin authentication and CSRF token
            requireAdminAuthWithCsrf();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('testimonials_delete');
            
            // Delete testimonial
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Testimonial ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid testimonial ID');
            }
            
            // Check if testimonial exists
            $existingTestimonial = $db->getRecordById('testimonials', $id);
            if (!$existingTestimonial) {
                ApiLogger::warning("Attempt to delete non-existent testimonial", ['id' => $id]);
                ApiResponse::notFound('Testimonial not found');
            }
            
            try {
                $db->deleteRecord('testimonials', $id);
                ApiLogger::info("Testimonial deleted successfully", ['testimonial_id' => $id]);
            } catch (PDOException $e) {
                ApiLogger::dbError('DELETE', 'testimonials', $e, ['testimonial_id' => $id]);
                ApiResponse::serverError('Failed to delete testimonial. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Testimonial deleted successfully',
                'testimonial_id' => $id
            ]);
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'testimonials', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in testimonials endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();
