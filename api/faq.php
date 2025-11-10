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

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all FAQ items or single item
            if (isset($_GET['id'])) {
                $item = $db->getRecordById('faq', $_GET['id']);
                
                if ($item) {
                    echo json_encode([
                        'success' => true,
                        'item' => $item
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'FAQ item not found'
                    ]);
                }
            } else {
                // Get all FAQ items with optional filters
                $where = [];
                if (isset($_GET['active'])) {
                    $where['active'] = $_GET['active'] === 'true' ? 1 : 0;
                }
                
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $items = $db->getRecords('faq', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('faq', $where);
                
                echo json_encode([
                    'success' => true,
                    'items' => $items,
                    'total' => $total
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // Create new FAQ item
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['question']) || empty($data['answer'])) {
                throw new Exception('Question and answer are required');
            }
            
            $id = $db->insertRecord('faq', $data);
            
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'FAQ item created successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'PUT':
            // Update FAQ item
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['id'])) {
                throw new Exception('FAQ item ID is required');
            }
            
            $id = $data['id'];
            unset($data['id']);
            
            $db->updateRecord('faq', $id, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'FAQ item updated successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'DELETE':
            // Delete FAQ item
            if (empty($_GET['id'])) {
                throw new Exception('FAQ item ID is required');
            }
            
            $db->deleteRecord('faq', $_GET['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'FAQ item deleted successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$db->close();
