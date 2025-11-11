<?php
// ========================================
// FAQ API Endpoint
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
            // Get all FAQ items or single item
            if (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid FAQ item ID');
                }
                
                $item = $db->getRecordById('faq', $id);
                
                if ($item) {
                    ApiResponse::success(['item' => $item]);
                } else {
                    ApiLogger::warning("FAQ item not found", ['id' => $id]);
                    ApiResponse::notFound('FAQ item not found');
                }
            } else {
                // Get all FAQ items with optional filters
                $where = [];
                if (isset($_GET['active'])) {
                    $where['active'] = $_GET['active'] === 'true' ? 1 : 0;
                }
                
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : null;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit !== null && ($limit === false || $limit < 1)) $limit = null;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $items = $db->getRecords('faq', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('faq', $where);
                
                ApiResponse::success(
                    ['items' => $items],
                    ['total' => $total]
                );
            }
            break;
            
        case 'POST':
            // Create new FAQ item
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Validate required fields
            $validationErrors = [];
            
            if (empty($data['question']) || !is_string($data['question'])) {
                $validationErrors['question'] = 'Question is required and must be a string';
            }
            
            if (empty($data['answer']) || !is_string($data['answer'])) {
                $validationErrors['answer'] = 'Answer is required and must be a string';
            }
            
            if (!empty($validationErrors)) {
                ApiLogger::validationError('POST /api/faq.php', $validationErrors);
                ApiResponse::validationError('Validation failed', $validationErrors);
            }
            
            try {
                $id = $db->insertRecord('faq', $data);
                ApiLogger::info("FAQ item created successfully", [
                    'faq_id' => $id,
                    'question' => substr($data['question'], 0, 50)
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('INSERT', 'faq', $e, ['data_keys' => array_keys($data)]);
                ApiResponse::serverError('Failed to create FAQ item. Please try again.');
            }
            
            ApiResponse::created([
                'id' => $id,
                'message' => 'FAQ item created successfully'
            ]);
            break;
            
        case 'PUT':
            // Update FAQ item
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('FAQ item ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid FAQ item ID');
            }
            
            // Check if FAQ item exists
            $existingItem = $db->getRecordById('faq', $id);
            if (!$existingItem) {
                ApiLogger::warning("Attempt to update non-existent FAQ item", ['id' => $id]);
                ApiResponse::notFound('FAQ item not found');
            }
            
            unset($data['id']);
            
            try {
                $db->updateRecord('faq', $id, $data);
                ApiLogger::info("FAQ item updated successfully", [
                    'faq_id' => $id,
                    'updated_fields' => array_keys($data)
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('UPDATE', 'faq', $e, ['faq_id' => $id]);
                ApiResponse::serverError('Failed to update FAQ item. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'FAQ item updated successfully',
                'faq_id' => $id
            ]);
            break;
            
        case 'DELETE':
            // Delete FAQ item
            if (empty($_GET['id'])) {
                ApiResponse::validationError('FAQ item ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid FAQ item ID');
            }
            
            // Check if FAQ item exists
            $existingItem = $db->getRecordById('faq', $id);
            if (!$existingItem) {
                ApiLogger::warning("Attempt to delete non-existent FAQ item", ['id' => $id]);
                ApiResponse::notFound('FAQ item not found');
            }
            
            try {
                $db->deleteRecord('faq', $id);
                ApiLogger::info("FAQ item deleted successfully", ['faq_id' => $id]);
            } catch (PDOException $e) {
                ApiLogger::dbError('DELETE', 'faq', $e, ['faq_id' => $id]);
                ApiResponse::serverError('Failed to delete FAQ item. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'FAQ item deleted successfully',
                'faq_id' => $id
            ]);
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'faq', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in FAQ endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();
