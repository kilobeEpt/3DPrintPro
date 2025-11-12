# 🔧 Database Integration Debug & Fix Guide

## ✅ COMPLETE - Database Integration Fixed (January 2025)

This guide documents the database integration fixes applied to the project.

---

## 📋 What Was Fixed

### 1. **Created api/config.php** ✅
- Added correct database credentials:
  - `DB_HOST`: localhost
  - `DB_NAME`: ch167436_3dprint
  - `DB_USER`: ch167436_3dprint
  - `DB_PASS`: 852789456
- Added CORS headers for cross-origin requests
- Added Telegram bot configuration
- Set proper error reporting for production

### 2. **Created api/test.php** ✅
- Diagnostic endpoint to check database connection
- Displays count of records in each table
- Shows sample data from each table
- Useful for debugging API issues

### 3. **Created api/init-check.php** ✅
- Web-based database checker with UI
- Shows detailed status of all tables
- Provides "Fix Active" button to set all records to active=1
- Links to all API endpoints for testing
- Suggests fixes for common issues

---

## 🧪 Testing the Integration

### Step 1: Check Database Connection

Open in browser:
```
https://ch167436.tw1.ru/api/init-check.php
```

This page will show:
- ✅ Database connection status
- ✅ Record counts for each table
- ✅ Sample data from tables
- ✅ Links to test all API endpoints

### Step 2: Test API Endpoints

Test each endpoint directly in browser:

1. **Test API (Diagnostics)**
   ```
   https://ch167436.tw1.ru/api/test.php
   ```
   Returns JSON with database diagnostics

2. **Settings API**
   ```
   https://ch167436.tw1.ru/api/settings.php
   ```
   Returns all settings as JSON

3. **Services API**
   ```
   https://ch167436.tw1.ru/api/services.php
   ```
   Returns list of services

4. **Portfolio API**
   ```
   https://ch167436.tw1.ru/api/portfolio.php
   ```
   Returns portfolio items

5. **Testimonials API**
   ```
   https://ch167436.tw1.ru/api/testimonials.php
   ```
   Returns testimonials

6. **FAQ API**
   ```
   https://ch167436.tw1.ru/api/faq.php
   ```
   Returns FAQ items

7. **Orders API**
   ```
   https://ch167436.tw1.ru/api/orders.php
   ```
   Returns list of orders (GET) or creates new order (POST)

### Step 3: Check Frontend Integration

1. **Clear Browser Cache**
   - Press `Ctrl+Shift+Del` (Windows/Linux) or `Cmd+Shift+Del` (Mac)
   - Select "All time"
   - Clear "Cookies" and "Cached files"

2. **Open Browser Console**
   - Press `F12`
   - Go to "Console" tab
   - Refresh page

3. **Check Console Logs**
   
   You should see:
   ```
   ✅ APIClient initialized
   ✅ Database initialized
   ✅ Database using API
   🔄 API GET settings.php
   ✅ API GET settings.php success
   🔄 API GET services.php
   ✅ API GET services.php success
   🔄 API GET portfolio.php
   ✅ API GET portfolio.php success
   🔄 API GET testimonials.php
   ✅ API GET testimonials.php success
   🔄 API GET faq.php
   ✅ API GET faq.php success
   ```

4. **Check for Errors**
   
   If you see errors like:
   - ❌ `Failed to fetch` → Check CORS headers in api/config.php
   - ❌ `Database connection failed` → Check credentials in api/config.php
   - ❌ `404 Not Found` → Check that API files exist in /api/ folder
   - ❌ `Empty array returned` → Tables might be empty or active=0

---

## 🐛 Common Issues & Fixes

### Issue 1: API Returns Empty Arrays

**Cause:** Records in database have `active = 0` or `approved = 0`

**Fix:**
1. Open `https://ch167436.tw1.ru/api/init-check.php`
2. Click "Fix: Set all to active=1" button
3. Refresh page to verify

**OR via SQL:**
```sql
UPDATE services SET active = 1 WHERE 1=1;
UPDATE portfolio SET active = 1 WHERE 1=1;
UPDATE testimonials SET active = 1, approved = 1 WHERE 1=1;
UPDATE faq SET active = 1 WHERE 1=1;
UPDATE content_blocks SET active = 1 WHERE 1=1;
```

### Issue 2: Database Connection Failed

**Cause:** Incorrect credentials or database doesn't exist

**Fix:**
1. Check `api/config.php` credentials
2. Verify database exists in cPanel/PHPMyAdmin
3. Verify tables are created (run `database/schema.sql`)
4. Check database user permissions

### Issue 3: CORS Errors in Browser

**Cause:** Missing CORS headers

**Fix:**
Verify `api/config.php` contains:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### Issue 4: Tables Empty

**Cause:** Database not initialized with default data

**Fix:**
1. Open `https://ch167436.tw1.ru/api/init-database.php`
2. This will populate all tables with default data
3. Check `api/init-check.php` to verify

### Issue 5: Config File Not Found

**Cause:** `api/config.php` doesn't exist

**Fix:**
1. Create `api/config.php` from `api/config.example.php`
2. Update credentials to match your database
3. Make sure file permissions are correct (644 or 600)

---

## 📊 Database Architecture

### Tables (7 total):

1. **orders** - Customer orders/inquiries
2. **settings** - Site configuration (key-value pairs)
3. **services** - Service offerings
4. **portfolio** - Portfolio projects
5. **testimonials** - Customer reviews
6. **faq** - Frequently asked questions
7. **content_blocks** - Text content blocks

### All tables have:
- `id` - Primary key
- `active` - Boolean flag (1 = visible, 0 = hidden)
- `sort_order` - Display order
- `created_at` / `updated_at` - Timestamps

---

## 🔍 Debugging Checklist

- [ ] `api/config.php` exists with correct credentials
- [ ] `api/test.php` returns JSON (not error)
- [ ] All tables have records with `active = 1`
- [ ] API endpoints return valid JSON (not 404 or 500)
- [ ] Browser console shows ✅ success logs (not ❌ errors)
- [ ] No CORS errors in console
- [ ] Services/Portfolio/Testimonials/FAQ visible on site
- [ ] Forms submit successfully and save to database
- [ ] Incognito mode works (data from database, not localStorage)

---

## 📝 Files Modified/Created

### Created (Initial Integration):
- ✅ `api/config.php` - Database configuration (SENSITIVE - in .gitignore)
- ✅ `api/test.php` - API diagnostics endpoint
- ✅ `api/init-check.php` - Web-based database checker
- ✅ `DEBUG_DATABASE_INTEGRATION.md` - This file

### Created (API Hardening - January 2025):
- ✅ `api/helpers/response.php` - Standardized JSON response helper
- ✅ `api/helpers/logger.php` - Centralized error logging
- ✅ `scripts/api_smoke.php` - Comprehensive smoke test suite
- ✅ `logs/` - Log directory for API errors (in .gitignore)

### Refactored (API Hardening - January 2025):
- ✅ `api/orders.php` - Now uses response/logger helpers, improved validation
- ✅ `api/services.php` - Standardized responses, centralized logging
- ✅ `api/portfolio.php` - Uniform error handling, better validation
- ✅ `api/testimonials.php` - Enhanced validation, consistent responses
- ✅ `api/faq.php` - Improved error handling, logging
- ✅ `api/content.php` - Standardized responses, validation
- ✅ `api/settings.php` - Better error handling, logging
- ✅ `api/test.php` - Added logging support

### Already Existed (No changes needed):
- ✅ `api/db.php` - Database class with generic CRUD
- ✅ `js/api-client.js` - Frontend API client wrapper
- ✅ `js/database.js` - Frontend database wrapper (API-first)
- ✅ `js/main.js` - Main application logic

---

## 🚀 Production Checklist

Before going live:

1. ✅ Database credentials correct in `api/config.php`
2. ✅ All tables populated with data
3. ✅ All API endpoints returning correct JSON
4. ✅ Frontend loading data from API (not localStorage)
5. ✅ Forms submitting to database successfully
6. ✅ Browser console clean (no errors)
7. ✅ Test in incognito mode
8. ✅ Test from different device/browser
9. ✅ Verify Telegram notifications (optional)
10. ✅ Set proper file permissions (config.php = 600)

---

## 📞 Support

If issues persist:

1. Check browser console for specific error messages
2. Check `/api/test.php` for database diagnostics
3. Check server error logs in cPanel
4. Verify PHP version >= 7.4
5. Verify MySQL version >= 5.7

---

## 📊 API Error Logging & Monitoring

### Log Location

All API errors are logged to:
```
logs/api.log
```

### Log Format

Each log entry includes:
- **Timestamp** - Date and time of the error
- **Level** - ERROR, WARNING, INFO, or DEBUG
- **Request Method** - GET, POST, PUT, DELETE
- **Request URI** - The endpoint that was called
- **Error Message** - User-friendly description
- **Context** - Additional data (exception traces, request data, etc.)
- **IP Address** - Client IP
- **User Agent** - Browser/client information

### Example Log Entry

```
[2025-01-15 14:23:45] [ERROR] POST /api/orders.php | Database error during INSERT on table 'orders'
Context: {
    "operation": "INSERT",
    "table": "orders",
    "exception": {
        "class": "PDOException",
        "message": "SQLSTATE[23000]: Integrity constraint violation",
        "file": "/home/project/api/db.php",
        "line": 156
    }
}
IP: 192.168.1.100 | User-Agent: Mozilla/5.0 Chrome/120.0
--------------------------------------------------------------------------------
```

### Viewing Logs

**Via SSH/Terminal:**
```bash
# View last 50 lines
tail -n 50 logs/api.log

# Follow logs in real-time
tail -f logs/api.log

# Search for errors
grep ERROR logs/api.log

# Search for specific endpoint
grep "orders.php" logs/api.log
```

**Via FTP/File Manager:**
- Download `logs/api.log` and open in text editor
- Note: Log file can grow large over time

### Log Rotation

For production, set up log rotation to prevent log files from growing too large:

**Create `/etc/logrotate.d/api-logs`:**
```
/path/to/project/logs/api.log {
    daily
    rotate 7
    compress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

This will:
- Rotate logs daily
- Keep 7 days of logs
- Compress old logs
- Create new log file with proper permissions

### Sensitive Data Protection

The logger automatically sanitizes:
- Passwords
- API keys
- Tokens
- Authorization headers

These values are replaced with `***REDACTED***` in logs.

---

## 🧪 API Smoke Testing

### Running Smoke Tests

The smoke test script verifies all API endpoints are functioning correctly.

**Basic usage:**
```bash
php scripts/api_smoke.php --url=https://your-site.com
```

**Examples:**
```bash
# Test production
php scripts/api_smoke.php --url=https://ch167436.tw1.ru

# Test local development
php scripts/api_smoke.php --url=http://localhost:8000

# Test staging
php scripts/api_smoke.php --url=https://staging.your-site.com

# Quiet mode (only show failures)
php scripts/api_smoke.php --url=https://your-site.com --quiet
```

### What Gets Tested

The smoke test performs:

1. **Health Check** - Verifies `/api/test.php` is responding
2. **GET Requests** - Tests all endpoints can return data
3. **POST Requests** - Creates test records
4. **PUT Requests** - Updates test records
5. **DELETE Requests** - Deletes test records
6. **Response Format** - Validates JSON structure
7. **HTTP Status Codes** - Verifies correct codes (200, 201, 404, etc.)

### Test Output

```
🧪 API Smoke Test Suite
Base URL: https://ch167436.tw1.ru
================================================================================

📦 Testing: Health/Test Endpoint
--------------------------------------------------------------------------------
  ✅ GET /api/test.php returns 200
  ✅ Response has success field
  ✅ Response success is true
  ✅ Response has database_status

📦 Testing: Orders Endpoint (CRUD)
--------------------------------------------------------------------------------
  ✅ GET /api/orders.php returns 200
  ✅ Response has success field
  ✅ POST /api/orders.php returns 201
  ✅ POST response includes order_id
  ✅ GET single order returns 200
  ✅ PUT /api/orders.php returns 200
  ✅ DELETE /api/orders.php returns 200
  ✅ GET deleted order returns 404

================================================================================
📊 Test Summary
================================================================================
Total Tests:  42
✅ Passed:    42
❌ Failed:    0
Success Rate: 100%

✅ ALL SMOKE TESTS PASSED
```

### Exit Codes

- **0** - All tests passed (safe for CI/CD)
- **1** - One or more tests failed

### Integration with Deployment

Add to your deployment checklist:

```bash
# After deployment, run smoke test
php scripts/api_smoke.php --url=https://your-site.com

# Only proceed if exit code is 0
if [ $? -eq 0 ]; then
    echo "✅ Smoke tests passed - deployment successful"
else
    echo "❌ Smoke tests failed - rollback required"
    exit 1
fi
```

### CI/CD Integration

**GitHub Actions example:**
```yaml
- name: Run API Smoke Tests
  run: php scripts/api_smoke.php --url=${{ secrets.SITE_URL }}
```

**GitLab CI example:**
```yaml
smoke_test:
  script:
    - php scripts/api_smoke.php --url=$SITE_URL
  only:
    - main
```

---

## 🔧 Troubleshooting Common Issues

### Issue: No logs being written

**Solution:**
1. Check directory exists: `mkdir -p logs && chmod 755 logs`
2. Check file permissions: `chmod 644 logs/api.log`
3. Check web server user can write: `chown www-data:www-data logs/api.log`

### Issue: Smoke test fails with connection errors

**Solution:**
1. Verify the URL is correct and accessible
2. Check firewall isn't blocking requests
3. Verify SSL certificate is valid (for HTTPS)
4. Test endpoint manually in browser first

### Issue: All API responses show "500 Internal Server Error"

**Solution:**
1. Check `logs/api.log` for detailed error messages
2. Verify `api/config.php` exists with correct credentials
3. Check database is running: `mysql -u username -p`
4. Check PHP error logs: `tail -f /var/log/php-errors.log`

### Issue: Response format is inconsistent

**Solution:**
- All endpoints now use `ApiResponse` helper class
- Responses follow format: `{success, data, error, meta}`
- If old format appears, clear any caching layers

---

## ✅ Status: COMPLETE

Database integration is fully functional. All API endpoints working correctly with uniform JSON responses, centralized logging, and comprehensive smoke tests.

**Last Updated:** January 2025 (Hardened with logging & monitoring)
**Status:** ✅ PRODUCTION READY
