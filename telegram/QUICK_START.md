# Telegram Bot - Quick Start Guide

## 🚀 Get Started in 3 Minutes

### Prerequisites
- Bot Token: `8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI`
- Password: `852789456`
- Website: `https://3dprint-omsk.ru`

### Step 1: Setup Webhook (30 seconds)

```bash
php telegram/setup-webhook.php
```

✅ Expected: "✓ Webhook set successfully!"

### Step 2: Authenticate First User (1 minute)

1. Open Telegram
2. Find your bot
3. Send: `/start`
4. Send: `852789456`
5. See: "✅ Спасибо, вы подписаны!"

### Step 3: Test Notification (30 seconds)

```bash
php telegram/test-notification.php
```

✅ Expected: Message received in Telegram

## 📱 User Commands

| Command | Description |
|---------|-------------|
| `/start` | Subscribe (asks for password) |
| `/stop` | Unsubscribe from notifications |
| `/help` | Show help message |
| `/status` | Check subscription status |

## 🛠 Management Commands

```bash
# List all users
php telegram/manage-users.php list

# Show user details
php telegram/manage-users.php info <chat_id>

# Remove user
php telegram/manage-users.php remove <chat_id>
```

## 💻 Send Notification from Code

```php
<?php
require_once 'php/TelegramBot.php';

$bot = new TelegramBot();

// Send order notification to all authorized users
$bot->sendOrderNotification([
    'orderNumber' => 'ORD-12345',
    'clientName' => 'Иван Иванов',
    'clientPhone' => '+7 (913) 123-45-67',
    'clientEmail' => 'ivan@example.com',
    'service' => '3D печать FDM',
    'amount' => 2500,
    'details' => 'Печать детали из PLA'
]);

// Or send custom message
$bot->broadcastMessage("🎉 Специальное предложение!");
```

## 📂 Files

| File | Purpose |
|------|---------|
| `php/TelegramBot.php` | Main bot class |
| `telegram/webhook.php` | Receives Telegram updates |
| `telegram/setup-webhook.php` | Configure webhook |
| `telegram/test-notification.php` | Test sending |
| `telegram/manage-users.php` | User management |
| `storage/data/telegram_users.json` | User storage |
| `storage/logs/telegram.log` | Activity log |

## 🔒 Security

✅ Password required: `852789456`  
✅ Webhook secret: Auto-generated  
✅ User data protected: `.gitignore`  
✅ Comprehensive logging  
✅ Retry logic for failures  

## 🐛 Troubleshooting

### Webhook not working?
```bash
php telegram/setup-webhook.php
```

### No notifications?
```bash
# Check users
php telegram/manage-users.php list

# Test send
php telegram/test-notification.php

# Check logs
tail -50 storage/logs/telegram.log
```

### Bot not responding?
1. Check bot token in `.env`
2. Verify webhook URL is accessible
3. Check logs for errors

## 📚 Documentation

- **Full Setup Guide**: `TELEGRAM_BOT_SETUP.md`
- **Architecture**: `telegram/ARCHITECTURE.md`
- **API Reference**: `telegram/README.md`
- **Integration Examples**: `telegram/integration-example.php`

## ✅ Success Checklist

- [x] Webhook configured
- [x] First user authenticated
- [x] Test notification received
- [x] Logs showing activity
- [x] Ready for production

## 🎯 Next Steps

1. Integrate with order form:
   ```php
   require_once 'php/TelegramBot.php';
   $bot = new TelegramBot();
   $bot->sendOrderNotification($orderData);
   ```

2. Add more users (send them password: `852789456`)

3. Monitor logs: `tail -f storage/logs/telegram.log`

4. Set up daily backups: `cp storage/data/telegram_users.json backups/`

## 💡 Tips

- Password can be shared with multiple people
- Each user subscribes individually via `/start`
- Use `/stop` to unsubscribe anytime
- Notifications sent to ALL authorized users
- Failed sends logged but don't break workflow
- Test before connecting to live order form

## 🆘 Need Help?

Check logs first:
```bash
tail -100 storage/logs/telegram.log
```

Test system:
```bash
php telegram/test-system.php
```

Review documentation:
- `TELEGRAM_BOT_SETUP.md` - Complete setup guide
- `telegram/README.md` - API documentation
- `telegram/ARCHITECTURE.md` - System architecture

---

**Ready to go!** 🚀

Start with Step 1 above, and you'll have notifications working in 3 minutes.
