<?php
// ========================================
// Telegram Notification Helper
// ========================================

class TelegramHelper {
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
        // Get chat ID from database settings or config
        $chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';
        
        // Try to get from database
        if ($db) {
            try {
                $dbChatId = $db->getSetting('telegram_chat_id');
                if ($dbChatId && !empty($dbChatId)) {
                    $chatId = $dbChatId;
                }
            } catch (Exception $e) {
                // Ignore database errors for chat ID lookup
            }
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
        $message = self::buildOrderMessage($data, $orderNumber, $orderId);
        
        // Send to Telegram API
        return self::sendMessage($chatId, $message);
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
    private static function sendMessage($chatId, $message) {
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
}
