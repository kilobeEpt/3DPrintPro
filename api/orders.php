<?php
// ========================================
// Orders API Endpoint - Full CRUD
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
            // Get all orders or single order
            if (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid order ID');
                }
                
                $order = $db->getRecordById('orders', $id);
                
                if ($order) {
                    ApiResponse::success(['order' => $order]);
                } else {
                    ApiLogger::warning("Order not found", ['id' => $id]);
                    ApiResponse::notFound('Order not found');
                }
            } else {
                // Get all orders with optional filters
                $where = [];
                if (isset($_GET['status'])) {
                    $where['status'] = $_GET['status'];
                }
                if (isset($_GET['type'])) {
                    $where['type'] = $_GET['type'];
                }
                
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : 100;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit === false || $limit < 1) $limit = 100;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $orders = $db->getRecords('orders', $where, 'created_at', $limit, $offset);
                $total = $db->getCount('orders', $where);
                
                ApiResponse::success(
                    ['orders' => $orders],
                    [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                );
            }
            break;
            
        case 'POST':
            // Create new order (from form submission)
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
            
            if (empty($data['phone']) || !is_string($data['phone'])) {
                $validationErrors['phone'] = 'Phone is required and must be a string';
            }
            
            if (!empty($validationErrors)) {
                ApiLogger::validationError('POST /api/orders.php', $validationErrors);
                ApiResponse::validationError('Validation failed', $validationErrors);
            }
            
            // Generate order number if not provided
            if (empty($data['orderNumber'])) {
                $data['order_number'] = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            } else {
                $data['order_number'] = $data['orderNumber'];
                unset($data['orderNumber']);
            }
            
            // Determine order type
            $data['type'] = isset($data['type']) ? $data['type'] : 'contact';
            if (!empty($data['calculatorData'])) {
                $data['type'] = 'order';
                $data['calculator_data'] = $data['calculatorData'];
                unset($data['calculatorData']);
            }
            
            // Set defaults
            if (!isset($data['status'])) {
                $data['status'] = 'new';
            }
            if (!isset($data['telegram_sent'])) {
                $data['telegram_sent'] = 0;
            }
            
            // Map fields
            if (!isset($data['service']) && !isset($data['subject'])) {
                $data['service'] = 'Обращение';
            }
            
            // Insert order
            try {
                $id = $db->insertRecord('orders', $data);
                ApiLogger::info("Order created successfully", [
                    'order_id' => $id,
                    'order_number' => $data['order_number'],
                    'type' => $data['type']
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('INSERT', 'orders', $e, ['data_keys' => array_keys($data)]);
                ApiResponse::serverError('Failed to create order. Please try again.');
            }
            
            // Send to Telegram
            $telegramSent = false;
            $telegramError = null;
            
            try {
                $telegramResult = sendToTelegram($data, $data['order_number'], $id, $db);
                $telegramSent = $telegramResult['success'];
                if (!$telegramSent) {
                    $telegramError = $telegramResult['error'];
                    ApiLogger::warning("Telegram notification failed", [
                        'order_id' => $id,
                        'error' => $telegramError
                    ]);
                }
            } catch (Exception $e) {
                $telegramError = $e->getMessage();
                ApiLogger::error("Telegram notification exception", [
                    'order_id' => $id,
                    'exception' => $e
                ]);
            }
            
            // Update telegram status
            try {
                $db->updateRecord('orders', $id, [
                    'telegram_sent' => $telegramSent ? 1 : 0,
                    'telegram_error' => $telegramError
                ]);
            } catch (PDOException $e) {
                ApiLogger::error("Failed to update telegram status", [
                    'order_id' => $id,
                    'exception' => $e
                ]);
            }
            
            ApiResponse::created([
                'order_id' => $id,
                'order_number' => $data['order_number'],
                'message' => 'Order submitted successfully'
            ], [
                'telegram_sent' => $telegramSent,
                'telegram_error' => $telegramError
            ]);
            break;
            
        case 'PUT':
            // Update order
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Order ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid order ID');
            }
            
            // Check if order exists
            $existingOrder = $db->getRecordById('orders', $id);
            if (!$existingOrder) {
                ApiLogger::warning("Attempt to update non-existent order", ['id' => $id]);
                ApiResponse::notFound('Order not found');
            }
            
            unset($data['id']);
            
            try {
                $db->updateRecord('orders', $id, $data);
                ApiLogger::info("Order updated successfully", [
                    'order_id' => $id,
                    'updated_fields' => array_keys($data)
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('UPDATE', 'orders', $e, ['order_id' => $id]);
                ApiResponse::serverError('Failed to update order. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Order updated successfully',
                'order_id' => $id
            ]);
            break;
            
        case 'DELETE':
            // Delete order
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Order ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid order ID');
            }
            
            // Check if order exists
            $existingOrder = $db->getRecordById('orders', $id);
            if (!$existingOrder) {
                ApiLogger::warning("Attempt to delete non-existent order", ['id' => $id]);
                ApiResponse::notFound('Order not found');
            }
            
            try {
                $db->deleteRecord('orders', $id);
                ApiLogger::info("Order deleted successfully", ['order_id' => $id]);
            } catch (PDOException $e) {
                ApiLogger::dbError('DELETE', 'orders', $e, ['order_id' => $id]);
                ApiResponse::serverError('Failed to delete order. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Order deleted successfully',
                'order_id' => $id
            ]);
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'orders', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in orders endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();

// ========================================
// Helper Functions
// ========================================

function sendToTelegram($data, $orderNumber, $orderId, $db) {
    // Get chat ID from database settings or config
    $chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';
    
    // Try to get from database
    $dbChatId = $db->getSetting('telegram_chat_id');
    if ($dbChatId && !empty($dbChatId)) {
        $chatId = $dbChatId;
    }
    
    // Check if chat ID is configured
    if (empty($chatId)) {
        return [
            'success' => false,
            'error' => 'Chat ID not configured'
        ];
    }
    
    // Check if bot token is configured
    if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN)) {
        return [
            'success' => false,
            'error' => 'Bot token not configured'
        ];
    }
    
    // Build message
    $message = "🆕 <b>НОВАЯ ЗАЯВКА #{$orderNumber}</b>\n\n";
    $message .= "🆔 <b>ID:</b> {$orderId}\n";
    $message .= "👤 <b>Имя:</b> " . htmlspecialchars($data['name']) . "\n";
    $message .= "📱 <b>Телефон:</b> " . htmlspecialchars($data['phone']) . "\n";
    
    if (!empty($data['email'])) {
        $message .= "📧 <b>Email:</b> " . htmlspecialchars($data['email']) . "\n";
    }
    
    if (!empty($data['telegram'])) {
        $message .= "💬 <b>Telegram:</b> " . htmlspecialchars($data['telegram']) . "\n";
    }
    
    $message .= "\n🛠 <b>Услуга:</b> " . htmlspecialchars($data['service'] ?? 'Обращение') . "\n";
    
    if (!empty($data['amount']) && $data['amount'] > 0) {
        $message .= "💰 <b>Сумма:</b> " . number_format($data['amount'], 0, ',', ' ') . " ₽\n";
    }
    
    // Add calculator data if present
    if (!empty($data['calculator_data'])) {
        $calc = is_array($data['calculator_data']) ? $data['calculator_data'] : json_decode($data['calculator_data'], true);
        if ($calc) {
            $message .= "\n📊 <b>Детали расчета:</b>\n";
            
            if (!empty($calc['technology'])) {
                $message .= "• Технология: " . strtoupper($calc['technology']) . "\n";
            }
            if (!empty($calc['material'])) {
                $message .= "• Материал: " . htmlspecialchars($calc['material']) . "\n";
            }
            if (!empty($calc['weight'])) {
                $message .= "• Вес: " . $calc['weight'] . " г\n";
            }
            if (!empty($calc['quantity'])) {
                $message .= "• Количество: " . $calc['quantity'] . " шт\n";
            }
            if (isset($calc['infill'])) {
                $message .= "• Заполнение: " . $calc['infill'] . "%\n";
            }
            if (!empty($calc['quality'])) {
                $message .= "• Качество: " . htmlspecialchars($calc['quality']) . "\n";
            }
            if (!empty($calc['timeEstimate'])) {
                $message .= "• Срок: " . htmlspecialchars($calc['timeEstimate']) . "\n";
            }
        }
    }
    
    // Add message/details
    if (!empty($data['message'])) {
        $message .= "\n💬 <b>Сообщение:</b>\n" . htmlspecialchars($data['message']) . "\n";
    } elseif (!empty($data['details'])) {
        $message .= "\n💬 <b>Сообщение:</b>\n" . htmlspecialchars($data['details']) . "\n";
    }
    
    $message .= "\n⏰ <b>Дата:</b> " . date('d.m.Y H:i') . "\n";
    if (defined('SITE_URL')) {
        $message .= "🌐 <b>Сайт:</b> " . SITE_URL;
    }
    
    // Send to Telegram API
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'success' => false,
            'error' => 'CURL error: ' . $curlError
        ];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['ok']) && $result['ok'] === true) {
        return ['success' => true];
    } else {
        $errorMsg = isset($result['description']) ? $result['description'] : 'Unknown error';
        return [
            'success' => false,
            'error' => 'Telegram API error: ' . $errorMsg
        ];
    }
}
