# Telegram Bot Password Authentication - Setup Guide

## Overview

A complete Telegram bot system with password-based authentication for receiving order notifications. Multiple users can subscribe after entering the correct password.

## Features

✅ Password-based authentication (password: `852789456`)  
✅ Multi-user support (broadcast to all authorized users)  
✅ Webhook integration with secret token validation  
✅ Comprehensive command handlers (/start, /stop, /help, /status)  
✅ Persistent user storage (JSON file)  
✅ Detailed logging of all activities  
✅ Error handling with automatic retry logic  
✅ User management CLI tools  

## Configuration

### Bot Credentials

- **Bot Token**: `8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI`
- **Password**: `852789456`
- **Webhook URL**: `https://3dprint-omsk.ru/telegram/webhook.php`

## Directory Structure

```
/
├── telegram/                      # Telegram bot scripts
│   ├── webhook.php               # Webhook endpoint (receives updates)
│   ├── setup-webhook.php         # Webhook configuration script
│   ├── test-notification.php     # Test notification sender
│   ├── test-system.php           # System validation tests
│   ├── manage-users.php          # User management CLI
│   ├── integration-example.php   # Integration examples
│   └── README.md                 # Detailed documentation
│
├── php/                          # PHP classes
│   └── TelegramBot.php          # Main bot class
│
├── storage/                      # Data storage
│   ├── data/                    # User data
│   │   ├── telegram_users.json  # Authorized users (auto-created)
│   │   └── .gitignore          # Protects user data
│   └── logs/                    # Log files
│       ├── telegram.log         # Bot activity log (auto-created)
│       └── .gitignore          # Ignores log files
│
└── .env                          # Environment configuration
```

## Setup Instructions

### Step 1: Verify Files

All files should be in place. Verify structure:

```bash
ls -la telegram/
ls -la php/
ls -la storage/data/
ls -la storage/logs/
```

### Step 2: Configure Environment

The `.env` file is already configured with:

```env
TELEGRAM_BOT_TOKEN=8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI
TELEGRAM_PASSWORD=852789456
TELEGRAM_WEBHOOK_SECRET=
APP_URL=https://3dprint-omsk.ru
```

### Step 3: Set Up Webhook

Run the webhook setup script:

```bash
php telegram/setup-webhook.php
```

This will:
1. Generate a secure webhook secret token
2. Save it to `.env` file
3. Register the webhook with Telegram
4. Verify the webhook is working

Expected output:
```
====================================
Telegram Webhook Setup
====================================

Generating webhook secret...
✓ Webhook secret generated and saved to .env

Webhook URL: https://3dprint-omsk.ru/telegram/webhook.php
Bot Token: 8241807858:AAE0JXxW...

Setting up webhook...
✓ Webhook set successfully!

Verifying webhook...

Webhook Info:
  URL: https://3dprint-omsk.ru/telegram/webhook.php
  Has custom certificate: No
  Pending updates: 0

✓ Webhook verification complete

====================================
Setup Complete!
====================================
```

### Step 4: Authenticate First User

1. Open Telegram
2. Find your bot (search by bot username or token)
3. Send `/start` command
4. Bot will respond: "Для получения уведомлений о заказах введите пароль доступа"
5. Send the password: `852789456`
6. Bot will confirm: "✅ Спасибо, вы подписаны!"

### Step 5: Test Notifications

Send a test notification to all authorized users:

```bash
php telegram/test-notification.php
```

Expected output:
```
====================================
Telegram Notification Test
====================================

Found 1 authorized user(s)

  • John (No username) - Chat ID: 123456789

Sending test notification...

Results:
  ✓ John (Chat ID: 123456789) - Sent successfully

====================================
Summary:
  ✓ Sent: 1
  ✗ Failed: 0
  Total: 1
====================================

✓ Test notification sent successfully!
```

### Step 6: Verify System (Optional)

Run comprehensive system tests:

```bash
php telegram/test-system.php
```

This validates:
- Environment configuration
- Directory structure
- File permissions
- TelegramBot class
- Data file creation
- Authentication logic
- User data management
- Webhook configuration
- Logging system
- Required files

## User Commands

Users can interact with the bot using these commands:

### `/start`
Start using the bot. If not authenticated, prompts for password. If already authenticated, shows subscription status.

**Response (not authenticated):**
```
👋 Добро пожаловать!

Для получения уведомлений о заказах введите пароль доступа.

🔑 Пароль можно получить у администратора.
```

**Response (already authenticated):**
```
✅ Вы уже подписаны!

Здравствуйте, John!

Вы будете получать уведомления о новых заказах.

Команды:
/stop - Отписаться от уведомлений
/status - Проверить статус подписки
```

### `/stop`
Unsubscribe from notifications. Removes user from authorized list.

**Response:**
```
✅ Вы успешно отписались

Вы больше не будете получать уведомления о заказах.

Чтобы подписаться снова, отправьте /start
```

### `/help`
Show help message with available commands.

**Response:**
```
📖 Справка

✅ Вы подписаны на уведомления о новых заказах.

Доступные команды:
/start - Начать работу с ботом
/stop - Отписаться от уведомлений
/help - Показать эту справку
/status - Проверить статус подписки
```

### `/status`
Check subscription status and user information.

**Response:**
```
✅ Статус подписки: Активна

👤 Имя: John
🆔 Username: @john_doe
📅 Подписан: 2025-01-25 14:00:00
📨 Последнее сообщение: 2025-01-25 14:05:00
```

## Management Commands

### List All Users

```bash
php telegram/manage-users.php list
```

Shows all authorized users with details:
```
====================================
Authorized Telegram Users
====================================

Total users: 2

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Name: John Doe
  Username: @john_doe
  Chat ID: 123456789
  Subscribed: 2025-01-25 14:00:00
  Last message: 2025-01-25 14:05:00
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Name: Jane Smith
  Username: No username
  Chat ID: 987654321
  Subscribed: 2025-01-25 15:30:00
  Last message: 2025-01-25 15:35:00
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### Show User Info

```bash
php telegram/manage-users.php info <chat_id>
```

Example:
```bash
php telegram/manage-users.php info 123456789
```

Output:
```
====================================
User Information
====================================

  Chat id: 123456789
  Username: john_doe
  First name: John
  Last name: Doe
  Authenticated: Yes
  Subscribed at: 2025-01-25 14:00:00
  Last message: 2025-01-25 14:05:00
```

### Remove User

```bash
php telegram/manage-users.php remove <chat_id>
```

Example:
```bash
php telegram/manage-users.php remove 123456789
```

Output:
```
Removing user with Chat ID: 123456789...
✓ User 'John' removed successfully
✓ Notification sent to user
```

The user will receive a message:
```
ℹ️ Вы были отписаны от уведомлений администратором.

Чтобы подписаться снова, отправьте /start
```

## Integration with Order Processing

### Basic Integration

Add to your order processing code (e.g., `contact.php`):

```php
<?php
// Load environment and bot class
require_once __DIR__ . '/php/TelegramBot.php';

// After successfully processing an order
$bot = new TelegramBot();
$bot->sendOrderNotification([
    'orderNumber' => 'ORD-12345',
    'clientName' => $_POST['name'],
    'clientPhone' => $_POST['phone'],
    'clientEmail' => $_POST['email'],
    'service' => '3D печать FDM',
    'amount' => 2500,
    'details' => $_POST['message']
]);
```

### Graceful Error Handling

Don't let notification failures break order processing:

```php
try {
    $bot = new TelegramBot();
    $bot->sendOrderNotification($orderData);
} catch (Exception $e) {
    // Log error but continue
    error_log("Telegram notification failed: " . $e->getMessage());
}
```

### Custom Notifications

Send custom formatted messages:

```php
$bot = new TelegramBot();

// Broadcast to all users
$message = "🎉 <b>Специальное предложение!</b>\n\n";
$message .= "Скидка 20% на все услуги до конца недели!";
$bot->broadcastMessage($message);

// Send to specific user
$bot->sendMessage($chatId, "Ваш заказ готов!");
```

## Data Storage

### telegram_users.json

Located at `storage/data/telegram_users.json` (auto-created):

```json
{
  "123456789": {
    "chat_id": 123456789,
    "username": "john_doe",
    "first_name": "John",
    "last_name": "Doe",
    "authenticated": true,
    "subscribed_at": "2025-01-25 14:00:00",
    "last_message": "2025-01-25 14:05:00"
  },
  "987654321": {
    "chat_id": 987654321,
    "username": null,
    "first_name": "Jane",
    "last_name": "Smith",
    "authenticated": true,
    "subscribed_at": "2025-01-25 15:30:00",
    "last_message": "2025-01-25 15:35:00"
  }
}
```

### telegram.log

Located at `storage/logs/telegram.log` (auto-created):

```
[2025-01-25 14:00:00] INFO: User authenticated successfully | {"chat_id":123456789,"username":"john_doe"}
[2025-01-25 14:05:00] INFO: Message sent successfully | {"chat_id":123456789}
[2025-01-25 14:10:00] INFO: Broadcasting message | {"recipients_count":2}
[2025-01-25 14:10:00] INFO: Message sent successfully | {"chat_id":123456789}
[2025-01-25 14:10:01] INFO: Message sent successfully | {"chat_id":987654321}
[2025-01-25 15:30:00] WARNING: Failed authentication attempt | {"chat_id":555555555,"username":"unknown"}
```

## Security Features

1. **Password Protection**: Only users with correct password can subscribe
2. **Webhook Secret**: Validates requests are from Telegram (not third parties)
3. **Rate Limiting**: Built-in delays prevent API spam
4. **Comprehensive Logging**: All actions logged for audit
5. **Error Handling**: Graceful failures with retry logic
6. **Data Protection**: `.gitignore` prevents committing user data

## Troubleshooting

### Webhook Not Set

**Problem**: Webhook URL is empty

**Solution**:
```bash
php telegram/setup-webhook.php
```

### Notifications Not Received

**Check 1**: Are there authorized users?
```bash
php telegram/manage-users.php list
```

**Check 2**: Test notifications
```bash
php telegram/test-notification.php
```

**Check 3**: Check logs
```bash
tail -50 storage/logs/telegram.log
```

### Authentication Fails

**Check password** in `.env`:
```bash
grep TELEGRAM_PASSWORD .env
```

Should show: `TELEGRAM_PASSWORD=852789456`

**Check logs** for failed attempts:
```bash
grep "Failed authentication" storage/logs/telegram.log
```

### Permission Errors

**Fix storage permissions**:
```bash
chmod 755 storage/data storage/logs
chmod 644 storage/data/.gitignore storage/logs/.gitignore
```

### Bot Not Responding

**Check 1**: Verify bot token in `.env`
```bash
grep TELEGRAM_BOT_TOKEN .env
```

**Check 2**: Verify webhook is set
```bash
php telegram/setup-webhook.php
```

**Check 3**: Ensure webhook URL is publicly accessible
- URL must be HTTPS
- Server must be reachable from internet
- PHP must be able to execute webhook.php

## API Reference

See `telegram/README.md` for complete API documentation including:
- TelegramBot class methods
- Method parameters and return values
- Usage examples
- Integration patterns

## Maintenance

### View Recent Logs

```bash
tail -100 storage/logs/telegram.log
```

### Backup User Data

```bash
cp storage/data/telegram_users.json storage/data/telegram_users.backup.json
```

### Clear Old Logs

```bash
# Keep last 1000 lines
tail -1000 storage/logs/telegram.log > /tmp/telegram.log.tmp
mv /tmp/telegram.log.tmp storage/logs/telegram.log
```

### Monitor Active Users

```bash
php telegram/manage-users.php list | grep "Total users"
```

## Success Criteria

✅ **Webhook configured**: `php telegram/setup-webhook.php` runs successfully  
✅ **User authentication**: `/start` command works, password accepted  
✅ **Notifications sent**: `php telegram/test-notification.php` delivers messages  
✅ **Logging active**: `storage/logs/telegram.log` contains entries  
✅ **Data persistence**: `storage/data/telegram_users.json` stores users  
✅ **Commands working**: `/stop`, `/help`, `/status` respond correctly  
✅ **Management tools**: `manage-users.php list` shows authorized users  
✅ **Error handling**: Failed API calls retry automatically  

## Support

For detailed API documentation and integration examples, see:
- `telegram/README.md` - Complete bot documentation
- `telegram/integration-example.php` - Code examples
- `storage/logs/telegram.log` - Activity logs

## License

Part of 3D Print Pro system.
