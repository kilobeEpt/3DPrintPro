<?php
// ========================================
// Form Submission API
// ========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Load configuration
require_once __DIR__ . '/config.php';

try {
    // Get POST data
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
    
    // Connect to database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Generate order number
    $orderNumber = isset($data['orderNumber']) ? $data['orderNumber'] : generateOrderNumber();
    
    // Prepare calculator data as JSON
    $calculatorData = null;
    if (isset($data['calculatorData']) && is_array($data['calculatorData'])) {
        $calculatorData = json_encode($data['calculatorData'], JSON_UNESCAPED_UNICODE);
    }
    
    // Determine order type
    $type = isset($data['type']) ? $data['type'] : 'contact';
    if (!empty($data['calculatorData'])) {
        $type = 'order';
    }
    
    // Insert order into database
    $sql = "INSERT INTO orders (
        order_number, type, name, email, phone, telegram,
        service, subject, message, amount, calculator_data,
        status, telegram_sent
    ) VALUES (
        :order_number, :type, :name, :email, :phone, :telegram,
        :service, :subject, :message, :amount, :calculator_data,
        :status, :telegram_sent
    )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':order_number' => $orderNumber,
        ':type' => $type,
        ':name' => $data['name'],
        ':email' => $data['email'] ?? null,
        ':phone' => $data['phone'],
        ':telegram' => $data['telegram'] ?? null,
        ':service' => $data['service'] ?? 'Обращение',
        ':subject' => $data['subject'] ?? null,
        ':message' => $data['message'] ?? $data['details'] ?? null,
        ':amount' => $data['amount'] ?? 0,
        ':calculator_data' => $calculatorData,
        ':status' => 'new',
        ':telegram_sent' => 0
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // Send to Telegram
    $telegramSent = false;
    $telegramError = null;
    
    try {
        $telegramResult = sendToTelegram($data, $orderNumber, $orderId, $pdo);
        $telegramSent = $telegramResult['success'];
        if (!$telegramSent) {
            $telegramError = $telegramResult['error'];
        }
    } catch (Exception $e) {
        $telegramError = $e->getMessage();
    }
    
    // Update telegram_sent status
    $updateSql = "UPDATE orders SET telegram_sent = :sent, telegram_error = :error WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':sent' => $telegramSent ? 1 : 0,
        ':error' => $telegramError,
        ':id' => $orderId
    ]);
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'telegram_sent' => $telegramSent,
        'telegram_error' => $telegramError,
        'message' => 'Form submitted successfully'
    ], JSON_UNESCAPED_UNICODE);
    
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

// ========================================
// Helper Functions
// ========================================

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function sendToTelegram($data, $orderNumber, $orderId, $pdo) {
    // Get chat ID from database settings or config
    $chatId = TELEGRAM_CHAT_ID;
    
    // Try to get from database
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'telegram_chat_id' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    if ($result && !empty($result['setting_value'])) {
        $chatId = $result['setting_value'];
    }
    
    // Check if chat ID is configured
    if (empty($chatId)) {
        return [
            'success' => false,
            'error' => 'Chat ID not configured'
        ];
    }
    
    // Check if bot token is configured
    if (empty(TELEGRAM_BOT_TOKEN)) {
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
    if (!empty($data['calculatorData'])) {
        $calc = $data['calculatorData'];
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
    
    // Add message/details
    if (!empty($data['message'])) {
        $message .= "\n💬 <b>Сообщение:</b>\n" . htmlspecialchars($data['message']) . "\n";
    } elseif (!empty($data['details'])) {
        $message .= "\n💬 <b>Сообщение:</b>\n" . htmlspecialchars($data['details']) . "\n";
    }
    
    $message .= "\n⏰ <b>Дата:</b> " . date('d.m.Y H:i') . "\n";
    $message .= "🌐 <b>Сайт:</b> " . SITE_URL;
    
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
