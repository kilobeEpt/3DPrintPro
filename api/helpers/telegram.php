<?php
// ========================================
// Telegram Notification Helper
// ========================================

class TelegramHelper {
    /**
     * Get Telegram credentials from database or config
     * 
     * @param Database $db Database instance
     * @return array ['botToken' => string, 'chatId' => string]
     */
    private static function getCredentials($db) {
        $botToken = '';
        $chatId = '';
        
        // Try to get from database first
        if ($db) {
            try {
                $dbBotToken = $db->getSetting('telegram_bot_token');
                if ($dbBotToken && !empty($dbBotToken)) {
                    $botToken = $dbBotToken;
                }
                
                $dbChatId = $db->getSetting('telegram_chat_id');
                if ($dbChatId && !empty($dbChatId)) {
                    $chatId = $dbChatId;
                }
            } catch (Exception $e) {
                // Ignore database errors
            }
        }
        
        // Fallback to config constants
        if (empty($botToken) && defined('TELEGRAM_BOT_TOKEN')) {
            $botToken = TELEGRAM_BOT_TOKEN;
        }
        if (empty($chatId) && defined('TELEGRAM_CHAT_ID')) {
            $chatId = TELEGRAM_CHAT_ID;
        }
        
        return ['botToken' => $botToken, 'chatId' => $chatId];
    }
    
    /**
     * Check if notifications are enabled for a specific event
     * 
     * @param Database $db Database instance
     * @param string $eventType Event type (new_order, status_change)
     * @return bool
     */
    private static function isNotificationEnabled($db, $eventType) {
        if (!$db) {
            return true; // Default to enabled if DB not available
        }
        
        try {
            $settingKey = 'telegram_notify_' . $eventType;
            $enabled = $db->getSetting($settingKey);
            return $enabled === null ? true : (bool)$enabled;
        } catch (Exception $e) {
            return true; // Default to enabled on error
        }
    }
    
    /**
     * Send order notification to Telegram
     * 
     * @param array $data Order data
     * @param string $orderNumber Order number
     * @param int $orderId Order ID
     * @param Database $db Database instance (for settings lookup)
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function sendOrderNotification($data, $orderNumber, $orderId, $db) {
        // Check if new order notifications are enabled
        if (!self::isNotificationEnabled($db, 'new_order')) {
            return [
                'success' => false,
                'error' => 'New order notifications disabled'
            ];
        }
        
        // Get credentials
        $credentials = self::getCredentials($db);
        $botToken = $credentials['botToken'];
        $chatId = $credentials['chatId'];
        
        // Validate credentials
        if (empty($botToken)) {
            return [
                'success' => false,
                'error' => 'Bot token not configured'
            ];
        }
        if (empty($chatId)) {
            return [
                'success' => false,
                'error' => 'Chat ID not configured'
            ];
        }
        
        // Build message
        $message = self::buildOrderMessage($data, $orderNumber, $orderId);
        
        // Send to Telegram API
        return self::sendMessage($botToken, $chatId, $message);
    }
    
    /**
     * Send status change notification to Telegram
     * 
     * @param int $orderId Order ID
     * @param string $orderNumber Order number
     * @param string $oldStatus Old status
     * @param string $newStatus New status
     * @param Database $db Database instance
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function sendStatusChangeNotification($orderId, $orderNumber, $oldStatus, $newStatus, $db) {
        // Check if status change notifications are enabled
        if (!self::isNotificationEnabled($db, 'status_change')) {
            return [
                'success' => false,
                'error' => 'Status change notifications disabled'
            ];
        }
        
        // Get credentials
        $credentials = self::getCredentials($db);
        $botToken = $credentials['botToken'];
        $chatId = $credentials['chatId'];
        
        // Validate credentials
        if (empty($botToken)) {
            return [
                'success' => false,
                'error' => 'Bot token not configured'
            ];
        }
        if (empty($chatId)) {
            return [
                'success' => false,
                'error' => 'Chat ID not configured'
            ];
        }
        
        // Build message
        $message = self::buildStatusChangeMessage($orderId, $orderNumber, $oldStatus, $newStatus);
        
        // Send to Telegram API
        return self::sendMessage($botToken, $chatId, $message);
    }
    
    /**
     * Send a test message to verify credentials
     * 
     * @param Database $db Database instance
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function sendTestMessage($db) {
        // Get credentials
        $credentials = self::getCredentials($db);
        $botToken = $credentials['botToken'];
        $chatId = $credentials['chatId'];
        
        // Validate credentials
        if (empty($botToken)) {
            return [
                'success' => false,
                'error' => 'Bot token not configured'
            ];
        }
        if (empty($chatId)) {
            return [
                'success' => false,
                'error' => 'Chat ID not configured'
            ];
        }
        
        // Build test message
        $message = "🧪 <b>Test Message</b>\n\n";
        $message .= "✅ Telegram integration is working correctly!\n";
        $message .= "⏰ " . date('d.m.Y H:i:s') . "\n";
        $message .= "🤖 Bot: Connected\n";
        $message .= "💬 Chat: Connected";
        
        // Send to Telegram API
        return self::sendMessage($botToken, $chatId, $message);
    }
    
    /**
     * Build status change message for Telegram
     */
    private static function buildStatusChangeMessage($orderId, $orderNumber, $oldStatus, $newStatus) {
        // Status labels in Russian
        $statusLabels = [
            'new' => '🆕 Новая',
            'processing' => '🔄 В работе',
            'completed' => '✅ Выполнена',
            'cancelled' => '❌ Отменена',
            'on_hold' => '⏸ На паузе'
        ];
        
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;
        
        $message = "🔔 <b>ИЗМЕНЕНИЕ СТАТУСА ЗАКАЗА</b>\n\n";
        $message .= "📋 <b>Заказ:</b> #{$orderNumber}\n";
        $message .= "🆔 <b>ID:</b> {$orderId}\n\n";
        $message .= "📊 <b>Статус изменен:</b>\n";
        $message .= "   {$oldLabel} → {$newLabel}\n\n";
        $message .= "⏰ <b>Дата:</b> " . date('d.m.Y H:i');
        
        return $message;
    }
    
    /**
     * Build order message for Telegram
     */
    private static function buildOrderMessage($data, $orderNumber, $orderId) {
        $message = "🆕 <b>НОВАЯ ЗАЯВКА #{$orderNumber}</b>\n\n";
        $message .= "🆔 <b>ID:</b> {$orderId}\n";
        $message .= "👤 <b>Имя:</b> " . htmlspecialchars($data['name'] ?? '', ENT_QUOTES, 'UTF-8') . "\n";
        $message .= "📱 <b>Телефон:</b> " . htmlspecialchars($data['phone'] ?? '', ENT_QUOTES, 'UTF-8') . "\n";
        
        if (!empty($data['email'])) {
            $message .= "📧 <b>Email:</b> " . htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') . "\n";
        }
        
        if (!empty($data['telegram'])) {
            $message .= "💬 <b>Telegram:</b> " . htmlspecialchars($data['telegram'], ENT_QUOTES, 'UTF-8') . "\n";
        }
        
        $message .= "\n🛠 <b>Услуга:</b> " . htmlspecialchars($data['service'] ?? 'Обращение', ENT_QUOTES, 'UTF-8') . "\n";
        
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
                    $message .= "• Материал: " . htmlspecialchars($calc['material'], ENT_QUOTES, 'UTF-8') . "\n";
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
                    $message .= "• Качество: " . htmlspecialchars($calc['quality'], ENT_QUOTES, 'UTF-8') . "\n";
                }
                if (!empty($calc['timeEstimate'])) {
                    $message .= "• Срок: " . htmlspecialchars($calc['timeEstimate'], ENT_QUOTES, 'UTF-8') . "\n";
                }
            }
        }
        
        // Add message/details
        if (!empty($data['message'])) {
            $message .= "\n💬 <b>Сообщение:</b>\n" . htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8') . "\n";
        } elseif (!empty($data['details'])) {
            $message .= "\n💬 <b>Сообщение:</b>\n" . htmlspecialchars($data['details'], ENT_QUOTES, 'UTF-8') . "\n";
        }
        
        $message .= "\n⏰ <b>Дата:</b> " . date('d.m.Y H:i') . "\n";
        if (defined('SITE_URL')) {
            $message .= "🌐 <b>Сайт:</b> " . SITE_URL;
        }
        
        return $message;
    }
    
    /**
     * Send message to Telegram
     */
    private static function sendMessage($botToken, $chatId, $message) {
        $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
        
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
}
