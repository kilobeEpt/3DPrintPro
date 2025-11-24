<?php
// ========================================
// Telegram Test API Endpoint
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/telegram.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/db.php';

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

// Require admin authentication
requireAdminAuth();

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

try {
    switch ($method) {
        case 'POST':
            // Apply rate limiting to prevent abuse
            $rateLimiter->apply('telegram_test');
            
            // Send test message
            ApiLogger::info("Telegram test message requested");
            
            $result = TelegramHelper::sendTestMessage($db);
            
            if ($result['success']) {
                ApiLogger::info("Telegram test message sent successfully");
                ApiResponse::success([
                    'message' => 'Test message sent successfully'
                ]);
            } else {
                ApiLogger::warning("Telegram test message failed", ['error' => $result['error']]);
                ApiResponse::validationError('Failed to send test message: ' . $result['error']);
            }
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'telegram_test', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in telegram-test endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();
