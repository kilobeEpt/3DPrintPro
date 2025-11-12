<?php
// ========================================
// Content Blocks API Endpoint
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
            // Get all content blocks or single block
            if (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid content block ID');
                }
                
                $block = $db->getRecordById('content_blocks', $id);
                
                if ($block) {
                    ApiResponse::success(['block' => $block]);
                } else {
                    ApiLogger::warning("Content block not found", ['id' => $id]);
                    ApiResponse::notFound('Content block not found');
                }
            } elseif (isset($_GET['name'])) {
                // Get by block name
                $blocks = $db->getRecords('content_blocks', ['block_name' => $_GET['name']], 'sort_order', 1);
                
                if (!empty($blocks)) {
                    ApiResponse::success(['block' => $blocks[0]]);
                } else {
                    ApiLogger::warning("Content block not found", ['name' => $_GET['name']]);
                    ApiResponse::notFound('Content block not found');
                }
            } else {
                // Get all content blocks with optional filters
                $where = [];
                if (isset($_GET['active'])) {
                    $where['active'] = $_GET['active'] === 'true' ? 1 : 0;
                }
                if (isset($_GET['page'])) {
                    $where['page'] = $_GET['page'];
                }
                
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : null;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit !== null && ($limit === false || $limit < 1)) $limit = null;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $blocks = $db->getRecords('content_blocks', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('content_blocks', $where);
                
                ApiResponse::success(
                    ['blocks' => $blocks],
                    ['total' => $total]
                );
            }
            break;
            
        case 'POST':
            // Require admin authentication and CSRF token
            requireAdminAuthWithCsrf();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('content_create');
            
            // Create new content block
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Validate required fields
            $validationErrors = [];
            
            if (empty($data['block_name']) || !is_string($data['block_name'])) {
                $validationErrors['block_name'] = 'Block name is required and must be a string';
            }
            
            if (!empty($validationErrors)) {
                ApiLogger::validationError('POST /api/content.php', $validationErrors);
                ApiResponse::validationError('Validation failed', $validationErrors);
            }
            
            try {
                $id = $db->insertRecord('content_blocks', $data);
                ApiLogger::info("Content block created successfully", [
                    'block_id' => $id,
                    'block_name' => $data['block_name']
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('INSERT', 'content_blocks', $e, ['data_keys' => array_keys($data)]);
                ApiResponse::serverError('Failed to create content block. Please try again.');
            }
            
            ApiResponse::created([
                'id' => $id,
                'message' => 'Content block created successfully'
            ]);
            break;
            
        case 'PUT':
            // Require admin authentication and CSRF token
            requireAdminAuthWithCsrf();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('content_update');
            
            // Update content block
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Content block ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid content block ID');
            }
            
            // Check if content block exists
            $existingBlock = $db->getRecordById('content_blocks', $id);
            if (!$existingBlock) {
                ApiLogger::warning("Attempt to update non-existent content block", ['id' => $id]);
                ApiResponse::notFound('Content block not found');
            }
            
            unset($data['id']);
            
            try {
                $db->updateRecord('content_blocks', $id, $data);
                ApiLogger::info("Content block updated successfully", [
                    'block_id' => $id,
                    'updated_fields' => array_keys($data)
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('UPDATE', 'content_blocks', $e, ['block_id' => $id]);
                ApiResponse::serverError('Failed to update content block. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Content block updated successfully',
                'block_id' => $id
            ]);
            break;
            
        case 'DELETE':
            // Require admin authentication and CSRF token
            requireAdminAuthWithCsrf();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('content_delete');
            
            // Delete content block
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Content block ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid content block ID');
            }
            
            // Check if content block exists
            $existingBlock = $db->getRecordById('content_blocks', $id);
            if (!$existingBlock) {
                ApiLogger::warning("Attempt to delete non-existent content block", ['id' => $id]);
                ApiResponse::notFound('Content block not found');
            }
            
            try {
                $db->deleteRecord('content_blocks', $id);
                ApiLogger::info("Content block deleted successfully", ['block_id' => $id]);
            } catch (PDOException $e) {
                ApiLogger::dbError('DELETE', 'content_blocks', $e, ['block_id' => $id]);
                ApiResponse::serverError('Failed to delete content block. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Content block deleted successfully',
                'block_id' => $id
            ]);
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'content_blocks', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in content endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();
