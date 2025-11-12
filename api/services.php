<?php
// ========================================
// Services API Endpoint
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/db.php';

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

try {
    switch ($method) {
        case 'GET':
            // Get all services or single service
            if (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid service ID');
                }
                
                $service = $db->getRecordById('services', $id);
                
                if ($service) {
                    ApiResponse::success(['service' => $service]);
                } else {
                    ApiLogger::warning("Service not found", ['id' => $id]);
                    ApiResponse::notFound('Service not found');
                }
            } else {
                // Get all services with optional filters
                $where = [];
                if (isset($_GET['active'])) {
                    $where['active'] = $_GET['active'] === 'true' ? 1 : 0;
                }
                if (isset($_GET['featured'])) {
                    $where['featured'] = $_GET['featured'] === 'true' ? 1 : 0;
                }
                
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : null;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit !== null && ($limit === false || $limit < 1)) $limit = null;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $services = $db->getRecords('services', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('services', $where);
                
                ApiResponse::success(
                    ['services' => $services],
                    ['total' => $total]
                );
            }
            break;
            
        case 'POST':
            // Apply rate limiting for write operations
            $rateLimiter->apply('services_create');
            
            // Create new service
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Validate required fields
            $validationErrors = [];
            
            if (empty($data['name']) || !is_string($data['name'])) {
                $validationErrors['name'] = 'Service name is required and must be a string';
            }
            
            if (!empty($validationErrors)) {
                ApiLogger::validationError('POST /api/services.php', $validationErrors);
                ApiResponse::validationError('Validation failed', $validationErrors);
            }
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = generateSlug($data['name']);
            }
            
            try {
                $id = $db->insertRecord('services', $data);
                ApiLogger::info("Service created successfully", [
                    'service_id' => $id,
                    'name' => $data['name']
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('INSERT', 'services', $e, ['data_keys' => array_keys($data)]);
                ApiResponse::serverError('Failed to create service. Please try again.');
            }
            
            ApiResponse::created([
                'id' => $id,
                'message' => 'Service created successfully'
            ]);
            break;
            
        case 'PUT':
            // Apply rate limiting for write operations
            $rateLimiter->apply('services_update');
            
            // Update service
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Service ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid service ID');
            }
            
            // Check if service exists
            $existingService = $db->getRecordById('services', $id);
            if (!$existingService) {
                ApiLogger::warning("Attempt to update non-existent service", ['id' => $id]);
                ApiResponse::notFound('Service not found');
            }
            
            unset($data['id']);
            
            try {
                $db->updateRecord('services', $id, $data);
                ApiLogger::info("Service updated successfully", [
                    'service_id' => $id,
                    'updated_fields' => array_keys($data)
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('UPDATE', 'services', $e, ['service_id' => $id]);
                ApiResponse::serverError('Failed to update service. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Service updated successfully',
                'service_id' => $id
            ]);
            break;
            
        case 'DELETE':
            // Apply rate limiting for write operations
            $rateLimiter->apply('services_delete');
            
            // Delete service
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Service ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid service ID');
            }
            
            // Check if service exists
            $existingService = $db->getRecordById('services', $id);
            if (!$existingService) {
                ApiLogger::warning("Attempt to delete non-existent service", ['id' => $id]);
                ApiResponse::notFound('Service not found');
            }
            
            try {
                $db->deleteRecord('services', $id);
                ApiLogger::info("Service deleted successfully", ['service_id' => $id]);
            } catch (PDOException $e) {
                ApiLogger::dbError('DELETE', 'services', $e, ['service_id' => $id]);
                ApiResponse::serverError('Failed to delete service. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Service deleted successfully',
                'service_id' => $id
            ]);
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'services', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in services endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();

function generateSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}
