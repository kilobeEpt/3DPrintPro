<?php
// ========================================
// Get Orders API
// ========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use GET.'
    ]);
    exit;
}

// Load configuration
require_once __DIR__ . '/config.php';

try {
    // Connect to database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Get query parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Build query
    $sql = "SELECT * FROM orders";
    $params = [];
    
    if ($status) {
        $sql .= " WHERE status = :status";
        $params[':status'] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    // Decode calculator_data JSON for each order
    foreach ($orders as &$order) {
        if (!empty($order['calculator_data'])) {
            $order['calculator_data'] = json_decode($order['calculator_data'], true);
        }
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM orders";
    if ($status) {
        $countSql .= " WHERE status = :status";
    }
    $countStmt = $pdo->prepare($countSql);
    if ($status) {
        $countStmt->bindValue(':status', $status);
    }
    $countStmt->execute();
    $countResult = $countStmt->fetch();
    $total = $countResult['total'];
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
