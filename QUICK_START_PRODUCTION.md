# ⚡ Quick Start - Production Server

## 🎯 Goal
Get Telegram bot running in 5 minutes on https://3dprint-omsk.ru

---

## ✅ Pre-Requirements

Make sure you have:
- ✅ SSH access to production server
- ✅ PHP 7.4+ installed
- ✅ Files uploaded to server
- ✅ Telegram Bot Token: `8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI`
- ✅ Password: `852789456`

---

## 🚀 5-Minute Setup

### Step 1: SSH into Server (30 seconds)

```bash
ssh your_username@your_server
cd /path/to/3dprint-omsk.ru
```

---

### Step 2: Verify Files (30 seconds)

```bash
# Check required files exist
ls -la php/TelegramBot.php
ls -la telegram/webhook.php
ls -la telegram/setup-webhook.php
ls -la order-submit.php
ls -la .env

# If .env missing, copy from example:
cp .env.example .env
```

---

### Step 3: Run Deployment Script (2 minutes)

```bash
# Make deploy script executable
chmod +x telegram/deploy.sh

# Run deployment (automatic setup)
bash telegram/deploy.sh
```

**What this does:**
- ✅ Creates storage directories
- ✅ Sets file permissions
- ✅ Generates webhook secret
- ✅ Configures webhook with Telegram
- ✅ Runs system tests

**Expected output:**
```
=====================================
Telegram Bot Deployment
=====================================

✓ Found .env file
✓ PHP 8.1.0 detected

Checking PHP extensions...
✓ curl
✓ json
✓ mbstring

... (continues) ...

=====================================
Deployment Complete!
=====================================
```

---

### Step 4: Test the Bot (1 minute)

```bash
# Send test notification
php telegram/test-notification.php
```

**But first, subscribe to bot:**

1. Open Telegram
2. Search for your bot (username from @BotFather)
3. Send: `/start`
4. Send: `852789456` (password)
5. Wait for confirmation
6. Now run the test command above

**Expected in Telegram:**
```
🔔 НОВЫЙ ЗАКАЗ

📋 Номер: #TEST-12345
👤 Клиент: Иван Петров
📱 Телефон: +7 (999) 123-45-67
📧 Email: test@example.com

🛠 Услуга: 3D печать по модели
💰 Сумма: 2 500 ₽
...
```

---

### Step 5: Test Order Form (1 minute)

1. Open https://3dprint-omsk.ru
2. Fill order form
3. Submit
4. Check Telegram for notification

---

## ✅ Success Criteria

You should now have:
- ✅ Bot responds to `/start`
- ✅ Password `852789456` works
- ✅ Test notification received
- ✅ Order form sends notifications
- ✅ Logs created in `storage/logs/`
- ✅ Users saved to `storage/data/telegram_users.json`

---

## 🔍 Quick Troubleshooting

### Bot not responding?
```bash
# Check webhook status
curl -s "https://api.telegram.org/bot8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI/getWebhookInfo" | python -m json.tool
```

Look for:
- `"url"`: Should be `https://3dprint-omsk.ru/telegram/webhook.php`
- `"has_custom_certificate": false`
- `"pending_update_count": 0`
- No `"last_error_message"`

**Fix:** Run `php telegram/setup-webhook.php` again

---

### Order form not sending notifications?
```bash
# Check logs
tail -n 20 storage/logs/telegram.log
tail -n 20 storage/logs/orders.log
```

**Common issues:**
- No authorized users → Subscribe to bot first
- Webhook not set → Run setup-webhook.php
- PHP errors → Check logs

---

### Permission errors?
```bash
# Fix permissions
chmod 755 storage/ storage/data/ storage/logs/ storage/uploads/ storage/cache/
chmod 644 storage/data/*.json
chmod 644 storage/logs/*.log
chmod 600 .env
```

---

## 📚 Next Steps

**For complete setup:**
- Read: [TELEGRAM_BOT_DEPLOYMENT.md](TELEGRAM_BOT_DEPLOYMENT.md)

**For testing:**
- Read: [TELEGRAM_BOT_TESTING_CHECKLIST.md](TELEGRAM_BOT_TESTING_CHECKLIST.md)

**For management:**
```bash
# List users
php telegram/manage-users.php list

# View logs
tail -f storage/logs/telegram.log

# Run all tests
php telegram/test-system.php
```

---

## 🎯 Key Commands

| Command | Purpose |
|---------|---------|
| `php telegram/setup-webhook.php` | Setup/reset webhook |
| `php telegram/test-system.php` | Run all tests |
| `php telegram/test-notification.php` | Send test notification |
| `php telegram/manage-users.php list` | List authorized users |
| `tail -f storage/logs/telegram.log` | Watch telegram logs |
| `tail -f storage/logs/orders.log` | Watch order logs |

---

## 📞 Support

**Bot Configuration:**
- Token: `8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI`
- Password: `852789456`
- Webhook: `https://3dprint-omsk.ru/telegram/webhook.php`

**Files:**
- Bot class: `php/TelegramBot.php`
- Webhook handler: `telegram/webhook.php`
- Order handler: `order-submit.php`
- Config: `.env`

**Storage:**
- Users: `storage/data/telegram_users.json`
- Logs: `storage/logs/telegram.log`, `storage/logs/orders.log`
- Uploads: `storage/uploads/orders/`

---

## ✅ Done!

Your Telegram bot is now live and ready to receive order notifications! 🎉

**Test it:**
1. Subscribe to bot
2. Submit order from website
3. Receive notification in Telegram

**Questions?** Check the full documentation in [TELEGRAM_BOT_DEPLOYMENT.md](TELEGRAM_BOT_DEPLOYMENT.md)
