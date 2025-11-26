# ✅ Telegram Bot Testing Checklist

## 📋 Pre-Testing Setup

Before running tests, ensure the following are complete:

- [ ] Files uploaded to production server (https://3dprint-omsk.ru)
- [ ] `.env` file exists with correct configuration
- [ ] Storage directories exist with correct permissions
- [ ] Webhook setup script has been run: `php telegram/setup-webhook.php`
- [ ] Webhook secret generated and saved to `.env`

---

## 🧪 Test Suite 1: Basic Bot Functionality

### Test 1.1: Find the Bot
**Objective:** Verify bot is accessible in Telegram

**Steps:**
1. Open Telegram app
2. Use search to find bot (get username from @BotFather)
3. OR use direct link: `https://t.me/YOUR_BOT_USERNAME`

**Expected Result:**
- ✅ Bot appears in search results
- ✅ Can open chat with bot
- ✅ Bot shows correct name and description

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.2: Start Command
**Objective:** Test initial bot interaction

**Steps:**
1. Open chat with bot
2. Click "START" button or type `/start`

**Expected Result:**
```
👋 Добро пожаловать!

Для получения уведомлений о заказах введите пароль доступа.

🔑 Пароль можно получить у администратора.
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.3: Wrong Password
**Objective:** Test password validation

**Steps:**
1. Type an incorrect password (e.g., "wrong123")
2. Send message

**Expected Result:**
```
❌ Неверный пароль

Попробуйте ещё раз или обратитесь к администратору.

Отправьте /start чтобы начать заново.
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.4: Correct Password
**Objective:** Test successful authentication

**Steps:**
1. Type the correct password: `852789456`
2. Send message

**Expected Result:**
```
✅ Спасибо, вы подписаны!

Здравствуйте, [Your First Name]!

Теперь вы будете получать уведомления о всех новых заказах.

Команды:
/stop - Отписаться от уведомлений
/status - Проверить статус подписки
/help - Показать справку
```

**Verification:**
- Check `storage/data/telegram_users.json` - your chat_id should be there

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.5: Status Command
**Objective:** Test status display for authenticated user

**Steps:**
1. Type `/status`
2. Send message

**Expected Result:**
```
✅ Статус подписки: Активна

👤 Имя: [Your Name]
🆔 Username: @your_username
📅 Подписан: [Timestamp]
📨 Последнее сообщение: [Timestamp]
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.6: Help Command
**Objective:** Test help message

**Steps:**
1. Type `/help`
2. Send message

**Expected Result:**
```
📖 Справка

✅ Вы подписаны на уведомления о новых заказах.

Доступные команды:
/start - Начать работу с ботом
/stop - Отписаться от уведомлений
/help - Показать эту справку
/status - Проверить статус подписки
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.7: Already Authenticated
**Objective:** Test message when sending password again

**Steps:**
1. Type the password `852789456` again
2. Send message

**Expected Result:**
```
ℹ️ Вы уже подписаны на уведомления.

Отправьте /help для списка команд.
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.8: Stop Command
**Objective:** Test unsubscribe functionality

**Steps:**
1. Type `/stop`
2. Send message

**Expected Result:**
```
✅ Вы успешно отписались

Вы больше не будете получать уведомления о заказах.

Чтобы подписаться снова, отправьте /start
```

**Verification:**
- Check `storage/data/telegram_users.json` - your chat_id should be removed

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.9: Status After Unsubscribe
**Objective:** Test status for unsubscribed user

**Steps:**
1. Type `/status`
2. Send message

**Expected Result:**
```
❌ Статус подписки: Не активна

Отправьте /start чтобы подписаться.
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 1.10: Re-subscribe
**Objective:** Test subscribing again after unsubscribe

**Steps:**
1. Type `/start`
2. Type password `852789456`

**Expected Result:**
- Same success message as Test 1.4
- User added back to telegram_users.json

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

## 🧪 Test Suite 2: CLI Tools

### Test 2.1: System Tests
**Objective:** Verify all system components

**Command:**
```bash
ssh your_server
cd /path/to/3dprint-omsk.ru
php telegram/test-system.php
```

**Expected Output:**
```
Telegram Bot System Tests
=====================================

✓ Test 1: Environment variables
✓ Test 2: Storage directories
✓ Test 3: TelegramBot class
✓ Test 4: User authentication
✓ Test 5: Send message
✓ Test 6: Broadcast message
✓ Test 7: User management
✓ Test 8: Webhook configuration
✓ Test 9: Order notification formatting
✓ Test 10: Log file writing

=====================================
All tests passed! (10/10)
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 2.2: List Users
**Objective:** Verify user management CLI

**Command:**
```bash
php telegram/manage-users.php list
```

**Expected Output:**
```
Total users: 1
----------------------------------------
Chat ID: [your_chat_id]
  Username: @your_username
  Name: [Your Name]
  Subscribed: [Timestamp]
  Last message: [Timestamp]
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 2.3: Test Notification
**Objective:** Test sending notification via CLI

**Command:**
```bash
php telegram/test-notification.php
```

**Expected Output (CLI):**
```
Sending test order notification...
✓ Notification sent successfully!
Recipients: 1
Success: 1
```

**Expected Result (Telegram):**
```
🔔 НОВЫЙ ЗАКАЗ

📋 Номер: #TEST-12345
👤 Клиент: Иван Петров
📱 Телефон: +7 (999) 123-45-67
📧 Email: test@example.com

🛠 Услуга: 3D печать по модели
💰 Сумма: 2 500 ₽

💬 Комментарий:
Тестовое уведомление системы

⏰ Дата: [Current timestamp]
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

## 🧪 Test Suite 3: Order Form Integration

### Test 3.1: Valid Order Submission
**Objective:** Test order form with valid data

**Steps:**
1. Open https://3dprint-omsk.ru
2. Scroll to order form section
3. Fill in form:
   - **Имя:** Test User
   - **Email:** test@example.com
   - **Телефон:** +79991234567
   - **Telegram:** test_user (without @)
   - **Услуга:** 3D печать по модели
   - **Описание:** This is a test order from the website to verify Telegram integration is working correctly.
4. Click "Отправить заявку"

**Expected Result (Browser):**
- Success message displayed
- Form resets

**Expected Result (Telegram):**
```
📋 Новый заказ с сайта!

👤 Имя: Test User
📧 Email: test@example.com
📱 Телефон: +79991234567
💬 Telegram: @test_user

🔧 Услуга: 3D печать по модели

📝 Описание:
This is a test order from the website to verify Telegram integration is working correctly.

⏰ Время: [Timestamp]
🌍 IP: [Your IP]
```

**Verification:**
- Check `storage/logs/orders.log` for entry

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 3.2: Missing Required Fields
**Objective:** Test validation for required fields

**Steps:**
1. Open order form
2. Leave all fields empty
3. Click "Отправить заявку"

**Expected Result:**
- Error message: "Ошибка валидации"
- Form highlights missing fields
- No Telegram notification sent

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 3.3: Invalid Email
**Objective:** Test email validation

**Steps:**
1. Fill form with invalid email: `notanemail`
2. Click "Отправить заявку"

**Expected Result:**
- Error message: "Неверный формат email"
- No Telegram notification sent

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 3.4: File Upload
**Objective:** Test file attachment functionality

**Steps:**
1. Fill valid order form
2. Attach file (e.g., .stl, .obj file)
3. Submit form

**Expected Result (Telegram):**
- Message includes file information:
  ```
  📎 Файлы: 1 шт.
    • filename.stl (XXX KB)
  ```

**Verification:**
- Check `storage/uploads/orders/` for uploaded file

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 3.5: Multiple Files Upload
**Objective:** Test multiple file attachments

**Steps:**
1. Fill valid order form
2. Attach multiple files (2-3 files)
3. Submit form

**Expected Result (Telegram):**
- Message includes all files:
  ```
  📎 Файлы: 3 шт.
    • file1.stl (XXX KB)
    • file2.obj (XXX KB)
    • file3.gcode (XXX KB)
  ```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 3.6: Invalid File Type
**Objective:** Test file type validation

**Steps:**
1. Fill valid order form
2. Try to attach invalid file (e.g., .exe, .zip)
3. Submit form

**Expected Result:**
- File rejected or ignored
- Order still processes without the file
- Or error message displayed

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 3.7: Large File Upload
**Objective:** Test file size limit (50 MB)

**Steps:**
1. Fill valid order form
2. Try to attach file > 50 MB
3. Submit form

**Expected Result:**
- Error message: "Размер файла превышает 50 МБ"
- Order not processed

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

## 🧪 Test Suite 4: Security Features

### Test 4.1: Honeypot Protection
**Objective:** Test bot detection

**Method:**
```bash
curl -X POST https://3dprint-omsk.ru/order-submit.php \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Bot User",
    "email": "bot@test.com",
    "phone": "1234567890",
    "service": "Test",
    "description": "Bot submission",
    "website": "http://spam.com"
  }'
```

**Expected Result:**
- Returns success (200)
- Message: "Спасибо, ваша заявка получена"
- BUT: No Telegram notification sent
- Log entry marked as honeypot

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 4.2: Rate Limiting
**Objective:** Test spam prevention (max 5 orders/hour)

**Steps:**
1. Submit 5 valid orders quickly from same IP
2. Try to submit 6th order

**Expected Result:**
- First 5 orders: Success
- 6th order: Error 429 "Превышен лимит запросов"
- Only first 5 Telegram notifications sent

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 4.3: Webhook Secret Validation
**Objective:** Test webhook security

**Method:**
```bash
# Send POST without proper secret
curl -X POST https://3dprint-omsk.ru/telegram/webhook.php \
  -H "Content-Type: application/json" \
  -d '{"update_id": 123}'
```

**Expected Result:**
- HTTP 403 Forbidden
- Message: "Forbidden"

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 4.4: GET Request to Webhook
**Objective:** Test webhook only accepts POST

**Method:**
```bash
curl https://3dprint-omsk.ru/telegram/webhook.php
```

**Expected Result:**
- HTTP 405 Method Not Allowed
- Message: "Method Not Allowed"

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

## 🧪 Test Suite 5: Multi-User Scenarios

### Test 5.1: Multiple Users Subscribe
**Objective:** Test multiple users receiving notifications

**Steps:**
1. Subscribe with User 1 (your account)
2. Subscribe with User 2 (another Telegram account)
3. Subscribe with User 3 (third account)
4. Submit order from website

**Expected Result:**
- All 3 users receive the notification
- CLI shows: `php telegram/manage-users.php list` → 3 users

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 5.2: One User Unsubscribes
**Objective:** Test notifications after partial unsubscribe

**Steps:**
1. User 2 sends `/stop`
2. Submit order from website

**Expected Result:**
- User 1 receives notification
- User 2 does NOT receive notification
- User 3 receives notification
- CLI shows 2 users

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 5.3: Remove User via CLI
**Objective:** Test admin user removal

**Command:**
```bash
php telegram/manage-users.php remove <chat_id>
```

**Expected Result:**
- User removed from telegram_users.json
- User doesn't receive future notifications
- CLI shows reduced user count

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

## 🧪 Test Suite 6: Error Handling

### Test 6.1: Telegram API Unavailable
**Objective:** Test graceful failure and queueing

**Steps:**
1. Temporarily break bot token in .env
2. Submit order from website
3. Check logs and queue

**Expected Result:**
- Order still processes successfully
- Error logged in telegram.log
- Order added to `storage/cache/order_queue.json`
- User still sees success message

**Cleanup:**
- Restore correct bot token
- Run `php process-order-queue.php` to retry

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 6.2: No Authorized Users
**Objective:** Test behavior when no users subscribed

**Steps:**
1. Remove all users: `/stop` from all accounts
2. Submit order from website

**Expected Result:**
- Order processes successfully
- Log shows "Broadcasting message | recipients_count: 0"
- No notifications sent (obviously)
- No errors

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 6.3: Invalid Webhook URL
**Objective:** Test webhook setup with wrong URL

**Steps:**
1. Edit .env: Change APP_URL to invalid URL
2. Run `php telegram/setup-webhook.php`

**Expected Result:**
- Webhook setup fails gracefully
- Error message displayed
- Original webhook unchanged

**Cleanup:**
- Restore correct APP_URL
- Re-run setup

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

## 🧪 Test Suite 7: Logging and Monitoring

### Test 7.1: Telegram Logs
**Objective:** Verify telegram activity logging

**Command:**
```bash
tail -f storage/logs/telegram.log
```

**Actions:**
1. Send `/start` to bot
2. Authenticate with password
3. Submit order from website

**Expected Log Entries:**
```
[timestamp] WEBHOOK INFO: Received update | {"update_id":...}
[timestamp] INFO: Processing message | {"chat_id":...,"username":"...","text":"/start"}
[timestamp] INFO: User authenticated successfully | {"chat_id":...,"username":"..."}
[timestamp] INFO: Broadcasting message | {"recipients_count":1}
[timestamp] INFO: Message sent successfully | {"chat_id":...}
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 7.2: Order Logs
**Objective:** Verify order submission logging

**Command:**
```bash
tail -f storage/logs/orders.log
```

**Action:**
Submit order from website

**Expected Log Entry:**
```
[timestamp] INFO: Order received | {"name":"Test User","email":"test@example.com","phone":"+79991234567","service":"3D печать","files_count":0,"telegram_status":"success","ip":"..."}
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 7.3: Failed Authentication Logging
**Objective:** Verify security event logging

**Action:**
Send wrong password to bot

**Expected Log Entry:**
```
[timestamp] WARNING: Failed authentication attempt | {"chat_id":...,"username":"..."}
```

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

## 🧪 Test Suite 8: Performance and Load

### Test 8.1: Broadcast to Many Users
**Objective:** Test broadcast performance (if possible)

**Setup:**
- Subscribe 5-10 users

**Action:**
- Submit order or run test-notification.php

**Expected Result:**
- All users receive notification
- Reasonable delay (100ms between messages)
- All messages sent successfully
- No timeout errors

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 8.2: Large Order Description
**Objective:** Test handling of long messages

**Steps:**
1. Submit order with very long description (1500+ characters)
2. Check Telegram

**Expected Result:**
- Message received with full description
- No truncation or errors
- Message properly formatted

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

### Test 8.3: Special Characters
**Objective:** Test Unicode and special character handling

**Steps:**
1. Submit order with emojis, Cyrillic, and special characters
   - Name: "Тест 🎉 User"
   - Description: "Тестовое описание с <b>HTML</b> & спецсимволами"

**Expected Result:**
- All characters displayed correctly in Telegram
- HTML entities escaped
- No encoding errors

**Status:** ⬜ PASS / ⬜ FAIL  
**Notes:** _______________________________________________

---

## 📊 Test Summary

### Overall Results

**Test Suite 1: Basic Bot Functionality** (10 tests)
- Passed: _____ / 10
- Failed: _____ / 10

**Test Suite 2: CLI Tools** (3 tests)
- Passed: _____ / 3
- Failed: _____ / 3

**Test Suite 3: Order Form Integration** (7 tests)
- Passed: _____ / 7
- Failed: _____ / 7

**Test Suite 4: Security Features** (4 tests)
- Passed: _____ / 4
- Failed: _____ / 4

**Test Suite 5: Multi-User Scenarios** (3 tests)
- Passed: _____ / 3
- Failed: _____ / 3

**Test Suite 6: Error Handling** (3 tests)
- Passed: _____ / 3
- Failed: _____ / 3

**Test Suite 7: Logging and Monitoring** (3 tests)
- Passed: _____ / 3
- Failed: _____ / 3

**Test Suite 8: Performance and Load** (3 tests)
- Passed: _____ / 3
- Failed: _____ / 3

### Total Results
- **Total Tests:** 36
- **Passed:** _____ / 36
- **Failed:** _____ / 36
- **Success Rate:** _____%

---

## ✅ Production Readiness Criteria

System is ready for production when:

- [ ] All Test Suite 1 tests pass (Basic functionality)
- [ ] All Test Suite 2 tests pass (CLI tools)
- [ ] At least 6/7 Test Suite 3 tests pass (Order integration)
- [ ] All Test Suite 4 tests pass (Security)
- [ ] At least 2/3 Test Suite 5 tests pass (Multi-user)
- [ ] At least 2/3 Test Suite 6 tests pass (Error handling)
- [ ] All Test Suite 7 tests pass (Logging)
- [ ] At least 2/3 Test Suite 8 tests pass (Performance)

**Minimum Required:** 30/36 tests passing (83%)

---

## 📝 Notes and Issues

### Issues Found:
1. ______________________________________________
2. ______________________________________________
3. ______________________________________________

### Actions Required:
1. ______________________________________________
2. ______________________________________________
3. ______________________________________________

### Tested By:
- **Name:** _____________________
- **Date:** _____________________
- **Environment:** Production / Staging / Local
- **Server:** https://3dprint-omsk.ru

---

## 🎯 Sign-Off

**Testing Completed By:** ________________________  
**Date:** ________________________  
**Signature:** ________________________

**Approved for Production:** ☐ YES / ☐ NO / ☐ WITH RESERVATIONS

**Comments:**
_______________________________________________
_______________________________________________
_______________________________________________
