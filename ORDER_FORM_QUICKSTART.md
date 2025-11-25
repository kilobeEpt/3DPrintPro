# 🚀 Order Form Quick Start Guide

## ⚡ 3-Minute Setup

### Step 1: Check Prerequisites ✅

```bash
# Ensure .env exists
test -f .env && echo "✅ .env exists" || echo "❌ Create .env from .env.example"

# Check Telegram configuration
grep TELEGRAM_BOT_TOKEN .env
# Should show: TELEGRAM_BOT_TOKEN=8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI
```

### Step 2: Authenticate Telegram User 📱

1. Open Telegram
2. Search for your bot
3. Send: `/start`
4. Enter password: `852789456`
5. Should receive: **"Спасибо, вы подписаны!"**

### Step 3: Verify Directories 📁

```bash
# Check directories exist
ls -ld storage/uploads/orders storage/cache/order_rate_limit

# If not exist, create:
mkdir -p storage/uploads/orders storage/cache/order_rate_limit
chmod 755 storage/uploads/orders storage/cache/order_rate_limit
```

### Step 4: Test the System 🧪

#### Option A: Open Demo Page
```
Open in browser: http://your-domain.com/order-form-demo.html
```

#### Option B: Use Existing Form
```
Open in browser: http://your-domain.com/index.html#contact
```

Fill the form:
- **Name:** Иван Петров (test)
- **Email:** test@example.com
- **Phone:** +7 999 123-45-67
- **Service:** FDM печать
- **Description:** Тестовый заказ для проверки системы

Click **"Отправить заявку"**

### Step 5: Verify Notification 📨

Check Telegram bot - you should receive:

```
📋 Новый заказ с сайта!

👤 Имя: Иван Петров
📧 Email: test@example.com
📱 Телефон: +7 999 123-45-67

🔧 Услуга: FDM печать

📝 Описание:
Тестовый заказ для проверки системы

⏰ Время: 2025-01-15 14:30:00
🌍 IP: 192.168.1.1
```

### Step 6: Check Logs 📊

```bash
# View order log
tail -5 storage/logs/orders.log

# Should show:
# [2025-01-15 14:30:00] INFO: Order received | {"name":"Иван Петров",...}
```

---

## ✅ Success!

If you received the Telegram notification and see the log entry, the system is working perfectly!

## 🔧 Optional: Setup Queue Processor

For automatic retry of failed notifications:

```bash
# Edit crontab
crontab -e

# Add this line (process queue every minute):
* * * * * php /var/www/3dprint-omsk.ru/current/process-order-queue.php
```

## 📚 Need More Details?

- **Implementation Guide:** `ORDER_FORM_IMPLEMENTATION.md`
- **Testing Guide:** `TESTING_ORDER_FORM.md`
- **Completion Summary:** `ORDER_FORM_TICKET_SUMMARY.md`

## 🐛 Troubleshooting

### ❌ No Telegram notification

```bash
# Check authenticated users
cat storage/data/telegram_users.json

# Test bot manually
php telegram/test-notification.php

# Check logs
tail -20 storage/logs/telegram.log
```

### ❌ Permission denied

```bash
chmod 755 storage/uploads/orders
chmod 755 storage/cache/order_rate_limit
chmod 755 storage/logs
```

### ❌ File upload fails

Check PHP limits:
```bash
php -i | grep upload_max_filesize
php -i | grep post_max_size
# Should be at least 50M
```

---

## 🎉 That's It!

Your order form → Telegram notification system is ready!

**Features:**
✅ Validation  
✅ Honeypot  
✅ Rate Limiting  
✅ File Uploads  
✅ Telegram Broadcasting  
✅ Queue Mechanism  
✅ Logging

---

**Questions?** Check `ORDER_FORM_IMPLEMENTATION.md` for complete documentation.
