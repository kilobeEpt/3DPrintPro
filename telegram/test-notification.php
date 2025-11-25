<?php
/**
 * Test Telegram Notification Script
 * 
 * Sends a test order notification to all authorized users.
 */

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found. Please copy .env.example to .env and configure it.\n");
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) {
        continue;
    }
    
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

// Load TelegramBot class
require_once __DIR__ . '/../php/TelegramBot.php';

// Initialize bot
$bot = new TelegramBot();

echo "====================================\n";
echo "Telegram Notification Test\n";
echo "====================================\n\n";

// Get authorized users
$users = $bot->getAuthorizedUsers();

if (empty($users)) {
    echo "⚠️  No authorized users found.\n\n";
    echo "To add users:\n";
    echo "1. Open Telegram and find your bot\n";
    echo "2. Send /start command\n";
    echo "3. Enter password: 852789456\n\n";
    exit(0);
}

echo "Found " . count($users) . " authorized user(s)\n\n";

// List authorized users
$allUsers = $bot->getAllUsers();
foreach ($allUsers as $user) {
    if ($user['authenticated']) {
        $name = $user['first_name'] ?? 'Unknown';
        $username = $user['username'] ? "@{$user['username']}" : 'No username';
        echo "  • {$name} ({$username}) - Chat ID: {$user['chat_id']}\n";
    }
}

echo "\nSending test notification...\n";

// Create test order
$testOrder = [
    'orderNumber' => 'TEST-' . date('YmdHis'),
    'clientName' => 'Иван Петров',
    'clientPhone' => '+7 (913) 123-45-67',
    'clientEmail' => 'ivan@example.com',
    'service' => '3D печать FDM',
    'amount' => 2500,
    'details' => 'Тестовый заказ для проверки уведомлений'
];

// Send notification
$results = $bot->sendOrderNotification($testOrder);

echo "\nResults:\n";

$successCount = 0;
$failCount = 0;

foreach ($results as $chatId => $result) {
    $user = $bot->getUser($chatId);
    $name = $user['first_name'] ?? 'Unknown';
    
    if ($result['ok']) {
        echo "  ✓ {$name} (Chat ID: {$chatId}) - Sent successfully\n";
        $successCount++;
    } else {
        echo "  ✗ {$name} (Chat ID: {$chatId}) - Failed: " . ($result['description'] ?? 'Unknown error') . "\n";
        $failCount++;
    }
}

echo "\n====================================\n";
echo "Summary:\n";
echo "  ✓ Sent: {$successCount}\n";
echo "  ✗ Failed: {$failCount}\n";
echo "  Total: " . count($results) . "\n";
echo "====================================\n\n";

if ($successCount > 0) {
    echo "✓ Test notification sent successfully!\n\n";
    echo "Check your Telegram to see the message.\n\n";
} else {
    echo "⚠️  All notifications failed. Please check:\n";
    echo "  1. Bot token is correct in .env\n";
    echo "  2. Users are properly authenticated\n";
    echo "  3. Bot is not blocked by users\n";
    echo "  4. Check storage/logs/telegram.log for errors\n\n";
}
