<?php
// ========================================
// Services API Endpoint
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
            // Get all services or single service
            if (isset($_GET['id'])) {
                $service = $db->getRecordById('services', $_GET['id']);
                
                if ($service) {
                    echo json_encode([
                        'success' => true,
                        'service' => $service
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Service not found'
                    ]);
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
                
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $services = $db->getRecords('services', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('services', $where);
                
                echo json_encode([
                    'success' => true,
                    'services' => $services,
                    'total' => $total
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // Create new service
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['name'])) {
                throw new Exception('Service name is required');
            }
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            }
            
            $id = $db->insertRecord('services', $data);
            
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'Service created successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'PUT':
            // Update service
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['id'])) {
                throw new Exception('Service ID is required');
            }
            
            $id = $data['id'];
            unset($data['id']);
            
            $db->updateRecord('services', $id, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Service updated successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'DELETE':
            // Delete service
            if (empty($_GET['id'])) {
                throw new Exception('Service ID is required');
            }
            
            $db->deleteRecord('services', $_GET['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Service deleted successfully'
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

function generateSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}
