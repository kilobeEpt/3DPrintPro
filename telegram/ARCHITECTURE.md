# Telegram Bot Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     TELEGRAM BOT SYSTEM                         │
│                  Password-Based Authentication                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐       ┌──────────────────┐      ┌─────────────┐
│  Telegram User  │◄─────►│  Telegram API    │◄────►│  Webhook    │
│                 │       │  Bot Server      │      │  Endpoint   │
└─────────────────┘       └──────────────────┘      └─────────────┘
                                                            │
                                                            ▼
                                                    ┌─────────────┐
                                                    │ TelegramBot │
                                                    │   Class     │
                                                    └─────────────┘
                                                            │
                                ┌───────────────────────────┼───────────────┐
                                ▼                           ▼               ▼
                        ┌──────────────┐          ┌──────────────┐  ┌──────────┐
                        │    Storage   │          │   Logging    │  │  Order   │
                        │ telegram_    │          │  telegram.   │  │Processing│
                        │ users.json   │          │    log       │  │  System  │
                        └──────────────┘          └──────────────┘  └──────────┘
```

## Component Flow

### 1. User Authentication Flow

```
User                  Bot                     Storage
 │                     │                        │
 │──── /start ────────►│                        │
 │                     │                        │
 │◄── "Enter password" │                        │
 │                     │                        │
 │──── 852789456 ─────►│                        │
 │                     │                        │
 │                     ├─── Verify Password     │
 │                     │                        │
 │                     ├─── Save User ─────────►│
 │                     │                        │
 │                     │◄─── Confirm Save ──────┤
 │                     │                        │
 │◄── "Subscribed!" ───┤                        │
 │                     │                        │
```

### 2. Order Notification Flow

```
Order Form          TelegramBot           Telegram API        Users
    │                   │                      │              │
    │── New Order ─────►│                      │              │
    │                   │                      │              │
    │                   ├── Get Authorized     │              │
    │                   │   Users              │              │
    │                   │                      │              │
    │                   ├── Format Message     │              │
    │                   │                      │              │
    │                   ├── Broadcast ────────►│              │
    │                   │                      │              │
    │                   │                      ├─ Send ──────►│
    │                   │                      │              │
    │                   │                      ├─ Send ──────►│
    │                   │                      │              │
    │                   │◄──── Responses ──────┤              │
    │                   │                      │              │
    │◄── Success ───────┤                      │              │
    │                   │                      │              │
```

### 3. Command Handling Flow

```
User Input           Webhook              Command Handler         Response
    │                   │                      │                    │
    │──── /start ───────►│                      │                    │
    │                   ├──── Parse Command ───►│                    │
    │                   │                      │                    │
    │                   │                      ├─ Check Auth       │
    │                   │                      │                    │
    │                   │                      ├─ Generate Reply   │
    │                   │                      │                    │
    │                   │◄──── Response ───────┤                    │
    │                   │                      │                    │
    │                   ├──── Send Message ────────────────────────►│
    │                   │                      │                    │
    │◄──────────────────────────────────────────────────────────────┤
    │                   │                      │                    │
```

## File Structure

```
project/
│
├── telegram/                          # Bot scripts directory
│   ├── webhook.php                   # Main webhook endpoint
│   │   ├── Receives POST from Telegram
│   │   ├── Validates webhook secret
│   │   ├── Parses update (message, command)
│   │   ├── Calls command handlers
│   │   └── Logs all activity
│   │
│   ├── setup-webhook.php             # Webhook configuration
│   │   ├── Generates secret token
│   │   ├── Saves to .env
│   │   ├── Registers with Telegram
│   │   └── Verifies setup
│   │
│   ├── test-notification.php         # Test notification sender
│   │   ├── Creates test order
│   │   ├── Sends to all users
│   │   └── Reports results
│   │
│   ├── test-system.php               # System validation
│   │   ├── Checks environment
│   │   ├── Tests authentication
│   │   ├── Verifies webhook
│   │   └── Validates storage
│   │
│   ├── manage-users.php              # User management CLI
│   │   ├── list - Show all users
│   │   ├── info - Show user details
│   │   └── remove - Delete user
│   │
│   ├── integration-example.php       # Integration patterns
│   │   └── Code examples for order processing
│   │
│   ├── README.md                     # Detailed documentation
│   └── ARCHITECTURE.md               # This file
│
├── php/
│   └── TelegramBot.php               # Main bot class
│       ├── authenticate()            # Verify password, save user
│       ├── getAuthorizedUsers()      # Get chat IDs
│       ├── sendMessage()             # Send to one user
│       ├── broadcastMessage()        # Send to all users
│       ├── removeUser()              # Unsubscribe user
│       ├── sendOrderNotification()   # Format and send order
│       ├── setWebhook()              # Configure webhook
│       └── getWebhookInfo()          # Check webhook status
│
├── storage/
│   ├── data/
│   │   ├── telegram_users.json       # User storage
│   │   │   {
│   │   │     "123456789": {
│   │   │       "chat_id": 123456789,
│   │   │       "username": "john_doe",
│   │   │       "first_name": "John",
│   │   │       "authenticated": true,
│   │   │       "subscribed_at": "2025-01-25 14:00:00",
│   │   │       "last_message": "2025-01-25 14:05:00"
│   │   │     }
│   │   │   }
│   │   └── .gitignore                # Protect user data
│   │
│   └── logs/
│       ├── telegram.log               # Activity log
│       │   [2025-01-25 14:00:00] INFO: User authenticated | {"chat_id":123456789}
│       │   [2025-01-25 14:05:00] INFO: Message sent | {"chat_id":123456789}
│       └── .gitignore                 # Ignore logs
│
└── .env                               # Configuration
    TELEGRAM_BOT_TOKEN=8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI
    TELEGRAM_PASSWORD=852789456
    TELEGRAM_WEBHOOK_SECRET=<auto-generated>
    APP_URL=https://3dprint-omsk.ru
```

## Security Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    SECURITY LAYERS                          │
└─────────────────────────────────────────────────────────────┘

Layer 1: Webhook Secret Validation
┌────────────────────────────────────┐
│ Telegram API sends secret token   │
│ Webhook validates before processing│
└────────────────────────────────────┘
                 │
                 ▼
Layer 2: Password Authentication
┌────────────────────────────────────┐
│ Users must provide correct password│
│ Only authenticated users saved     │
└────────────────────────────────────┘
                 │
                 ▼
Layer 3: Data Protection
┌────────────────────────────────────┐
│ User data in JSON (not committed)  │
│ Logs excluded from version control │
└────────────────────────────────────┘
                 │
                 ▼
Layer 4: Error Handling
┌────────────────────────────────────┐
│ Graceful failures (no data loss)   │
│ Retry logic for API failures       │
│ Comprehensive logging for audit    │
└────────────────────────────────────┘
```

## Data Flow Diagram

```
┌──────────────┐
│  New Order   │
│   Created    │
└──────┬───────┘
       │
       ▼
┌──────────────────────┐
│ TelegramBot::        │
│ sendOrderNotification│
│ (order data)         │
└──────┬───────────────┘
       │
       ├─── Format Message
       │    (formatOrderMessage)
       │
       ├─── Get Authorized Users
       │    (getAuthorizedUsers)
       │    │
       │    └──► Read from telegram_users.json
       │
       ▼
┌──────────────────────┐
│ broadcastMessage()   │
│ (to all users)       │
└──────┬───────────────┘
       │
       ├─── For each chat_id:
       │    │
       │    ├──► sendMessage(chatId, message)
       │    │    │
       │    │    ├──► POST to Telegram API
       │    │    │
       │    │    ├──► Update last_message timestamp
       │    │    │
       │    │    └──► Log result
       │    │
       │    └──► 100ms delay (rate limiting)
       │
       ▼
┌──────────────────────┐
│   Return Results     │
│ {chatId: response}   │
└──────────────────────┘
```

## Command Processing Pipeline

```
Incoming Update
      │
      ▼
┌─────────────────┐
│ webhook.php     │
│ - Validate      │
│ - Parse JSON    │
└────────┬────────┘
         │
         ├─── Is Command? (/start, /stop, etc.)
         │    │
         │    └──► handleCommand()
         │         │
         │         ├─── /start ──► handleStartCommand()
         │         │                 │
         │         │                 ├─── Check if authorized
         │         │                 │
         │         │                 ├─── Send appropriate message
         │         │                 │
         │         │                 └─── Log action
         │         │
         │         ├─── /stop ───► handleStopCommand()
         │         │                 │
         │         │                 ├─── removeUser(chatId)
         │         │                 │
         │         │                 ├─── Send confirmation
         │         │                 │
         │         │                 └─── Log action
         │         │
         │         ├─── /help ───► handleHelpCommand()
         │         │
         │         └─── /status ─► handleStatusCommand()
         │
         └─── Plain Text?
              │
              └──► handlePasswordAttempt()
                   │
                   ├─── Check if authorized
                   │    │
                   │    └─── Already authorized? → Info message
                   │
                   ├─── Try authenticate(chatId, password)
                   │    │
                   │    ├─── Success → Save user, confirm
                   │    │
                   │    └─── Failure → Reject, log attempt
                   │
                   └─── Log result
```

## Integration Points

### 1. Order Form Submission

```php
// In contact.php or order handler
require_once 'php/TelegramBot.php';

$bot = new TelegramBot();
$bot->sendOrderNotification([
    'orderNumber' => $orderNumber,
    'clientName' => $_POST['name'],
    'clientPhone' => $_POST['phone'],
    'clientEmail' => $_POST['email'],
    'service' => $_POST['service'],
    'amount' => $amount,
    'details' => $_POST['message']
]);
```

### 2. Custom Notifications

```php
$bot = new TelegramBot();
$bot->broadcastMessage("🎉 Special announcement!");
```

### 3. Check Subscription Status

```php
$bot = new TelegramBot();
if ($bot->isAuthorized($chatId)) {
    // User is subscribed
}
```

## API Endpoints

### Telegram API (Used by Bot)

- `POST https://api.telegram.org/bot{token}/sendMessage`
  - Send message to user
  - Used by: `TelegramBot::sendMessage()`

- `POST https://api.telegram.org/bot{token}/setWebhook`
  - Configure webhook URL
  - Used by: `TelegramBot::setWebhook()`

- `GET https://api.telegram.org/bot{token}/getWebhookInfo`
  - Check webhook status
  - Used by: `TelegramBot::getWebhookInfo()`

### Webhook Endpoint (Receives Updates)

- `POST https://3dprint-omsk.ru/telegram/webhook.php`
  - Receives updates from Telegram
  - Validates secret token
  - Processes commands and messages
  - Handled by: `webhook.php`

## State Management

```
User States:

┌──────────────┐
│ Unregistered │ (Default state)
└──────┬───────┘
       │
       │ /start command
       │
       ▼
┌──────────────┐
│  Awaiting    │ (Waiting for password)
│  Password    │
└──────┬───────┘
       │
       │ Correct password
       │
       ▼
┌──────────────┐
│ Authenticated│ (Receives notifications)
│  & Subscribed│
└──────┬───────┘
       │
       │ /stop command
       │
       ▼
┌──────────────┐
│ Unsubscribed │ (Can re-subscribe with /start)
└──────────────┘
```

## Error Handling Strategy

```
API Request
    │
    ▼
┌────────────────┐
│  Try Request   │
└────┬───────────┘
     │
     ├─── Success? ──► Return result
     │
     └─── Failure? ──► Retry Logic
                       │
                       ├─── Attempt 1 (1s delay)
                       │
                       ├─── Attempt 2 (2s delay)
                       │
                       ├─── Attempt 3 (3s delay)
                       │
                       └─── Max retries reached
                            │
                            ├─── Log error
                            │
                            └─── Return failure (graceful)
```

## Logging Format

```
[TIMESTAMP] LEVEL: MESSAGE | CONTEXT

Examples:
[2025-01-25 14:00:00] INFO: User authenticated successfully | {"chat_id":123456789,"username":"john_doe"}
[2025-01-25 14:05:00] INFO: Message sent successfully | {"chat_id":123456789}
[2025-01-25 14:10:00] INFO: Broadcasting message | {"recipients_count":2}
[2025-01-25 14:15:00] WARNING: Failed authentication attempt | {"chat_id":555555555,"username":"unknown"}
[2025-01-25 14:20:00] ERROR: API request failed: sendMessage | {"error":"Bad Request","retry":0}
[2025-01-25 14:20:01] INFO: User unsubscribed | {"chat_id":123456789}
[2025-01-25 14:25:00] WEBHOOK INFO: Received update | {"update_id":123456}
[2025-01-25 14:25:00] WEBHOOK SUCCESS: User authenticated | {"chat_id":789012345,"username":"new_user"}
```

## Deployment Checklist

- [x] Create directory structure
- [x] Upload TelegramBot.php class
- [x] Upload webhook.php endpoint
- [x] Configure .env file
- [x] Set storage permissions (755)
- [x] Run setup-webhook.php
- [x] Test with /start command
- [x] Verify notification delivery
- [x] Check logging is working
- [x] Review security (webhook secret, password)

## Maintenance Tasks

### Daily
- Monitor storage/logs/telegram.log for errors
- Check active user count

### Weekly
- Review failed authentication attempts
- Verify webhook is still active
- Test notifications with test-notification.php

### Monthly
- Backup telegram_users.json
- Archive old logs (keep last 10000 lines)
- Review and optimize if needed

### On Demand
- Add/remove users manually via manage-users.php
- Investigate delivery failures in logs
- Update webhook if URL changes
