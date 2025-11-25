<?php
/**
 * Telegram Users Management Script
 * 
 * View and manage authorized Telegram users.
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

// Parse command line arguments
$command = $argv[1] ?? 'list';

switch ($command) {
    case 'list':
        listUsers($bot);
        break;
        
    case 'remove':
        if (!isset($argv[2])) {
            echo "Error: Chat ID required\n";
            echo "Usage: php manage-users.php remove <chat_id>\n";
            exit(1);
        }
        removeUser($bot, $argv[2]);
        break;
        
    case 'info':
        if (!isset($argv[2])) {
            echo "Error: Chat ID required\n";
            echo "Usage: php manage-users.php info <chat_id>\n";
            exit(1);
        }
        showUserInfo($bot, $argv[2]);
        break;
        
    case 'help':
        showHelp();
        break;
        
    default:
        echo "Unknown command: {$command}\n\n";
        showHelp();
        exit(1);
}

/**
 * List all authorized users
 */
function listUsers($bot) {
    echo "====================================\n";
    echo "Authorized Telegram Users\n";
    echo "====================================\n\n";
    
    $users = $bot->getAllUsers();
    
    if (empty($users)) {
        echo "No users found.\n\n";
        echo "To add users, send /start to the bot and enter password: 852789456\n\n";
        return;
    }
    
    echo "Total users: " . count($users) . "\n\n";
    
    foreach ($users as $user) {
        if ($user['authenticated']) {
            $name = $user['first_name'] ?? 'Unknown';
            if (isset($user['last_name'])) {
                $name .= ' ' . $user['last_name'];
            }
            
            $username = $user['username'] ? "@{$user['username']}" : 'No username';
            
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "  Name: {$name}\n";
            echo "  Username: {$username}\n";
            echo "  Chat ID: {$user['chat_id']}\n";
            echo "  Subscribed: {$user['subscribed_at']}\n";
            echo "  Last message: {$user['last_message']}\n";
        }
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

/**
 * Remove user by chat_id
 */
function removeUser($bot, $chatId) {
    echo "Removing user with Chat ID: {$chatId}...\n";
    
    $user = $bot->getUser($chatId);
    
    if (!$user) {
        echo "✗ User not found\n";
        exit(1);
    }
    
    $name = $user['first_name'] ?? 'Unknown';
    
    if ($bot->removeUser($chatId)) {
        echo "✓ User '{$name}' removed successfully\n";
        
        // Try to notify the user
        try {
            $bot->sendMessage($chatId,
                "ℹ️ Вы были отписаны от уведомлений администратором.\n\n" .
                "Чтобы подписаться снова, отправьте /start"
            );
            echo "✓ Notification sent to user\n";
        } catch (Exception $e) {
            echo "⚠️  Could not notify user: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ Failed to remove user\n";
        exit(1);
    }
}

/**
 * Show detailed user info
 */
function showUserInfo($bot, $chatId) {
    $user = $bot->getUser($chatId);
    
    if (!$user) {
        echo "User with Chat ID {$chatId} not found\n";
        exit(1);
    }
    
    echo "====================================\n";
    echo "User Information\n";
    echo "====================================\n\n";
    
    foreach ($user as $key => $value) {
        if (is_bool($value)) {
            $value = $value ? 'Yes' : 'No';
        }
        $label = ucwords(str_replace('_', ' ', $key));
        echo "  {$label}: {$value}\n";
    }
    
    echo "\n";
}

/**
 * Show help message
 */
function showHelp() {
    echo "====================================\n";
    echo "Telegram Users Management\n";
    echo "====================================\n\n";
    
    echo "Usage:\n";
    echo "  php manage-users.php <command> [arguments]\n\n";
    
    echo "Commands:\n";
    echo "  list              List all authorized users\n";
    echo "  info <chat_id>    Show detailed user information\n";
    echo "  remove <chat_id>  Remove user from authorized list\n";
    echo "  help              Show this help message\n\n";
    
    echo "Examples:\n";
    echo "  php manage-users.php list\n";
    echo "  php manage-users.php info 123456789\n";
    echo "  php manage-users.php remove 123456789\n\n";
}
