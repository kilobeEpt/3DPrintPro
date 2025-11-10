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

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get all orders or single order
            if (isset($_GET['id'])) {
                $order = $db->getRecordById('orders', $_GET['id']);
                
                if ($order) {
                    echo json_encode([
                        'success' => true,
                        'order' => $order
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Order not found'
                    ]);
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
                
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
                $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $orders = $db->getRecords('orders', $where, 'created_at', $limit, $offset);
                $total = $db->getCount('orders', $where);
                
                echo json_encode([
                    'success' => true,
                    'orders' => $orders,
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'POST':
            // Create new order (from form submission)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                throw new Exception('Invalid JSON data');
            }
            
            // Validate required fields
            if (empty($data['name'])) {
                throw new Exception('Name is required');
            }
            
            if (empty($data['phone'])) {
                throw new Exception('Phone is required');
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
            $id = $db->insertRecord('orders', $data);
            
            // Send to Telegram
            $telegramSent = false;
            $telegramError = null;
            
            try {
                $telegramResult = sendToTelegram($data, $data['order_number'], $id, $db);
                $telegramSent = $telegramResult['success'];
                if (!$telegramSent) {
                    $telegramError = $telegramResult['error'];
                }
            } catch (Exception $e) {
                $telegramError = $e->getMessage();
            }
            
            // Update telegram status
            $db->updateRecord('orders', $id, [
                'telegram_sent' => $telegramSent ? 1 : 0,
                'telegram_error' => $telegramError
            ]);
            
            echo json_encode([
                'success' => true,
                'order_id' => $id,
                'order_number' => $data['order_number'],
                'telegram_sent' => $telegramSent,
                'telegram_error' => $telegramError,
                'message' => 'Order submitted successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'PUT':
            // Update order
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data || empty($data['id'])) {
                throw new Exception('Order ID is required');
            }
            
            $id = $data['id'];
            unset($data['id']);
            
            $db->updateRecord('orders', $id, $data);
            
            echo json_encode([
                'success' => true,
                'message' => 'Order updated successfully'
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'DELETE':
            // Delete order
            if (empty($_GET['id'])) {
                throw new Exception('Order ID is required');
            }
            
            $db->deleteRecord('orders', $_GET['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Order deleted successfully'
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
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
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
