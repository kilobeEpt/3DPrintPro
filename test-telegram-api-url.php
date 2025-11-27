#!/usr/bin/env php
<?php
/**
 * Test Telegram API URL Construction
 * 
 * Verifies that the bot token is properly loaded and included in API URLs
 */

echo "=== Telegram API URL Construction Test ===\n\n";

// Test 1: Load environment variables
echo "Test 1: Loading environment variables...\n";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
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
    echo "✓ Environment variables loaded\n";
} else {
    echo "✗ .env file not found\n";
    exit(1);
}

// Test 2: Check if bot token is available
echo "\nTest 2: Checking bot token availability...\n";
$token = getenv('TELEGRAM_BOT_TOKEN');
if ($token) {
    // Show only first and last 5 characters for security
    $maskedToken = substr($token, 0, 5) . '...' . substr($token, -5);
    echo "✓ Bot token found: {$maskedToken}\n";
    echo "  Token length: " . strlen($token) . " characters\n";
} else {
    echo "✗ Bot token not found\n";
    exit(1);
}

// Test 3: Load TelegramBot class
echo "\nTest 3: Loading TelegramBot class...\n";
require_once __DIR__ . '/php/TelegramBot.php';
echo "✓ TelegramBot class loaded\n";

// Test 4: Instantiate TelegramBot
echo "\nTest 4: Instantiating TelegramBot...\n";
$bot = new TelegramBot();
echo "✓ TelegramBot instance created\n";

// Test 5: Check API URL construction (using reflection to access private property)
echo "\nTest 5: Checking API URL construction...\n";
$reflection = new ReflectionClass($bot);
$apiUrlProperty = $reflection->getProperty('apiUrl');
$apiUrlProperty->setAccessible(true);
$apiUrl = $apiUrlProperty->getValue($bot);

echo "  API URL: {$apiUrl}\n";

// Verify URL format
$expectedPattern = '/^https:\/\/api\.telegram\.org\/bot\d+:[A-Za-z0-9_-]+$/';
if (preg_match($expectedPattern, $apiUrl)) {
    echo "✓ API URL format is correct\n";
    echo "  Pattern match: URL includes bot token\n";
} else {
    echo "✗ API URL format is incorrect\n";
    echo "  Expected pattern: https://api.telegram.org/bot{TOKEN}\n";
    echo "  Actual URL: {$apiUrl}\n";
    exit(1);
}

// Test 6: Verify URL doesn't have double slashes (bug symptom)
echo "\nTest 6: Checking for bug symptoms...\n";
if (strpos($apiUrl, '/bot/') !== false) {
    echo "✗ BUG DETECTED: URL contains '/bot/' which means token is missing\n";
    echo "  URL: {$apiUrl}\n";
    exit(1);
} else {
    echo "✓ No bug detected: Token is properly included in URL\n";
}

// Test 7: Test method URL construction
echo "\nTest 7: Testing method URL construction...\n";
$testMethod = 'sendMessage';
$fullUrl = $apiUrl . '/' . $testMethod;
echo "  Full URL: {$fullUrl}\n";

if (preg_match('/^https:\/\/api\.telegram\.org\/bot\d+:[A-Za-z0-9_-]+\/sendMessage$/', $fullUrl)) {
    echo "✓ Method URL format is correct\n";
} else {
    echo "✗ Method URL format is incorrect\n";
    exit(1);
}

// Test 8: Get authorized users count
echo "\nTest 8: Checking authorized users...\n";
$users = $bot->getAuthorizedUsers();
echo "  Authorized users: " . count($users) . "\n";
if (count($users) > 0) {
    echo "✓ Found authorized users ready to receive notifications\n";
    foreach ($users as $chatId) {
        $user = $bot->getUser($chatId);
        $username = $user['username'] ?? 'unknown';
        echo "    - Chat ID: {$chatId}, Username: @{$username}\n";
    }
} else {
    echo "! Warning: No authorized users found. Use /start command in Telegram to authenticate.\n";
}

echo "\n=== All Tests Passed! ===\n";
echo "\nSummary:\n";
echo "✓ Environment variables loaded correctly\n";
echo "✓ Bot token is available and properly formatted\n";
echo "✓ TelegramBot class instantiated successfully\n";
echo "✓ API URL construction is correct\n";
echo "✓ No bug symptoms detected\n";
echo "✓ Method URLs are properly formatted\n";
echo "\nThe Telegram API URL bug has been fixed!\n";
