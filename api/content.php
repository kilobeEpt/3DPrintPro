<?php
// ========================================
// Content Blocks API Endpoint
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
            // Get all content blocks or single block
            if (isset($_GET['id'])) {
                $block = $db->getRecordById('content_blocks', $_GET['id']);
                
                if ($block) {
                    echo json_encode([
                        'success' => true,
                        'block' => $block
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Content block not found'
                    ]);
                }
            } elseif (isset($_GET['name'])) {
                // Get by block name
                $blocks = $db->getRecords('content_blocks', ['block_name' => $_GET['name']], 'sort_order', 1);
                
                if (!empty($blocks)) {
                    echo json_encode([
                        'success' => true,
                        'block' => $blocks[0]
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Content block not found'
                    ]);
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
                
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $blocks = $db->getRecords('content_blocks', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('content_blocks', $where);
                
                echo json_encode([
                    'success' => true,
                    'blocks' => $blocks,
                    'total' => $total
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // Create new content block
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['block_name'])) {
                throw new Exception('Block name is required');
            }
            
            $id = $db->insertRecord('content_blocks', $data);
            
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'Content block created successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'PUT':
            // Update content block
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['id'])) {
                throw new Exception('Content block ID is required');
            }
            
            $id = $data['id'];
            unset($data['id']);
            
            $db->updateRecord('content_blocks', $id, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Content block updated successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'DELETE':
            // Delete content block
            if (empty($_GET['id'])) {
                throw new Exception('Content block ID is required');
            }
            
            $db->deleteRecord('content_blocks', $_GET['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Content block deleted successfully'
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
