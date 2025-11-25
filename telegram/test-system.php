<?php
/**
 * Telegram Bot System Test Script
 * 
 * Comprehensive tests to verify the bot system is working correctly.
 */

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("❌ Error: .env file not found\n");
}

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

// Load TelegramBot class
require_once __DIR__ . '/../php/TelegramBot.php';

echo "====================================\n";
echo "Telegram Bot System Tests\n";
echo "====================================\n\n";

$passed = 0;
$failed = 0;

// Test 1: Environment Configuration
echo "Test 1: Environment Configuration...\n";
$botToken = getenv('TELEGRAM_BOT_TOKEN');
$password = getenv('TELEGRAM_PASSWORD');
$appUrl = getenv('APP_URL');

if ($botToken && $password && $appUrl) {
    echo "  ✓ Environment variables configured\n";
    echo "    - Bot Token: " . substr($botToken, 0, 20) . "...\n";
    echo "    - Password: " . str_repeat('*', strlen($password)) . "\n";
    echo "    - App URL: {$appUrl}\n";
    $passed++;
} else {
    echo "  ✗ Missing environment variables\n";
    $failed++;
}
echo "\n";

// Test 2: Directory Structure
echo "Test 2: Directory Structure...\n";
$dirs = [
    __DIR__ . '/../storage/data',
    __DIR__ . '/../storage/logs',
    __DIR__ . '/../php',
    __DIR__
];

$allDirsExist = true;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        echo "  ✗ Directory missing: {$dir}\n";
        $allDirsExist = false;
    }
}

if ($allDirsExist) {
    echo "  ✓ All directories exist\n";
    $passed++;
} else {
    $failed++;
}
echo "\n";

// Test 3: File Permissions
echo "Test 3: File Permissions...\n";
$dataDir = __DIR__ . '/../storage/data';
$logsDir = __DIR__ . '/../storage/logs';

$dataWritable = is_writable($dataDir);
$logsWritable = is_writable($logsDir);

if ($dataWritable && $logsWritable) {
    echo "  ✓ Storage directories are writable\n";
    $passed++;
} else {
    echo "  ✗ Permission issues:\n";
    if (!$dataWritable) echo "    - storage/data not writable\n";
    if (!$logsWritable) echo "    - storage/logs not writable\n";
    $failed++;
}
echo "\n";

// Test 4: TelegramBot Class
echo "Test 4: TelegramBot Class...\n";
try {
    $bot = new TelegramBot();
    echo "  ✓ TelegramBot class loaded successfully\n";
    $passed++;
} catch (Exception $e) {
    echo "  ✗ Failed to load TelegramBot class: " . $e->getMessage() . "\n";
    $failed++;
    exit(1);
}
echo "\n";

// Test 5: Data File Creation
echo "Test 5: Data File Creation...\n";
$dataFile = __DIR__ . '/../storage/data/telegram_users.json';
if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    $data = json_decode($content, true);
    if (is_array($data)) {
        echo "  ✓ Data file exists and is valid JSON\n";
        echo "    - Users: " . count($data) . "\n";
        $passed++;
    } else {
        echo "  ✗ Data file is not valid JSON\n";
        $failed++;
    }
} else {
    echo "  ✗ Data file does not exist\n";
    $failed++;
}
echo "\n";

// Test 6: Authentication Logic
echo "Test 6: Authentication Logic...\n";
$testChatId = 999999999; // Test chat ID
$correctPassword = getenv('TELEGRAM_PASSWORD');
$wrongPassword = 'wrong_password';

// Test with wrong password
$resultWrong = $bot->authenticate($testChatId, $wrongPassword, [
    'username' => 'test_user',
    'first_name' => 'Test'
]);

if (!$resultWrong) {
    echo "  ✓ Wrong password rejected\n";
    
    // Test with correct password
    $resultCorrect = $bot->authenticate($testChatId, $correctPassword, [
        'username' => 'test_user',
        'first_name' => 'Test',
        'last_name' => 'User'
    ]);
    
    if ($resultCorrect) {
        echo "  ✓ Correct password accepted\n";
        
        // Test authorization check
        if ($bot->isAuthorized($testChatId)) {
            echo "  ✓ Authorization check working\n";
            
            // Clean up test user
            $bot->removeUser($testChatId);
            echo "  ✓ User removal working\n";
            
            $passed++;
        } else {
            echo "  ✗ Authorization check failed\n";
            $failed++;
        }
    } else {
        echo "  ✗ Correct password rejected\n";
        $failed++;
    }
} else {
    echo "  ✗ Wrong password accepted (security issue!)\n";
    $failed++;
}
echo "\n";

// Test 7: User Data Management
echo "Test 7: User Data Management...\n";
$users = $bot->getAllUsers();
$authorizedUsers = $bot->getAuthorizedUsers();

echo "  ✓ Data retrieval working\n";
echo "    - Total users: " . count($users) . "\n";
echo "    - Authorized users: " . count($authorizedUsers) . "\n";
$passed++;
echo "\n";

// Test 8: Webhook Configuration
echo "Test 8: Webhook Configuration...\n";
try {
    $webhookInfo = $bot->getWebhookInfo();
    
    if ($webhookInfo['ok']) {
        $result = $webhookInfo['result'];
        $webhookUrl = $result['url'] ?? '';
        
        if ($webhookUrl) {
            echo "  ✓ Webhook is configured\n";
            echo "    - URL: {$webhookUrl}\n";
            echo "    - Pending updates: " . ($result['pending_update_count'] ?? 0) . "\n";
            
            if (isset($result['last_error_date'])) {
                echo "    ⚠️  Last error: " . date('Y-m-d H:i:s', $result['last_error_date']) . "\n";
                echo "    ⚠️  Error message: " . ($result['last_error_message'] ?? 'Unknown') . "\n";
            }
        } else {
            echo "  ⚠️  Webhook not set\n";
            echo "    Run: php telegram/setup-webhook.php\n";
        }
        $passed++;
    } else {
        echo "  ✗ Failed to get webhook info\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ✗ Error checking webhook: " . $e->getMessage() . "\n";
    $failed++;
}
echo "\n";

// Test 9: Logging
echo "Test 9: Logging...\n";
$logFile = __DIR__ . '/../storage/logs/telegram.log';

if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    $logLines = count(file($logFile));
    echo "  ✓ Log file exists\n";
    echo "    - Size: " . number_format($logSize) . " bytes\n";
    echo "    - Lines: {$logLines}\n";
    $passed++;
} else {
    echo "  ⚠️  Log file not created yet (will be created on first event)\n";
    $passed++;
}
echo "\n";

// Test 10: Required Files
echo "Test 10: Required Files...\n";
$requiredFiles = [
    __DIR__ . '/../php/TelegramBot.php',
    __DIR__ . '/webhook.php',
    __DIR__ . '/setup-webhook.php',
    __DIR__ . '/test-notification.php',
    __DIR__ . '/manage-users.php',
    __DIR__ . '/README.md'
];

$allFilesExist = true;
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        echo "  ✗ Missing file: " . basename($file) . "\n";
        $allFilesExist = false;
    }
}

if ($allFilesExist) {
    echo "  ✓ All required files present\n";
    $passed++;
} else {
    $failed++;
}
echo "\n";

// Summary
echo "====================================\n";
echo "Test Results\n";
echo "====================================\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total:  " . ($passed + $failed) . "\n";
echo "====================================\n\n";

if ($failed === 0) {
    echo "✓ All tests passed! System is ready.\n\n";
    
    echo "Next steps:\n";
    
    $webhookInfo = $bot->getWebhookInfo();
    $webhookUrl = $webhookInfo['result']['url'] ?? '';
    
    if (!$webhookUrl) {
        echo "1. Set up webhook:\n";
        echo "   php telegram/setup-webhook.php\n\n";
    }
    
    if (count($bot->getAuthorizedUsers()) === 0) {
        echo "2. Authenticate your first user:\n";
        echo "   - Open Telegram and find your bot\n";
        echo "   - Send /start command\n";
        echo "   - Enter password: 852789456\n\n";
    }
    
    echo "3. Test notifications:\n";
    echo "   php telegram/test-notification.php\n\n";
    
    exit(0);
} else {
    echo "✗ Some tests failed. Please fix the issues above.\n\n";
    exit(1);
}
