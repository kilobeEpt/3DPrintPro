<?php
/**
 * Telegram Webhook Endpoint
 * 
 * Receives updates from Telegram Bot API and handles:
 * - /start command - initiate authentication
 * - Password verification - authenticate user
 * - /stop command - unsubscribe user
 * - Other messages - provide help
 */

// Prevent direct browser access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
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
}

// Load TelegramBot class
require_once __DIR__ . '/../php/TelegramBot.php';

// Initialize bot
$bot = new TelegramBot();

// Get webhook secret for validation
$webhookSecret = getenv('TELEGRAM_WEBHOOK_SECRET');

// Validate webhook secret if set
if ($webhookSecret) {
    $receivedSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    
    if ($receivedSecret !== $webhookSecret) {
        http_response_code(403);
        logWebhook('ERROR', 'Invalid webhook secret');
        die('Forbidden');
    }
}

// Get update data
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// Log incoming update
logWebhook('INFO', 'Received update', [
    'update_id' => $update['update_id'] ?? null
]);

// Handle update
try {
    handleUpdate($update, $bot);
    http_response_code(200);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    logWebhook('ERROR', 'Error handling update', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

/**
 * Handle Telegram update
 */
function handleUpdate($update, $bot) {
    // Extract message
    $message = $update['message'] ?? null;
    
    if (!$message) {
        logWebhook('WARNING', 'No message in update');
        return;
    }
    
    $chatId = $message['chat']['id'] ?? null;
    $text = $message['text'] ?? null;
    $from = $message['from'] ?? [];
    
    if (!$chatId) {
        logWebhook('WARNING', 'No chat_id in message');
        return;
    }
    
    $userInfo = [
        'username' => $from['username'] ?? null,
        'first_name' => $from['first_name'] ?? null,
        'last_name' => $from['last_name'] ?? null
    ];
    
    logWebhook('INFO', 'Processing message', [
        'chat_id' => $chatId,
        'username' => $userInfo['username'] ?? 'unknown',
        'text' => $text
    ]);
    
    // Handle commands
    if ($text && strpos($text, '/') === 0) {
        handleCommand($text, $chatId, $userInfo, $bot);
        return;
    }
    
    // Handle password authentication
    if ($text) {
        handlePasswordAttempt($text, $chatId, $userInfo, $bot);
        return;
    }
    
    // Unknown message type
    $bot->sendMessage($chatId, 
        "❓ Я понимаю только текстовые сообщения.\n\n" .
        "Отправьте /start чтобы начать."
    );
}

/**
 * Handle commands (/start, /stop, etc.)
 */
function handleCommand($command, $chatId, $userInfo, $bot) {
    $command = strtolower(trim($command));
    
    switch ($command) {
        case '/start':
            handleStartCommand($chatId, $userInfo, $bot);
            break;
            
        case '/stop':
            handleStopCommand($chatId, $userInfo, $bot);
            break;
            
        case '/help':
            handleHelpCommand($chatId, $bot);
            break;
            
        case '/status':
            handleStatusCommand($chatId, $bot);
            break;
            
        default:
            $bot->sendMessage($chatId,
                "❓ Неизвестная команда.\n\n" .
                "Доступные команды:\n" .
                "/start - Начать работу\n" .
                "/stop - Отписаться\n" .
                "/help - Помощь\n" .
                "/status - Статус подписки"
            );
    }
}

/**
 * Handle /start command
 */
function handleStartCommand($chatId, $userInfo, $bot) {
    logWebhook('INFO', 'Start command', [
        'chat_id' => $chatId,
        'username' => $userInfo['username'] ?? 'unknown'
    ]);
    
    if ($bot->isAuthorized($chatId)) {
        $user = $bot->getUser($chatId);
        $name = $user['first_name'] ?? 'Пользователь';
        
        $bot->sendMessage($chatId,
            "✅ <b>Вы уже подписаны!</b>\n\n" .
            "Здравствуйте, {$name}!\n\n" .
            "Вы будете получать уведомления о новых заказах.\n\n" .
            "Команды:\n" .
            "/stop - Отписаться от уведомлений\n" .
            "/status - Проверить статус подписки"
        );
    } else {
        $bot->sendMessage($chatId,
            "👋 <b>Добро пожаловать!</b>\n\n" .
            "Для получения уведомлений о заказах введите пароль доступа.\n\n" .
            "🔑 Пароль можно получить у администратора."
        );
    }
}

/**
 * Handle /stop command
 */
function handleStopCommand($chatId, $userInfo, $bot) {
    logWebhook('INFO', 'Stop command', [
        'chat_id' => $chatId,
        'username' => $userInfo['username'] ?? 'unknown'
    ]);
    
    if ($bot->removeUser($chatId)) {
        $bot->sendMessage($chatId,
            "✅ <b>Вы успешно отписались</b>\n\n" .
            "Вы больше не будете получать уведомления о заказах.\n\n" .
            "Чтобы подписаться снова, отправьте /start"
        );
    } else {
        $bot->sendMessage($chatId,
            "ℹ️ Вы не были подписаны на уведомления.\n\n" .
            "Отправьте /start чтобы подписаться."
        );
    }
}

/**
 * Handle /help command
 */
function handleHelpCommand($chatId, $bot) {
    $isAuthorized = $bot->isAuthorized($chatId);
    
    $message = "<b>📖 Справка</b>\n\n";
    
    if ($isAuthorized) {
        $message .= "✅ Вы подписаны на уведомления о новых заказах.\n\n";
    } else {
        $message .= "❌ Вы не подписаны на уведомления.\n\n";
    }
    
    $message .= "<b>Доступные команды:</b>\n";
    $message .= "/start - Начать работу с ботом\n";
    $message .= "/stop - Отписаться от уведомлений\n";
    $message .= "/help - Показать эту справку\n";
    $message .= "/status - Проверить статус подписки\n\n";
    
    if (!$isAuthorized) {
        $message .= "Для подписки отправьте /start и введите пароль.";
    }
    
    $bot->sendMessage($chatId, $message);
}

/**
 * Handle /status command
 */
function handleStatusCommand($chatId, $bot) {
    $user = $bot->getUser($chatId);
    
    if ($user) {
        $message = "✅ <b>Статус подписки: Активна</b>\n\n";
        $message .= "👤 <b>Имя:</b> " . ($user['first_name'] ?? 'Не указано') . "\n";
        
        if ($user['username']) {
            $message .= "🆔 <b>Username:</b> @{$user['username']}\n";
        }
        
        $message .= "📅 <b>Подписан:</b> " . ($user['subscribed_at'] ?? 'Неизвестно') . "\n";
        $message .= "📨 <b>Последнее сообщение:</b> " . ($user['last_message'] ?? 'Неизвестно') . "\n";
    } else {
        $message = "❌ <b>Статус подписки: Не активна</b>\n\n";
        $message .= "Отправьте /start чтобы подписаться.";
    }
    
    $bot->sendMessage($chatId, $message);
}

/**
 * Handle password authentication attempt
 */
function handlePasswordAttempt($password, $chatId, $userInfo, $bot) {
    logWebhook('INFO', 'Password attempt', [
        'chat_id' => $chatId,
        'username' => $userInfo['username'] ?? 'unknown'
    ]);
    
    // Check if already authorized
    if ($bot->isAuthorized($chatId)) {
        $bot->sendMessage($chatId,
            "ℹ️ Вы уже подписаны на уведомления.\n\n" .
            "Отправьте /help для списка команд."
        );
        return;
    }
    
    // Try to authenticate
    if ($bot->authenticate($chatId, trim($password), $userInfo)) {
        $name = $userInfo['first_name'] ?? 'Пользователь';
        
        $bot->sendMessage($chatId,
            "✅ <b>Спасибо, вы подписаны!</b>\n\n" .
            "Здравствуйте, {$name}!\n\n" .
            "Теперь вы будете получать уведомления о всех новых заказах.\n\n" .
            "Команды:\n" .
            "/stop - Отписаться от уведомлений\n" .
            "/status - Проверить статус подписки\n" .
            "/help - Показать справку"
        );
        
        logWebhook('SUCCESS', 'User authenticated', [
            'chat_id' => $chatId,
            'username' => $userInfo['username'] ?? 'unknown'
        ]);
    } else {
        $bot->sendMessage($chatId,
            "❌ <b>Неверный пароль</b>\n\n" .
            "Попробуйте ещё раз или обратитесь к администратору.\n\n" .
            "Отправьте /start чтобы начать заново."
        );
        
        logWebhook('WARNING', 'Failed authentication', [
            'chat_id' => $chatId,
            'username' => $userInfo['username'] ?? 'unknown'
        ]);
    }
}

/**
 * Log webhook activity
 */
function logWebhook($level, $message, $context = []) {
    $logFile = __DIR__ . '/../storage/logs/telegram.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logMessage = "[{$timestamp}] WEBHOOK {$level}: {$message}{$contextStr}\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
