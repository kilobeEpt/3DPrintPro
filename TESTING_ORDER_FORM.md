# Order Form Testing Guide

## 🧪 Quick Testing Steps

### 1. Prerequisites

Ensure Telegram bot is set up and at least one user is authenticated:

```bash
# Check .env file
cat .env | grep TELEGRAM

# Should show:
# TELEGRAM_BOT_TOKEN=8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI
# TELEGRAM_PASSWORD=852789456
```

**Authenticate a user:**
1. Open Telegram
2. Search for your bot
3. Send: `/start`
4. Enter password: `852789456`
5. You should receive: "Спасибо, вы подписаны!"

### 2. Start Local Server

```bash
# Using PHP built-in server
php -S localhost:8000

# Or using Python
python3 -m http.server 8000
```

### 3. Open Demo Form

Open in browser:
- http://localhost:8000/order-form-demo.html

Or test with actual site form:
- http://localhost:8000/index.html

### 4. Manual Testing Checklist

#### Test 1: Valid Submission ✅
Fill all fields with valid data:
- Name: Иван Петров
- Email: ivan@example.com
- Phone: +7 999 123-45-67
- Service: FDM печать
- Description: Нужно напечатать детали для проекта (minimum 10 chars)

**Expected:**
- ✅ Form submits successfully
- ✅ Success message appears
- ✅ Form resets
- ✅ Telegram notification arrives to all authenticated users
- ✅ Order logged to `storage/logs/orders.log`

#### Test 2: Empty Fields ❌
Leave required fields empty and submit.

**Expected:**
- ❌ Red border on empty fields
- ❌ Error messages appear below fields
- ❌ Form does not submit

#### Test 3: Invalid Email ❌
Enter invalid email: `invalid-email`

**Expected:**
- ❌ Error message: "Неверный формат email"
- ❌ Form does not submit

#### Test 4: Short Name/Description ❌
- Name: `A` (too short)
- Description: `Short` (too short)

**Expected:**
- ❌ Error: "Минимальная длина: 2 символов" (name)
- ❌ Error: "Минимальная длина: 10 символов" (description)

#### Test 5: File Upload 📎
Upload a file (e.g., test.stl):

**Expected:**
- ✅ File name appears in form
- ✅ File size shown
- ✅ Telegram message includes file info

#### Test 6: Rate Limiting 🚫
Submit 6 orders quickly from the same browser.

**Expected:**
- ✅ First 5 orders succeed
- ❌ 6th order rejected with: "Превышен лимит запросов"
- ❌ Error shows reset time

### 5. Automated Tests

Run the test suite:

```bash
# Start server first
php -S localhost:8000 &

# Run tests
php test-order-submit.php http://localhost:8000
```

**Expected output:**
```
╔════════════════════════════════════════════════════════════╗
║  Order Submission Handler - Test Suite                    ║
╚════════════════════════════════════════════════════════════╝

Test 1: Valid order submission
--------------------------------
✅ PASS: Order submitted successfully
   Message: Спасибо, ваша заявка получена!
   Order ID: order_67890abcdef12345
   Telegram Status: success

Test 2: Missing required fields
--------------------------------
✅ PASS: Validation errors detected correctly

Test 3: Invalid email format
--------------------------------
✅ PASS: Invalid email detected

Test 4: Field length validation
--------------------------------
✅ PASS: Field length validation working

Test 5: Honeypot detection
--------------------------------
✅ PASS: Honeypot working correctly

╔════════════════════════════════════════════════════════════╗
║  Test Results Summary                                      ║
╚════════════════════════════════════════════════════════════╝

Valid Submission:              ✅ PASS
Missing Fields:                ✅ PASS
Invalid Email:                 ✅ PASS
Field Length:                  ✅ PASS
Honeypot:                      ✅ PASS

Total Tests: 5
Passed: 5
Failed: 0

🎉 All critical tests passed!
```

### 6. Check Logs

```bash
# View order logs
tail -f storage/logs/orders.log

# Expected format:
# [2025-01-15 14:30:00] INFO: Order received | {"name":"Иван Петров","email":"ivan@example.com",...}

# View Telegram logs
tail -f storage/logs/telegram.log

# Check queue (if Telegram failed)
cat storage/cache/order_queue.json
```

### 7. Verify Telegram Notification

Check Telegram bot messages. Should receive:

```
📋 Новый заказ с сайта!

👤 Имя: Иван Петров
📧 Email: ivan@example.com
📱 Телефон: +7 999 123-45-67

🔧 Услуга: FDM печать

📝 Описание:
Нужно напечатать детали для проекта...

⏰ Время: 2025-01-15 14:30:00
🌍 IP: 127.0.0.1
```

### 8. Test Queue Processing

If Telegram API was unavailable (queued orders):

```bash
# Process queue manually
php process-order-queue.php

# Expected output:
# [2025-01-15 14:35:00] INFO: Processing queue | {"items":1}
# [2025-01-15 14:35:01] INFO: Queue item processed successfully | {...}
# [2025-01-15 14:35:01] INFO: Queue processing complete | {"processed":1,"failed":0,"remaining":0}
```

## 🔍 Troubleshooting

### Issue: No Telegram notification

**Check:**
```bash
# 1. Check if users are authenticated
cat storage/data/telegram_users.json

# Should show at least one user with "authenticated": true

# 2. Test bot manually
php telegram/test-notification.php

# 3. Check Telegram logs
tail -50 storage/logs/telegram.log
```

### Issue: Permission denied on storage/

**Fix:**
```bash
chmod 755 storage/uploads/orders
chmod 755 storage/cache/order_rate_limit
chmod 755 storage/logs
```

### Issue: File upload fails

**Check:**
```bash
# PHP upload limits
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Should be at least 50M
```

**Fix in php.ini:**
```ini
upload_max_filesize = 50M
post_max_size = 55M
```

## 📊 Success Criteria

All items checked:

- [ ] ✅ Valid order submits successfully
- [ ] ✅ Telegram notification arrives to all authenticated users
- [ ] ✅ Message format is correct with all details
- [ ] ✅ Empty fields are rejected with error messages
- [ ] ✅ Invalid email is rejected
- [ ] ✅ Short fields are rejected (name < 2, description < 10)
- [ ] ✅ Honeypot detection works (bot submissions silently succeed)
- [ ] ✅ Rate limiting works (6th request rejected)
- [ ] ✅ File upload works (.stl, .obj, etc.)
- [ ] ✅ Large files rejected (> 50 MB)
- [ ] ✅ Invalid file types rejected (.exe, .bat, etc.)
- [ ] ✅ Order logged to storage/logs/orders.log
- [ ] ✅ Queue mechanism works when Telegram fails
- [ ] ✅ No 500 errors on server
- [ ] ✅ User sees appropriate success/error messages

## 🚀 Production Deployment

Before deploying:

1. **Review configuration:**
   ```bash
   # Check .env has production values
   cat .env | grep TELEGRAM_BOT_TOKEN
   ```

2. **Test on staging:**
   ```bash
   # Update test script with staging URL
   php test-order-submit.php https://staging.3dprint-omsk.ru
   ```

3. **Setup cron job:**
   ```bash
   crontab -e
   # Add:
   * * * * * php /var/www/3dprint-omsk.ru/current/process-order-queue.php
   ```

4. **Monitor logs:**
   ```bash
   tail -f storage/logs/orders.log
   tail -f storage/logs/telegram.log
   ```

5. **Update CORS (security):**
   Edit `order-submit.php`:
   ```php
   // Change from:
   header('Access-Control-Allow-Origin: *');
   
   // To:
   header('Access-Control-Allow-Origin: https://3dprint-omsk.ru');
   ```

## 📞 Support

If issues persist:
1. Check logs: `storage/logs/orders.log` and `storage/logs/telegram.log`
2. Run test suite: `php test-order-submit.php`
3. Test Telegram bot: `php telegram/test-notification.php`
4. Verify permissions: `ls -la storage/uploads/orders/`

---

**Testing Status:** Ready for validation ✅  
**Last Updated:** 2025-01-15
