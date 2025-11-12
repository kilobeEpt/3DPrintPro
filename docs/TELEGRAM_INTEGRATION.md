# Telegram Integration Guide

Complete guide for configuring and using the Telegram notification system.

## Overview

The Telegram integration allows automatic notifications for:
- ✅ New order submissions
- ✅ Order status changes

All settings are managed through the admin panel with secure credential storage.

## Features

✅ **Admin-Controlled Configuration**
- Bot token and chat ID stored in database
- Secure token handling (never exposed in frontend)
- Test connection button to verify setup

✅ **Granular Notification Control**
- Toggle notifications for new orders
- Toggle notifications for status changes
- Real-time configuration updates

✅ **Security**
- Bot token masked in admin UI
- Tokens sanitized in logs (never logged in plaintext)
- Admin authentication required for all settings

✅ **Error Handling**
- Graceful fallback when Telegram unavailable
- Detailed error logging
- User-friendly error messages

## Setup Instructions

### 1. Create a Telegram Bot

1. Open Telegram and search for `@BotFather`
2. Send `/newbot` command
3. Follow the prompts to create your bot
4. Copy the bot token (format: `123456:ABC-DEF...`)

### 2. Get Your Chat ID

**Option A: Using a Group Chat (Recommended)**
1. Create a new Telegram group
2. Add your bot to the group
3. Make the bot an admin (required for posting)
4. Send a message to the group
5. Open this URL in your browser (replace `{TOKEN}` with your bot token):
   ```
   https://api.telegram.org/bot{TOKEN}/getUpdates
   ```
6. Look for `"chat":{"id":-1001234567890}` in the response
7. Copy the chat ID (includes the minus sign)

**Option B: Using Direct Messages**
1. Start a conversation with your bot (`/start`)
2. Send any message
3. Open the getUpdates URL (see above)
4. Look for your chat ID in the response

### 3. Configure in Admin Panel

1. Log in to the admin panel
2. Go to **Settings** page
3. Fill in the Telegram configuration:
   - **Bot Token**: Paste your bot token
   - **Chat ID**: Paste your chat ID
   - **Contact URL**: Public link to your bot (e.g., `https://t.me/YourBot`)
4. Click **Save Changes**
5. Click **Send Test Message** to verify the connection

### 4. Configure Notifications

In the same Settings page, you can toggle:
- ✅ **Notify about new orders** - Sends notification when a customer submits an order
- ✅ **Notify about status changes** - Sends notification when order status changes

## Notification Examples

### New Order Notification

```
🆕 НОВАЯ ЗАЯВКА #ORD-20250119-A1B2C3

🆔 ID: 42
👤 Имя: Иван Петров
📱 Телефон: +7 (999) 123-45-67
📧 Email: ivan@example.com
💬 Telegram: @ivanpetrov

🛠 Услуга: FDM печать
💰 Сумма: 1 500 ₽

📊 Детали расчета:
• Технология: FDM
• Материал: PLA
• Вес: 100 г
• Количество: 2 шт
• Заполнение: 20%
• Качество: Нормальное
• Срок: 2-3 дня

💬 Сообщение:
Нужна печать корпуса для прототипа

⏰ Дата: 19.01.2025 14:30
🌐 Сайт: https://your-site.com
```

### Status Change Notification

```
🔔 ИЗМЕНЕНИЕ СТАТУСА ЗАКАЗА

📋 Заказ: #ORD-20250119-A1B2C3
🆔 ID: 42

📊 Статус изменен:
   🆕 Новая → 🔄 В работе

⏰ Дата: 19.01.2025 15:45
```

## API Reference

### Telegram Test Endpoint

**Endpoint:** `POST /api/telegram-test.php`

**Authentication:** Admin session required

**Rate Limit:** 60 requests per minute

**Request:**
```bash
curl -X POST https://your-site.com/api/telegram-test.php \
  -H "Cookie: PHPSESSID=..." \
  -H "X-CSRF-Token: ..."
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "message": "Test message sent successfully"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Bot token not configured"
}
```

### Notification Flow

1. **New Order** (`POST /api/orders.php`)
   - Order created in database
   - `TelegramHelper::sendOrderNotification()` called
   - If enabled: notification sent to Telegram
   - Result logged in `telegram_sent` and `telegram_error` fields

2. **Status Change** (`PUT /api/orders.php`)
   - Order status updated
   - If status changed: `TelegramHelper::sendStatusChangeNotification()` called
   - If enabled: notification sent to Telegram
   - Result logged and returned in response

## Settings Database Schema

Settings are stored in the `settings` table with these keys:

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `telegram_bot_token` | string | `''` | Bot token from BotFather |
| `telegram_chat_id` | string | `''` | Target chat ID for notifications |
| `telegram_contact_url` | string | `'https://t.me/...'` | Public contact link |
| `telegram_notify_new_order` | string | `'1'` | Enable new order notifications |
| `telegram_notify_status_change` | string | `'1'` | Enable status change notifications |

## Security Considerations

### Token Protection

1. **Never in Frontend**
   - Bot token is NEVER exposed in JavaScript
   - `config.js` has empty placeholder only
   - Token only used server-side

2. **Admin-Only Access**
   - Settings API requires admin authentication
   - CSRF protection on all write operations
   - Rate limiting to prevent abuse

3. **Log Sanitization**
   - Bot tokens automatically redacted in logs
   - Search patterns: `token`, `bot_token`, `telegram_bot_token`
   - Replaced with `***REDACTED***`

4. **Masked in UI**
   - Token input field uses `type="password"`
   - Toggle button to show/hide token
   - Auto-hides on page load

### Fallback Behavior

If Telegram is unavailable or misconfigured:
- Orders are still created successfully
- Error logged but doesn't block order submission
- `telegram_sent` set to `0`
- `telegram_error` contains error message
- Admin can check error in order details

## Troubleshooting

### Test Message Fails

**Error: "Bot token not configured"**
- Verify token is saved in admin settings
- Check database: `SELECT * FROM settings WHERE setting_key = 'telegram_bot_token'`
- Token should be non-empty string

**Error: "Chat ID not configured"**
- Verify chat ID is saved in admin settings
- Check database: `SELECT * FROM settings WHERE setting_key = 'telegram_chat_id'`
- Chat ID should include minus sign for groups

**Error: "Telegram API error: Unauthorized"**
- Bot token is invalid
- Create a new bot or regenerate token

**Error: "Telegram API error: Chat not found"**
- Chat ID is incorrect
- Verify using getUpdates URL
- For groups: ensure bot is still a member

**Error: "Telegram API error: Bot was blocked by the user"**
- User blocked the bot
- Unblock the bot in Telegram

**Error: "Telegram API error: Bot is not a member of the group chat"**
- Bot removed from group
- Add bot back to group
- Make bot an admin

### Notifications Not Arriving

1. **Check if enabled:**
   - Go to Settings → Notification settings
   - Verify checkboxes are ticked

2. **Check order details:**
   - Go to Orders → View order
   - Check `telegram_sent` field
   - Check `telegram_error` field for errors

3. **Check logs:**
   ```bash
   tail -f logs/api.log | grep -i telegram
   ```

4. **Test connection:**
   - Go to Settings
   - Click "Send Test Message"
   - Check Telegram for message

### Rate Limiting

If you see `429 Too Many Requests`:
- Telegram bot API has limits
- Wait a few seconds and try again
- Contact @BotSupport if persistent

## Maintenance

### Changing Bot Token

1. Create a new bot or regenerate token
2. Update in admin settings
3. Test connection
4. Old token will stop working immediately

### Changing Chat ID

1. Get new chat ID (see Setup Instructions)
2. Update in admin settings
3. Test connection
4. Notifications will go to new chat

### Monitoring

Check logs regularly for errors:
```bash
grep -i "telegram" logs/api.log
```

Look for patterns:
- `Telegram notification failed`
- `Telegram API error`
- `Bot token not configured`

## Code Architecture

### PHP Classes

**`TelegramHelper`** (`api/helpers/telegram.php`)
- `getCredentials($db)` - Fetch token/chat ID from DB
- `isNotificationEnabled($db, $eventType)` - Check if notification type enabled
- `sendOrderNotification($data, $orderNumber, $orderId, $db)` - Send new order notification
- `sendStatusChangeNotification($orderId, $orderNumber, $oldStatus, $newStatus, $db)` - Send status change notification
- `sendTestMessage($db)` - Send test message
- `buildOrderMessage($data, $orderNumber, $orderId)` - Format order message
- `buildStatusChangeMessage($orderId, $orderNumber, $oldStatus, $newStatus)` - Format status change message
- `sendMessage($botToken, $chatId, $message)` - Send to Telegram API

### JavaScript Modules

**`SettingsModule`** (`admin/js/modules/settings.js`)
- Load settings from API
- Toggle token visibility
- Test Telegram connection
- Save settings with checkbox handling

### Admin UI

**`admin/settings.php`**
- Telegram configuration form
- Token visibility toggle
- Test connection button
- Notification toggles
- Email settings

## Best Practices

1. ✅ **Use Group Chats**
   - Multiple admins can receive notifications
   - History preserved if admin leaves
   - Easier to manage team access

2. ✅ **Test After Changes**
   - Always test after changing credentials
   - Verify notifications for both order types
   - Check error messages

3. ✅ **Monitor Logs**
   - Check logs daily for errors
   - Set up log rotation
   - Alert on repeated failures

4. ✅ **Secure Access**
   - Keep admin passwords strong
   - Use HTTPS for admin panel
   - Rotate credentials periodically

5. ✅ **Backup Settings**
   - Include settings table in backups
   - Document chat IDs in secure location
   - Keep bot token in password manager

## Migration from Old System

If upgrading from hardcoded config:

1. **Copy credentials:**
   - Get `TELEGRAM_BOT_TOKEN` from `api/config.php`
   - Get `TELEGRAM_CHAT_ID` from `api/config.php`

2. **Update admin settings:**
   - Paste token and chat ID
   - Save changes
   - Test connection

3. **Remove from config (optional):**
   - Can keep in `api/config.php` as fallback
   - Database settings take priority
   - Comment out to ensure DB is used

4. **Update frontend:**
   - `config.js` should have empty token
   - Contact URL can be in config or DB
   - DB setting overrides config

## Support

For issues or questions:
- Check logs: `logs/api.log`
- Test endpoint: `POST /api/telegram-test.php`
- Telegram API docs: https://core.telegram.org/bots/api
- Bot support: @BotSupport on Telegram

## Version History

- **v2.0** (January 2025) - Admin panel integration, database-driven config
- **v1.0** (Initial) - Hardcoded config in `api/config.php`
