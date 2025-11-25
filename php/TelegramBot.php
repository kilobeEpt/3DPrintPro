<?php
/**
 * TelegramBot - Password-based authentication system for Telegram notifications
 * 
 * Manages user authentication via password and stores authorized chat IDs
 * for order notification broadcasting.
 */

class TelegramBot {
    private $botToken;
    private $password;
    private $apiUrl;
    private $dataFile;
    private $logFile;
    private $maxRetries = 3;
    private $retryDelay = 1000000; // 1 second in microseconds

    public function __construct($botToken = null, $password = null) {
        $this->botToken = $botToken ?: getenv('TELEGRAM_BOT_TOKEN');
        $this->password = $password ?: getenv('TELEGRAM_PASSWORD');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
        $this->dataFile = __DIR__ . '/../storage/data/telegram_users.json';
        $this->logFile = __DIR__ . '/../storage/logs/telegram.log';
        
        $this->ensureDirectoryExists();
    }

    /**
     * Ensure storage directories exist
     */
    private function ensureDirectoryExists() {
        $dataDir = dirname($this->dataFile);
        $logDir = dirname($this->logFile);
        
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Log action to file
     */
    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Load users from JSON file
     */
    private function loadUsers() {
        if (!file_exists($this->dataFile)) {
            return [];
        }
        
        $content = file_get_contents($this->dataFile);
        $users = json_decode($content, true);
        
        return is_array($users) ? $users : [];
    }

    /**
     * Save users to JSON file
     */
    private function saveUsers($users) {
        $json = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->dataFile, $json) !== false;
    }

    /**
     * Make API request to Telegram with retry logic
     */
    private function apiRequest($method, $data = [], $retry = 0) {
        $url = $this->apiUrl . '/' . $method;
        
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($options);
        
        try {
            $result = @file_get_contents($url, false, $context);
            
            if ($result === false) {
                $error = error_get_last();
                throw new Exception($error['message'] ?? 'Unknown error');
            }
            
            $response = json_decode($result, true);
            
            if (!$response['ok']) {
                throw new Exception($response['description'] ?? 'API request failed');
            }
            
            return $response;
            
        } catch (Exception $e) {
            $this->log('ERROR', "API request failed: {$method}", [
                'error' => $e->getMessage(),
                'retry' => $retry
            ]);
            
            if ($retry < $this->maxRetries) {
                usleep($this->retryDelay * ($retry + 1)); // Exponential backoff
                return $this->apiRequest($method, $data, $retry + 1);
            }
            
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Authenticate user with password and save chat_id
     * 
     * @param int $chatId Telegram chat ID
     * @param string $password Password to verify
     * @param array $userInfo Additional user info (username, first_name, last_name)
     * @return bool True if authenticated successfully
     */
    public function authenticate($chatId, $password, $userInfo = []) {
        if ($password !== $this->password) {
            $this->log('WARNING', 'Failed authentication attempt', [
                'chat_id' => $chatId,
                'username' => $userInfo['username'] ?? 'unknown'
            ]);
            return false;
        }
        
        $users = $this->loadUsers();
        
        $users[(string)$chatId] = [
            'chat_id' => $chatId,
            'username' => $userInfo['username'] ?? null,
            'first_name' => $userInfo['first_name'] ?? null,
            'last_name' => $userInfo['last_name'] ?? null,
            'authenticated' => true,
            'subscribed_at' => date('Y-m-d H:i:s'),
            'last_message' => date('Y-m-d H:i:s')
        ];
        
        $this->saveUsers($users);
        
        $this->log('INFO', 'User authenticated successfully', [
            'chat_id' => $chatId,
            'username' => $userInfo['username'] ?? 'unknown'
        ]);
        
        return true;
    }

    /**
     * Get list of authorized chat IDs
     * 
     * @return array Array of chat IDs
     */
    public function getAuthorizedUsers() {
        $users = $this->loadUsers();
        $chatIds = [];
        
        foreach ($users as $user) {
            if (isset($user['authenticated']) && $user['authenticated']) {
                $chatIds[] = $user['chat_id'];
            }
        }
        
        return $chatIds;
    }

    /**
     * Check if user is authorized
     * 
     * @param int $chatId Telegram chat ID
     * @return bool
     */
    public function isAuthorized($chatId) {
        $users = $this->loadUsers();
        $chatIdStr = (string)$chatId;
        
        return isset($users[$chatIdStr]) && 
               isset($users[$chatIdStr]['authenticated']) && 
               $users[$chatIdStr]['authenticated'];
    }

    /**
     * Update last message timestamp for user
     */
    public function updateLastMessage($chatId) {
        $users = $this->loadUsers();
        $chatIdStr = (string)$chatId;
        
        if (isset($users[$chatIdStr])) {
            $users[$chatIdStr]['last_message'] = date('Y-m-d H:i:s');
            $this->saveUsers($users);
        }
    }

    /**
     * Send message to specific chat
     * 
     * @param int $chatId Telegram chat ID
     * @param string $message Message text
     * @param array $options Additional options (parse_mode, etc.)
     * @return array Response from Telegram API
     */
    public function sendMessage($chatId, $message, $options = []) {
        $data = array_merge([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ], $options);
        
        $response = $this->apiRequest('sendMessage', $data);
        
        if ($response['ok']) {
            $this->log('INFO', 'Message sent successfully', [
                'chat_id' => $chatId
            ]);
            $this->updateLastMessage($chatId);
        } else {
            $this->log('ERROR', 'Failed to send message', [
                'chat_id' => $chatId,
                'error' => $response['description'] ?? 'Unknown error'
            ]);
        }
        
        return $response;
    }

    /**
     * Broadcast message to all authorized users
     * 
     * @param string $message Message text
     * @param array $options Additional options
     * @return array Array of responses per chat_id
     */
    public function broadcastMessage($message, $options = []) {
        $chatIds = $this->getAuthorizedUsers();
        $results = [];
        
        $this->log('INFO', 'Broadcasting message', [
            'recipients_count' => count($chatIds)
        ]);
        
        foreach ($chatIds as $chatId) {
            $response = $this->sendMessage($chatId, $message, $options);
            $results[$chatId] = $response;
            
            // Small delay to avoid rate limiting
            usleep(100000); // 100ms
        }
        
        return $results;
    }

    /**
     * Remove user from authorized list
     * 
     * @param int $chatId Telegram chat ID
     * @return bool True if removed successfully
     */
    public function removeUser($chatId) {
        $users = $this->loadUsers();
        $chatIdStr = (string)$chatId;
        
        if (isset($users[$chatIdStr])) {
            unset($users[$chatIdStr]);
            $this->saveUsers($users);
            
            $this->log('INFO', 'User unsubscribed', [
                'chat_id' => $chatId
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Get user info
     * 
     * @param int $chatId Telegram chat ID
     * @return array|null User data or null if not found
     */
    public function getUser($chatId) {
        $users = $this->loadUsers();
        $chatIdStr = (string)$chatId;
        
        return $users[$chatIdStr] ?? null;
    }

    /**
     * Get all users data
     * 
     * @return array All users
     */
    public function getAllUsers() {
        return $this->loadUsers();
    }

    /**
     * Send order notification to all authorized users
     * 
     * @param array $order Order data
     * @return array Broadcast results
     */
    public function sendOrderNotification($order) {
        $message = $this->formatOrderMessage($order);
        return $this->broadcastMessage($message);
    }

    /**
     * Format order data as message
     * 
     * @param array $order Order data
     * @return string Formatted message
     */
    private function formatOrderMessage($order) {
        $message = "🔔 <b>НОВЫЙ ЗАКАЗ</b>\n\n";
        
        if (isset($order['orderNumber'])) {
            $message .= "📋 <b>Номер:</b> #{$order['orderNumber']}\n";
        }
        
        if (isset($order['clientName'])) {
            $message .= "👤 <b>Клиент:</b> {$order['clientName']}\n";
        }
        
        if (isset($order['clientPhone'])) {
            $message .= "📱 <b>Телефон:</b> {$order['clientPhone']}\n";
        }
        
        if (isset($order['clientEmail'])) {
            $message .= "📧 <b>Email:</b> {$order['clientEmail']}\n";
        }
        
        if (isset($order['service'])) {
            $message .= "\n🛠 <b>Услуга:</b> {$order['service']}\n";
        }
        
        if (isset($order['amount'])) {
            $message .= "💰 <b>Сумма:</b> " . number_format($order['amount'], 0, '.', ' ') . " ₽\n";
        }
        
        if (isset($order['details'])) {
            $message .= "\n💬 <b>Комментарий:</b>\n{$order['details']}\n";
        }
        
        $message .= "\n⏰ <b>Дата:</b> " . date('d.m.Y H:i:s');
        
        return $message;
    }

    /**
     * Set webhook URL
     * 
     * @param string $url Webhook URL
     * @param string $secret Optional secret token
     * @return array Response from Telegram API
     */
    public function setWebhook($url, $secret = null) {
        $data = ['url' => $url];
        
        if ($secret) {
            $data['secret_token'] = $secret;
        }
        
        $response = $this->apiRequest('setWebhook', $data);
        
        $this->log('INFO', 'Webhook set', [
            'url' => $url,
            'success' => $response['ok']
        ]);
        
        return $response;
    }

    /**
     * Delete webhook
     * 
     * @return array Response from Telegram API
     */
    public function deleteWebhook() {
        $response = $this->apiRequest('deleteWebhook');
        
        $this->log('INFO', 'Webhook deleted', [
            'success' => $response['ok']
        ]);
        
        return $response;
    }

    /**
     * Get webhook info
     * 
     * @return array Response from Telegram API
     */
    public function getWebhookInfo() {
        return $this->apiRequest('getWebhookInfo');
    }
}
