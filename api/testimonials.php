<?php
// ========================================
// Testimonials API Endpoint
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
            // Get all testimonials or single testimonial
            if (isset($_GET['id'])) {
                $testimonial = $db->getRecordById('testimonials', $_GET['id']);
                
                if ($testimonial) {
                    echo json_encode([
                        'success' => true,
                        'testimonial' => $testimonial
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Testimonial not found'
                    ]);
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
                
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $testimonials = $db->getRecords('testimonials', $where, 'sort_order', $limit, $offset);
                $total = $db->getCount('testimonials', $where);
                
                echo json_encode([
                    'success' => true,
                    'testimonials' => $testimonials,
                    'total' => $total
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // Create new testimonial
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['name']) || empty($data['text'])) {
                throw new Exception('Name and text are required');
            }
            
            $id = $db->insertRecord('testimonials', $data);
            
            echo json_encode([
                'success' => true,
                'id' => $id,
                'message' => 'Testimonial created successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'PUT':
            // Update testimonial
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['id'])) {
                throw new Exception('Testimonial ID is required');
            }
            
            $id = $data['id'];
            unset($data['id']);
            
            $db->updateRecord('testimonials', $id, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Testimonial updated successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'DELETE':
            // Delete testimonial
            if (empty($_GET['id'])) {
                throw new Exception('Testimonial ID is required');
            }
            
            $db->deleteRecord('testimonials', $_GET['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Testimonial deleted successfully'
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
