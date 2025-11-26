# ✅ Telegram Bot - Deployment Checklist

## Pre-Deployment Checklist

### Files Verification:
- [ ] `php/TelegramBot.php` exists (13 KB)
- [ ] `telegram/webhook.php` exists (11 KB)
- [ ] `telegram/setup-webhook.php` exists (4.6 KB)
- [ ] `telegram/deploy.sh` exists and is executable (7 KB)
- [ ] `order-submit.php` exists (17 KB)
- [ ] `.env` file exists with correct configuration (867 bytes)
- [ ] All management tools present in `telegram/` directory
- [ ] All documentation files present

### Configuration Verification:
- [ ] `.env` has `TELEGRAM_BOT_TOKEN=8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI`
- [ ] `.env` has `TELEGRAM_PASSWORD=852789456`
- [ ] `.env` has `APP_URL=https://3dprint-omsk.ru`
- [ ] `.env` has `TELEGRAM_WEBHOOK_SECRET=` (will be auto-generated)

### Server Requirements:
- [ ] PHP 7.4+ installed
- [ ] PHP extensions: curl, json, mbstring
- [ ] SSH access to server
- [ ] Write permissions for storage directories

---

## Deployment Steps

### Step 1: Upload Files
- [ ] All files uploaded to production server via FTP/SFTP/Git
- [ ] Files are in correct directory structure
- [ ] `.env` file uploaded (or copied from `.env.example`)

### Step 2: Connect to Server
```bash
ssh your_username@your_server
cd /path/to/3dprint-omsk.ru
```
- [ ] Successfully connected to server
- [ ] In correct project directory

### Step 3: Run Deployment Script
```bash
chmod +x telegram/deploy.sh
bash telegram/deploy.sh
```
- [ ] Script completed without errors
- [ ] Storage directories created
- [ ] Permissions set correctly
- [ ] Webhook secret generated
- [ ] Webhook configured with Telegram
- [ ] System tests passed (10/10)

### Step 4: Verify Webhook
```bash
php telegram/test-system.php
```
- [ ] All 10 tests passed
- [ ] Webhook URL matches APP_URL
- [ ] No errors in output

### Step 5: Subscribe to Bot
- [ ] Opened Telegram app
- [ ] Found bot (searched by username from @BotFather)
- [ ] Sent `/start` command
- [ ] Received welcome message
- [ ] Sent password: `852789456`
- [ ] Received confirmation: "Спасибо, вы подписаны!"

### Step 6: Test Notification
```bash
php telegram/test-notification.php
```
- [ ] Command executed successfully
- [ ] Received test notification in Telegram
- [ ] Notification formatted correctly with emojis
- [ ] All fields present in notification

### Step 7: Test Order Form
- [ ] Opened https://3dprint-omsk.ru in browser
- [ ] Scrolled to order form section
- [ ] Filled out form with test data:
  - Name: Test User
  - Email: test@example.com
  - Phone: +79991234567
  - Telegram: test_user
  - Service: Selected
  - Description: Test order
- [ ] Submitted form successfully
- [ ] Received notification in Telegram
- [ ] Notification contains all order details

### Step 8: Verify Logs
```bash
tail -n 20 storage/logs/telegram.log
tail -n 20 storage/logs/orders.log
```
- [ ] `telegram.log` exists and has entries
- [ ] `orders.log` exists and has entries
- [ ] No errors in logs
- [ ] Timestamps are correct

### Step 9: Verify User Storage
```bash
cat storage/data/telegram_users.json
```
- [ ] File exists
- [ ] Contains your chat_id
- [ ] User data formatted correctly
- [ ] All fields present (chat_id, username, first_name, authenticated, subscribed_at)

### Step 10: Test All Bot Commands
- [ ] `/start` - Shows welcome or already subscribed message
- [ ] `/stop` - Unsubscribes successfully
- [ ] `/start` again - Can re-subscribe
- [ ] Password `852789456` - Authenticates again
- [ ] `/status` - Shows subscription status
- [ ] `/help` - Shows help message

---

## Post-Deployment Verification

### Security Checks:
- [ ] `.env` file has 600 permissions (`chmod 600 .env`)
- [ ] Storage directories have 755 permissions
- [ ] `storage/.htaccess` exists (Apache only)
- [ ] Webhook only accepts POST requests (test with GET)
- [ ] Webhook validates secret token

### Performance Tests:
- [ ] Order form responds quickly (< 2 seconds)
- [ ] Telegram notifications arrive within 1-2 seconds
- [ ] Multiple users can subscribe simultaneously
- [ ] Broadcasting works for multiple users

### Error Handling:
- [ ] Invalid password shows error message
- [ ] Missing form fields show validation errors
- [ ] Rate limiting works (try 6 orders quickly)
- [ ] Honeypot catches bot submissions
- [ ] Invalid file types are rejected

---

## Monitoring Setup

### Log Rotation:
- [ ] Set up log rotation for `telegram.log`
- [ ] Set up log rotation for `orders.log`
- [ ] Configure retention policy (e.g., 30 days)

### Cron Jobs (Optional):
```bash
# Process failed notification queue every minute
* * * * * cd /path/to/3dprint-omsk.ru && php process-order-queue.php
```
- [ ] Cron job configured (if using queue)

### Alerts:
- [ ] Set up monitoring for webhook status
- [ ] Set up monitoring for log errors
- [ ] Set up disk space monitoring for storage/

---

## Testing Checklist

### Basic Functionality (Required):
- [ ] ✅ Bot responds to `/start`
- [ ] ✅ Password authentication works
- [ ] ✅ User gets subscribed successfully
- [ ] ✅ Test notification received
- [ ] ✅ Order form sends notifications
- [ ] ✅ Logs are written correctly
- [ ] ✅ Users are saved to JSON
- [ ] ✅ `/stop` unsubscribes user
- [ ] ✅ `/status` shows status
- [ ] ✅ `/help` shows commands

### Advanced Features (Optional):
- [ ] Multiple users receive notifications
- [ ] File uploads work
- [ ] Rate limiting prevents spam
- [ ] Honeypot catches bots
- [ ] Queue handles failed notifications

### Documentation Review:
- [ ] Team knows how to use the bot
- [ ] Team knows how to manage users
- [ ] Team knows where to check logs
- [ ] Team has access to all documentation

---

## Rollback Plan

If something goes wrong:

### Immediate Actions:
1. Check logs: `tail -f storage/logs/telegram.log`
2. Verify webhook: `php telegram/test-system.php`
3. Re-run setup: `php telegram/setup-webhook.php`

### If Major Issues:
1. Delete webhook: 
   ```bash
   curl "https://api.telegram.org/bot8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI/deleteWebhook"
   ```
2. Fix issues
3. Re-run deployment: `bash telegram/deploy.sh`

### Contact Information:
- Documentation: See `TELEGRAM_BOT_DEPLOYMENT.md`
- Testing Guide: See `TELEGRAM_BOT_TESTING_CHECKLIST.md`
- Quick Start: See `QUICK_START_PRODUCTION.md`

---

## Success Criteria

### Minimum Requirements (Must Pass):
- ✅ Bot is accessible in Telegram
- ✅ Password authentication works
- ✅ At least 1 user subscribed
- ✅ Test notification received
- ✅ Order form integration works
- ✅ Logs are being written
- ✅ No critical errors

### Optional Enhancements:
- ✅ Multiple users subscribed
- ✅ File uploads working
- ✅ All 36 tests passed (see TELEGRAM_BOT_TESTING_CHECKLIST.md)
- ✅ Monitoring set up
- ✅ Team trained

---

## Sign-Off

**Deployment Completed By:** ________________________

**Date:** ________________________

**Time:** ________________________

**Server:** https://3dprint-omsk.ru

### Verification Results:

| Item | Status | Notes |
|------|--------|-------|
| Files uploaded | ☐ Pass / ☐ Fail | _________________ |
| Configuration correct | ☐ Pass / ☐ Fail | _________________ |
| Webhook configured | ☐ Pass / ☐ Fail | _________________ |
| Bot responding | ☐ Pass / ☐ Fail | _________________ |
| Authentication works | ☐ Pass / ☐ Fail | _________________ |
| Notifications working | ☐ Pass / ☐ Fail | _________________ |
| Order form integrated | ☐ Pass / ☐ Fail | _________________ |
| Logs working | ☐ Pass / ☐ Fail | _________________ |
| Security verified | ☐ Pass / ☐ Fail | _________________ |
| Documentation reviewed | ☐ Pass / ☐ Fail | _________________ |

**Overall Status:** ☐ PRODUCTION READY / ☐ NEEDS FIXES

**Comments:**
_____________________________________________
_____________________________________________
_____________________________________________

**Approved By:** ________________________

**Signature:** ________________________

---

## 🎉 Deployment Complete!

If all items are checked, your Telegram bot is live and operational! 

**Next steps:**
1. Monitor logs for 24 hours
2. Test with real orders
3. Train team on management tools
4. Set up automated monitoring

**For support, see:**
- [TELEGRAM_BOT_DEPLOYMENT.md](TELEGRAM_BOT_DEPLOYMENT.md)
- [TELEGRAM_BOT_TESTING_CHECKLIST.md](TELEGRAM_BOT_TESTING_CHECKLIST.md)
- [QUICK_START_PRODUCTION.md](QUICK_START_PRODUCTION.md)

**Congratulations! 🎊**
