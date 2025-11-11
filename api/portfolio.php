<?php
// ========================================
// Portfolio API Endpoint
// ========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all portfolio items or single item
            if (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid portfolio item ID');
                }
                
                $item = $db->getRecordById('portfolio', $id);
                
                if ($item) {
                    ApiResponse::success(['item' => $item]);
                } else {
                    ApiLogger::warning("Portfolio item not found", ['id' => $id]);
                    ApiResponse::notFound('Portfolio item not found');
                }
            } else {
                // Get all portfolio items with optional filters
                $where = [];
                if (isset($_GET['active'])) {
                    $where['active'] = $_GET['active'] === 'true' ? 1 : 0;
                }
                if (isset($_GET['category'])) {
                    $where['category'] = $_GET['category'];
                }
                
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : null;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit !== null && ($limit === false || $limit < 1)) $limit = null;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $items = $db->getRecords('portfolio', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('portfolio', $where);
                
                ApiResponse::success(
                    ['items' => $items],
                    ['total' => $total]
                );
            }
            break;
            
        case 'POST':
            // Create new portfolio item
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Validate required fields
            $validationErrors = [];
            
            if (empty($data['title']) || !is_string($data['title'])) {
                $validationErrors['title'] = 'Portfolio title is required and must be a string';
            }
            
            if (!empty($validationErrors)) {
                ApiLogger::validationError('POST /api/portfolio.php', $validationErrors);
                ApiResponse::validationError('Validation failed', $validationErrors);
            }
            
            try {
                $id = $db->insertRecord('portfolio', $data);
                ApiLogger::info("Portfolio item created successfully", [
                    'item_id' => $id,
                    'title' => $data['title']
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('INSERT', 'portfolio', $e, ['data_keys' => array_keys($data)]);
                ApiResponse::serverError('Failed to create portfolio item. Please try again.');
            }
            
            ApiResponse::created([
                'id' => $id,
                'message' => 'Portfolio item created successfully'
            ]);
            break;
            
        case 'PUT':
            // Update portfolio item
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Portfolio item ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid portfolio item ID');
            }
            
            // Check if item exists
            $existingItem = $db->getRecordById('portfolio', $id);
            if (!$existingItem) {
                ApiLogger::warning("Attempt to update non-existent portfolio item", ['id' => $id]);
                ApiResponse::notFound('Portfolio item not found');
            }
            
            unset($data['id']);
            
            try {
                $db->updateRecord('portfolio', $id, $data);
                ApiLogger::info("Portfolio item updated successfully", [
                    'item_id' => $id,
                    'updated_fields' => array_keys($data)
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('UPDATE', 'portfolio', $e, ['item_id' => $id]);
                ApiResponse::serverError('Failed to update portfolio item. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Portfolio item updated successfully',
                'item_id' => $id
            ]);
            break;
            
        case 'DELETE':
            // Delete portfolio item
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Portfolio item ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid portfolio item ID');
            }
            
            // Check if item exists
            $existingItem = $db->getRecordById('portfolio', $id);
            if (!$existingItem) {
                ApiLogger::warning("Attempt to delete non-existent portfolio item", ['id' => $id]);
                ApiResponse::notFound('Portfolio item not found');
            }
            
            try {
                $db->deleteRecord('portfolio', $id);
                ApiLogger::info("Portfolio item deleted successfully", ['item_id' => $id]);
            } catch (PDOException $e) {
                ApiLogger::dbError('DELETE', 'portfolio', $e, ['item_id' => $id]);
                ApiResponse::serverError('Failed to delete portfolio item. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Portfolio item deleted successfully',
                'item_id' => $id
            ]);
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'portfolio', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in portfolio endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();
