<?php
/**
 * Telegram Webhook Setup Script
 * 
 * Sets up the webhook for the Telegram bot with a secure secret token.
 * Run this script once to configure the webhook.
 */

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found. Please copy .env.example to .env and configure it.\n");
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];

foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) {
        continue;
    }
    
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $env[$key] = $value;
        
        if (!getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Check required variables
$botToken = getenv('TELEGRAM_BOT_TOKEN');
$appUrl = getenv('APP_URL');

if (!$botToken) {
    die("Error: TELEGRAM_BOT_TOKEN not set in .env file.\n");
}

if (!$appUrl) {
    die("Error: APP_URL not set in .env file.\n");
}

// Load TelegramBot class
require_once __DIR__ . '/../php/TelegramBot.php';

// Initialize bot
$bot = new TelegramBot();

echo "====================================\n";
echo "Telegram Webhook Setup\n";
echo "====================================\n\n";

// Generate webhook secret if not exists
$webhookSecret = getenv('TELEGRAM_WEBHOOK_SECRET');
if (!$webhookSecret || empty($webhookSecret)) {
    echo "Generating webhook secret...\n";
    $webhookSecret = bin2hex(random_bytes(32));
    
    // Update .env file
    $envContent = file_get_contents($envFile);
    $envContent = preg_replace(
        '/TELEGRAM_WEBHOOK_SECRET=.*/',
        "TELEGRAM_WEBHOOK_SECRET={$webhookSecret}",
        $envContent
    );
    file_put_contents($envFile, $envContent);
    
    echo "✓ Webhook secret generated and saved to .env\n\n";
} else {
    echo "✓ Using existing webhook secret from .env\n\n";
}

// Construct webhook URL
$webhookUrl = rtrim($appUrl, '/') . '/telegram/webhook.php';

echo "Webhook URL: {$webhookUrl}\n";
echo "Bot Token: " . substr($botToken, 0, 20) . "...\n\n";

// Get current webhook info
echo "Checking current webhook...\n";
$info = $bot->getWebhookInfo();

if ($info['ok'] && isset($info['result'])) {
    $currentUrl = $info['result']['url'] ?? '';
    
    if ($currentUrl) {
        echo "Current webhook: {$currentUrl}\n";
        
        if ($currentUrl === $webhookUrl) {
            echo "✓ Webhook is already set correctly\n\n";
            
            // Ask if user wants to continue
            echo "Do you want to reset the webhook? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            
            if (trim(strtolower($line)) !== 'yes') {
                echo "\nWebhook setup cancelled.\n";
                exit(0);
            }
        }
    } else {
        echo "No webhook currently set\n";
    }
}

echo "\nSetting up webhook...\n";

// Set webhook
$response = $bot->setWebhook($webhookUrl, $webhookSecret);

if ($response['ok']) {
    echo "✓ Webhook set successfully!\n\n";
    
    // Verify webhook
    echo "Verifying webhook...\n";
    $info = $bot->getWebhookInfo();
    
    if ($info['ok'] && isset($info['result'])) {
        $result = $info['result'];
        
        echo "\nWebhook Info:\n";
        echo "  URL: " . ($result['url'] ?? 'Not set') . "\n";
        echo "  Has custom certificate: " . ($result['has_custom_certificate'] ? 'Yes' : 'No') . "\n";
        echo "  Pending updates: " . ($result['pending_update_count'] ?? 0) . "\n";
        
        if (isset($result['last_error_date'])) {
            echo "  Last error: " . date('Y-m-d H:i:s', $result['last_error_date']) . "\n";
            echo "  Last error message: " . ($result['last_error_message'] ?? 'Unknown') . "\n";
        }
        
        echo "\n✓ Webhook verification complete\n\n";
    }
    
    echo "====================================\n";
    echo "Setup Complete!\n";
    echo "====================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Open Telegram and find your bot\n";
    echo "2. Send /start command\n";
    echo "3. Enter password: 852789456\n";
    echo "4. You will receive confirmation message\n\n";
    
    echo "To test notifications:\n";
    echo "  php telegram/test-notification.php\n\n";
    
} else {
    echo "✗ Failed to set webhook\n";
    echo "Error: " . ($response['description'] ?? 'Unknown error') . "\n";
    exit(1);
}
