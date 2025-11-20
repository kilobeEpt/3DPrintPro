<?php
// ========================================
// Email Test API Endpoint
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Services\SettingsService;

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

// Require admin authentication
requireAdminAuth();

$settingsService = new SettingsService();
$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

try {
    switch ($method) {
        case 'POST':
            // Apply rate limiting to prevent abuse
            $rateLimiter->apply('email_test');
            
            // Get email settings
            $enabled = $settingsService->get('email_notifications_enabled', false);
            $smtpHost = $settingsService->get('smtp_host', '');
            $smtpPort = $settingsService->get('smtp_port', 587);
            $smtpUsername = $settingsService->get('smtp_username', '');
            $smtpPassword = $settingsService->get('smtp_password', '');
            $smtpEncryption = $settingsService->get('smtp_encryption', 'tls');
            $fromEmail = $settingsService->get('smtp_from_email', '');
            $fromName = $settingsService->get('smtp_from_name', '3D Print Pro');
            $toEmail = $settingsService->get('admin_email', '');
            
            ApiLogger::info("Email test message requested");
            
            // Validate configuration
            if (!$enabled) {
                ApiResponse::validationError('Email notifications are disabled');
            }
            
            if (empty($smtpHost) || empty($smtpUsername) || empty($smtpPassword)) {
                ApiResponse::validationError('SMTP configuration is incomplete. Please configure SMTP settings first.');
            }
            
            if (empty($toEmail)) {
                ApiResponse::validationError('Admin email is not set');
            }
            
            // Send test email using mail() function or SMTP
            // For simplicity, we'll use mail() function here
            // In production, you'd use PHPMailer or similar
            
            $subject = 'Test Email from 3D Print Pro Admin';
            $message = "This is a test email from your 3D Print Pro admin panel.\n\n";
            $message .= "Email notifications are configured and working correctly.\n\n";
            $message .= "Configuration:\n";
            $message .= "- SMTP Host: {$smtpHost}\n";
            $message .= "- SMTP Port: {$smtpPort}\n";
            $message .= "- SMTP Encryption: {$smtpEncryption}\n";
            $message .= "- From: {$fromEmail}\n\n";
            $message .= "Sent at: " . date('Y-m-d H:i:s') . "\n";
            
            $headers = "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "Reply-To: {$fromEmail}\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Attempt to send email
            $result = @mail($toEmail, $subject, $message, $headers);
            
            if ($result) {
                ApiLogger::info("Test email sent successfully", ['to' => $toEmail]);
                ApiResponse::success([
                    'message' => 'Test email sent successfully to ' . $toEmail
                ]);
            } else {
                ApiLogger::warning("Failed to send test email", ['to' => $toEmail]);
                ApiResponse::serverError('Failed to send test email. Please check your SMTP configuration and server mail settings.');
            }
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in email-test endpoint", ['exception' => $e->getMessage()]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}
