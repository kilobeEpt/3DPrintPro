# Telegram API URL Fix - Deployment Checklist

## Pre-Deployment Verification

### 1. File Changes Confirmed
- [x] `order-submit.php` - Environment loading added (lines 33-54)
- [x] `process-order-queue.php` - Environment loading added (lines 12-33)
- [x] `.env` file created from `.env.example`
- [x] `.env` contains correct `TELEGRAM_BOT_TOKEN`
- [x] `.env` contains correct `TELEGRAM_PASSWORD`

### 2. Documentation Created
- [x] `TELEGRAM_API_URL_FIX.md` - Technical documentation
- [x] `FIX_SUMMARY.md` - Quick reference guide
- [x] `test-telegram-api-url.php` - Automated test script
- [x] `TELEGRAM_FIX_CHECKLIST.md` - This checklist

### 3. Code Review
- [x] All files requiring TelegramBot have environment loading
- [x] Environment loading pattern is consistent across all files
- [x] No other files instantiate TelegramBot without .env loading
- [x] `.env` file has correct permissions (will be 644 or 640)

## Deployment Steps

### Step 1: Backup Current State
```bash
# Backup current files before deployment
cp order-submit.php order-submit.php.backup
cp process-order-queue.php process-order-queue.php.backup
```

### Step 2: Deploy Files
```bash
# Upload modified files to production:
# - order-submit.php
# - process-order-queue.php
# - .env (if not exists)
```

### Step 3: Verify .env Configuration
```bash
# Check .env exists and has bot token
grep TELEGRAM_BOT_TOKEN .env
# Should output: TELEGRAM_BOT_TOKEN=8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI

# Set correct permissions
chmod 640 .env
```

### Step 4: Test Webhook (Should Still Work)
```bash
# Webhook was working before, should continue working
php telegram/test-system.php
# Expected: All 10 tests pass
```

### Step 5: Test Order Submission
1. Navigate to: `https://3dprint-omsk.ru/#order-form-section`
2. Fill in order form:
   - Name: Test User
   - Email: test@example.com
   - Phone: +79991234567
   - Service: Any option
   - Description: Test order notification
3. Submit form
4. Wait for success message

### Step 6: Verify Notification Received
1. Open Telegram bot
2. Check for new message from bot
3. Should see order details with emojis
4. Should include all form data

### Step 7: Check Logs
```bash
# Check for successful notification
tail -20 storage/logs/orders.log
# Should show: "Telegram notification sent", "recipients_count": X, "success": X

tail -20 storage/logs/telegram.log
# Should show: "Message sent successfully" for each recipient
# Should NOT show: 404 errors or "bot/" URLs
```

### Step 8: Test Queue Processor
```bash
# Manually run queue processor
php process-order-queue.php
# Should not crash, should process any queued items
```

## Post-Deployment Verification

### Automated Tests
- [ ] Run: `php test-telegram-api-url.php`
  - Expected: All 8 tests pass
  - Expected: API URL includes bot token
  - Expected: No "/bot/" double-slash in URLs

### Manual Tests
- [ ] Submit test order via web form
- [ ] Verify notification received in Telegram
- [ ] Check logs for "Message sent successfully"
- [ ] Verify NO 404 errors in logs
- [ ] Test with multiple authorized users (if available)

### Monitoring (First 24 Hours)
- [ ] Monitor `storage/logs/telegram.log` for errors
- [ ] Monitor `storage/logs/orders.log` for failed notifications
- [ ] Check queue file: `storage/cache/order_queue.json` (should be empty or small)
- [ ] Verify all real orders trigger notifications

## Rollback Plan

If issues occur after deployment:

### Quick Rollback
```bash
# Restore backup files
cp order-submit.php.backup order-submit.php
cp process-order-queue.php.backup process-order-queue.php
```

### Symptoms Requiring Rollback
- ❌ 404 errors return in logs
- ❌ Notifications stop working
- ❌ Orders fail to submit
- ❌ Queue processor crashes
- ❌ Different error appears

## Success Criteria

✅ All criteria must be met:
1. Order submissions work correctly
2. Telegram notifications arrive to all authorized users
3. No 404 errors in telegram.log
4. No "/bot/" URLs in logs (token present in all URLs)
5. Queue processor runs without errors
6. Webhook continues to work (users can subscribe)
7. Test script passes all 8 tests

## Troubleshooting

### Issue: "Bot token not found"
**Solution**: Ensure .env file exists and has TELEGRAM_BOT_TOKEN set

### Issue: Still getting 404 errors
**Solution**: 
1. Check .env is in correct location (project root)
2. Verify environment loading code is BEFORE `require_once TelegramBot.php`
3. Check file permissions on .env

### Issue: Webhook works but order form doesn't
**Solution**: 
1. Verify order-submit.php has environment loading
2. Check .env path in order-submit.php is correct
3. Clear any PHP opcode cache

### Issue: Queue processor fails
**Solution**:
1. Verify process-order-queue.php has environment loading
2. Check cron job path is correct
3. Ensure .env is readable by cron user

## Contact

If issues persist after following this checklist:
1. Check full error logs: `storage/logs/telegram.log`, `storage/logs/orders.log`
2. Run test script: `php test-telegram-api-url.php`
3. Review documentation: `TELEGRAM_API_URL_FIX.md`

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Status**: Ready for deployment  
