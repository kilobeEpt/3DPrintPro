<?php
/**
 * Order Form Handler with Telegram Notifications
 * 
 * Receives POST requests from order form, validates data,
 * applies rate limiting and honeypot, and broadcasts to Telegram.
 */

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// CORS headers (if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Метод не разрешен. Используйте POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Load dependencies
require_once __DIR__ . '/php/TelegramBot.php';

// Initialize logger
class OrderLogger {
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/storage/logs/orders.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND);
    }
}

// Initialize rate limiter
class OrderRateLimiter {
    private $cacheDir;
    private $maxRequests = 5; // Max 5 orders per hour
    private $timeWindow = 3600; // 1 hour in seconds
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/storage/cache/order_rate_limit';
        $this->ensureCacheDirectory();
    }
    
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function checkLimit($ip) {
        $filename = $this->cacheDir . '/' . md5($ip) . '.json';
        
        // Load existing data
        if (file_exists($filename)) {
            $data = json_decode(file_get_contents($filename), true);
            
            // Check if time window has expired
            if (time() < $data['expires_at']) {
                if ($data['count'] >= $this->maxRequests) {
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'reset_at' => date('Y-m-d H:i:s', $data['expires_at'])
                    ];
                }
                
                // Increment counter
                $data['count']++;
                file_put_contents($filename, json_encode($data));
                
                return [
                    'allowed' => true,
                    'remaining' => $this->maxRequests - $data['count'],
                    'reset_at' => date('Y-m-d H:i:s', $data['expires_at'])
                ];
            }
        }
        
        // Create new rate limit record
        $data = [
            'ip' => $ip,
            'count' => 1,
            'expires_at' => time() + $this->timeWindow
        ];
        file_put_contents($filename, json_encode($data));
        
        return [
            'allowed' => true,
            'remaining' => $this->maxRequests - 1,
            'reset_at' => date('Y-m-d H:i:s', $data['expires_at'])
        ];
    }
}

// Initialize file uploader
class OrderFileUploader {
    private $uploadDir;
    private $allowedExtensions = ['stl', 'obj', 'gcode', 'step', 'stp', '3mf', 'amf', 'ply'];
    private $maxFileSize = 52428800; // 50 MB
    
    public function __construct() {
        $this->uploadDir = __DIR__ . '/storage/uploads/orders';
        $this->ensureUploadDirectory();
    }
    
    private function ensureUploadDirectory() {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function uploadFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка загрузки файла');
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('Размер файла превышает 50 МБ');
        }
        
        // Check extension
        $filename = $file['name'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('Недопустимый тип файла. Разрешены: ' . implode(', ', $this->allowedExtensions));
        }
        
        // Generate unique filename
        $uniqueFilename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $this->uploadDir . '/' . $uniqueFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Не удалось сохранить файл');
        }
        
        return [
            'original_name' => $filename,
            'saved_name' => $uniqueFilename,
            'path' => $destination,
            'size' => $file['size'],
            'extension' => $extension
        ];
    }
}

// Initialize queue handler
class OrderQueue {
    private $queueFile;
    
    public function __construct() {
        $this->queueFile = __DIR__ . '/storage/cache/order_queue.json';
        $this->ensureCacheDirectory();
    }
    
    private function ensureCacheDirectory() {
        $dir = dirname($this->queueFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if (!file_exists($this->queueFile)) {
            file_put_contents($this->queueFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }
    
    public function addToQueue($orderData) {
        $queue = $this->loadQueue();
        
        $queue[] = [
            'id' => uniqid('order_', true),
            'data' => $orderData,
            'queued_at' => date('Y-m-d H:i:s'),
            'attempts' => 0
        ];
        
        $this->saveQueue($queue);
    }
    
    private function loadQueue() {
        if (!file_exists($this->queueFile)) {
            return [];
        }
        
        $content = file_get_contents($this->queueFile);
        return json_decode($content, true) ?: [];
    }
    
    private function saveQueue($queue) {
        file_put_contents($this->queueFile, json_encode($queue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Initialize components
$logger = new OrderLogger();
$rateLimiter = new OrderRateLimiter();
$fileUploader = new OrderFileUploader();
$queue = new OrderQueue();
$telegramBot = new TelegramBot();

try {
    // Get client IP
    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Parse input data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON input
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
        }
    } else {
        // Form data or multipart
        $data = $_POST;
    }
    
    // Honeypot check (silent fail)
    if (!empty($data['website']) || !empty($data['url']) || !empty($data['honeypot'])) {
        $logger->log('WARNING', 'Honeypot triggered', ['ip' => $clientIp]);
        
        // Return success to bot (don't reveal honeypot)
        echo json_encode([
            'success' => true,
            'message' => 'Спасибо, ваша заявка получена'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Check rate limit
    $rateLimitCheck = $rateLimiter->checkLimit($clientIp);
    
    if (!$rateLimitCheck['allowed']) {
        $logger->log('WARNING', 'Rate limit exceeded', [
            'ip' => $clientIp,
            'reset_at' => $rateLimitCheck['reset_at']
        ]);
        
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Превышен лимит запросов',
            'details' => [
                'message' => 'Вы отправили слишком много заявок. Попробуйте позже.',
                'reset_at' => $rateLimitCheck['reset_at']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validate required fields
    $requiredFields = ['name', 'email', 'phone', 'service', 'description'];
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = 'Поле обязательно для заполнения';
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка валидации',
            'details' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка валидации',
            'details' => [
                'email' => 'Неверный формат email'
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validate string lengths
    $lengthValidation = [
        'name' => ['min' => 2, 'max' => 100],
        'email' => ['min' => 5, 'max' => 100],
        'phone' => ['min' => 10, 'max' => 20],
        'service' => ['min' => 3, 'max' => 100],
        'description' => ['min' => 10, 'max' => 2000]
    ];
    
    foreach ($lengthValidation as $field => $limits) {
        $length = mb_strlen($data[$field]);
        
        if ($length < $limits['min']) {
            $errors[$field] = "Минимальная длина: {$limits['min']} символов";
        }
        
        if ($length > $limits['max']) {
            $errors[$field] = "Максимальная длина: {$limits['max']} символов";
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка валидации',
            'details' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Handle file uploads
    $uploadedFiles = [];
    
    if (!empty($_FILES['files'])) {
        $files = $_FILES['files'];
        
        // Normalize file array structure
        if (is_array($files['name'])) {
            // Multiple files
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue; // Skip empty files
                }
                
                try {
                    $file = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                    
                    $uploadedFiles[] = $fileUploader->uploadFile($file);
                } catch (Exception $e) {
                    $logger->log('WARNING', 'File upload failed', [
                        'filename' => $files['name'][$i],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } else {
            // Single file
            if ($files['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $uploadedFiles[] = $fileUploader->uploadFile($files);
                } catch (Exception $e) {
                    $logger->log('WARNING', 'File upload failed', [
                        'filename' => $files['name'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    // Prepare order data
    $orderData = [
        'name' => trim($data['name']),
        'email' => trim($data['email']),
        'phone' => trim($data['phone']),
        'service' => trim($data['service']),
        'description' => trim($data['description']),
        'files' => $uploadedFiles,
        'ip' => $clientIp,
        'timestamp' => date('Y-m-d H:i:s'),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Format Telegram message
    $message = "📋 <b>Новый заказ с сайта!</b>\n\n";
    $message .= "👤 <b>Имя:</b> " . htmlspecialchars($orderData['name']) . "\n";
    $message .= "📧 <b>Email:</b> " . htmlspecialchars($orderData['email']) . "\n";
    $message .= "📱 <b>Телефон:</b> " . htmlspecialchars($orderData['phone']) . "\n\n";
    $message .= "🔧 <b>Услуга:</b> " . htmlspecialchars($orderData['service']) . "\n\n";
    $message .= "📝 <b>Описание:</b>\n" . htmlspecialchars($orderData['description']) . "\n\n";
    
    if (!empty($uploadedFiles)) {
        $message .= "📎 <b>Файлы:</b> " . count($uploadedFiles) . " шт.\n";
        foreach ($uploadedFiles as $file) {
            $message .= "  • {$file['original_name']} (" . round($file['size'] / 1024, 2) . " KB)\n";
        }
        $message .= "\n";
    }
    
    $message .= "⏰ <b>Время:</b> " . $orderData['timestamp'] . "\n";
    $message .= "🌍 <b>IP:</b> " . $orderData['ip'];
    
    // Try to send Telegram notification
    $telegramStatus = 'success';
    
    try {
        $responses = $telegramBot->broadcastMessage($message);
        
        // Check if any message was sent successfully
        $successCount = 0;
        foreach ($responses as $response) {
            if ($response['ok']) {
                $successCount++;
            }
        }
        
        if ($successCount === 0) {
            throw new Exception('Не удалось отправить уведомление ни одному пользователю');
        }
        
        $logger->log('INFO', 'Telegram notification sent', [
            'recipients' => count($responses),
            'success' => $successCount
        ]);
        
    } catch (Exception $e) {
        $telegramStatus = 'queued';
        
        // Add to queue for retry
        $queue->addToQueue($orderData);
        
        $logger->log('ERROR', 'Telegram notification failed, added to queue', [
            'error' => $e->getMessage()
        ]);
    }
    
    // Log order
    $logger->log('INFO', 'Order received', [
        'name' => $orderData['name'],
        'email' => $orderData['email'],
        'phone' => $orderData['phone'],
        'service' => $orderData['service'],
        'files_count' => count($uploadedFiles),
        'telegram_status' => $telegramStatus,
        'ip' => $clientIp
    ]);
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо, ваша заявка получена! Мы свяжемся с вами в ближайшее время.',
        'order_id' => uniqid('order_', true),
        'telegram_status' => $telegramStatus
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $logger->log('ERROR', 'Order processing failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Произошла ошибка при обработке заявки',
        'details' => [
            'message' => $e->getMessage()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
