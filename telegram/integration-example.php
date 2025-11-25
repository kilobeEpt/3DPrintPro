<?php
/**
 * Example: Integration with Order Processing
 * 
 * This file demonstrates how to integrate the Telegram bot
 * with your existing order processing workflow.
 */

// Example: Integration in contact.php or order submission handler

// Load environment variables (if not already loaded)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load TelegramBot class
require_once __DIR__ . '/../php/TelegramBot.php';

// Example 1: Send notification when order is created
function sendOrderNotification($orderData) {
    try {
        $bot = new TelegramBot();
        
        // Prepare order data
        $order = [
            'orderNumber' => $orderData['order_number'] ?? 'N/A',
            'clientName' => $orderData['name'] ?? 'Unknown',
            'clientPhone' => $orderData['phone'] ?? '',
            'clientEmail' => $orderData['email'] ?? '',
            'service' => $orderData['service'] ?? 'General inquiry',
            'amount' => $orderData['amount'] ?? 0,
            'details' => $orderData['message'] ?? $orderData['details'] ?? ''
        ];
        
        // Send to all authorized users
        $results = $bot->sendOrderNotification($order);
        
        // Log results
        $successCount = 0;
        foreach ($results as $result) {
            if ($result['ok']) {
                $successCount++;
            }
        }
        
        error_log("Telegram notification sent to {$successCount} users");
        
        return true;
    } catch (Exception $e) {
        // Don't fail the order if notification fails
        error_log("Failed to send Telegram notification: " . $e->getMessage());
        return false;
    }
}

// Example 2: Send custom message
function sendCustomNotification($message) {
    try {
        $bot = new TelegramBot();
        $results = $bot->broadcastMessage($message);
        return true;
    } catch (Exception $e) {
        error_log("Failed to send custom notification: " . $e->getMessage());
        return false;
    }
}

// Example 3: Check if notifications are enabled
function areNotificationsEnabled() {
    try {
        $bot = new TelegramBot();
        $users = $bot->getAuthorizedUsers();
        return count($users) > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Example usage in form submission handler:
/*

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... validate form data ...
    
    // Process order
    $orderData = [
        'order_number' => generateOrderNumber(),
        'name' => $_POST['name'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'],
        'service' => $_POST['service'],
        'amount' => calculateAmount($_POST),
        'details' => $_POST['message']
    ];
    
    // Save to database
    saveOrder($orderData);
    
    // Send Telegram notification (non-blocking)
    sendOrderNotification($orderData);
    
    // Send response to user
    echo json_encode([
        'success' => true,
        'message' => 'Заказ принят! Мы свяжемся с вами в ближайшее время.'
    ]);
}

*/

// Example 4: Integration with existing js/telegram.js
// The frontend code will continue to work as-is
// Backend will handle notifications via webhook system

// Example 5: Send notification to specific user (if you have their chat_id)
function sendToSpecificUser($chatId, $message) {
    try {
        $bot = new TelegramBot();
        
        if ($bot->isAuthorized($chatId)) {
            $result = $bot->sendMessage($chatId, $message);
            return $result['ok'];
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Failed to send to specific user: " . $e->getMessage());
        return false;
    }
}

// Example 6: Get notification statistics
function getNotificationStats() {
    try {
        $bot = new TelegramBot();
        $users = $bot->getAllUsers();
        
        return [
            'total_users' => count($users),
            'authorized_users' => count(array_filter($users, function($u) {
                return $u['authenticated'];
            })),
            'last_subscription' => !empty($users) ? max(array_column($users, 'subscribed_at')) : null
        ];
    } catch (Exception $e) {
        return [
            'total_users' => 0,
            'authorized_users' => 0,
            'last_subscription' => null
        ];
    }
}

// Example 7: Graceful handling - don't break if Telegram is down
function sendOrderWithFallback($orderData) {
    // Try Telegram first
    $telegramSent = sendOrderNotification($orderData);
    
    // Fallback to email if Telegram fails
    if (!$telegramSent) {
        // Send email notification instead
        sendEmailNotification($orderData);
    }
    
    return true;
}

// Example 8: Format different types of messages
function sendStatusUpdate($orderNumber, $status, $customerName) {
    $message = "📦 <b>Обновление заказа #{$orderNumber}</b>\n\n";
    $message .= "👤 <b>Клиент:</b> {$customerName}\n";
    $message .= "📊 <b>Статус:</b> {$status}\n\n";
    $message .= "⏰ " . date('d.m.Y H:i:s');
    
    sendCustomNotification($message);
}

// Example 9: Daily summary
function sendDailySummary() {
    // Get today's statistics
    $ordersCount = getTodaysOrdersCount();
    $revenue = getTodaysRevenue();
    
    $message = "📊 <b>Ежедневная сводка</b>\n\n";
    $message .= "📋 <b>Заказов:</b> {$ordersCount}\n";
    $message .= "💰 <b>Выручка:</b> " . number_format($revenue, 0, '.', ' ') . " ₽\n\n";
    $message .= "📅 " . date('d.m.Y');
    
    sendCustomNotification($message);
}

// Note: This is an example file showing integration patterns.
// Implement these functions in your actual order processing code.
