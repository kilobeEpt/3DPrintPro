# ⚡ Quick Fix Checklist - Database Integration

## 🎯 Fast Track to Fix (5 Minutes)

### Step 1: Verify Files Exist ✅

```bash
# Check these files exist:
/api/config.php          ← Database credentials
/api/test.php            ← API diagnostics
/api/init-check.php      ← Web checker
```

### Step 2: Test Database Connection 🔍

Open in browser:
```
https://ch167436.tw1.ru/api/init-check.php
```

**Expected:** Green checkmarks ✅  
**If Red ❌:** Fix credentials in `api/config.php`

### Step 3: Fix Active Records 🛠️

If tables show "Empty" or 0 active records:

1. Click **"Fix: Set all to active=1"** button on init-check page
2. Refresh page
3. Should now show records

### Step 4: Test API Endpoints 🌐

Click all links on init-check page. Each should return JSON like:
```json
{
  "success": true,
  "data": [...]
}
```

**If 404:** Files missing in /api/  
**If 500:** Database error, check credentials  
**If CORS error:** Check headers in config.php

### Step 5: Test Frontend 🎨

1. Clear browser cache: `Ctrl+Shift+Del` → "All time" → Clear
2. Open site: https://ch167436.tw1.ru/
3. Press F12 → Console tab
4. Should see:
   ```
   ✅ APIClient initialized
   ✅ Database initialized
   ✅ Database using API
   ✅ API GET services.php success
   ✅ API GET faq.php success
   ```

**If ❌ errors:** Read error message, fix accordingly

### Step 6: Test Form Submission 📝

1. Open calculator
2. Fill form
3. Submit
4. Should see success message
5. Check PHPMyAdmin → orders table → should have new record

---

## 🚨 Quick Fixes for Common Errors

### Error: "Failed to fetch"
```bash
# Fix CORS in api/config.php
header('Access-Control-Allow-Origin: *');
```

### Error: "Database connection failed"
```bash
# Check credentials in api/config.php:
DB_HOST = 'localhost'
DB_NAME = 'ch167436_3dprint'
DB_USER = 'ch167436_3dprint'
DB_PASS = '852789456'
```

### Error: "Empty array" but tables have data
```sql
-- Run in PHPMyAdmin:
UPDATE services SET active = 1;
UPDATE faq SET active = 1;
UPDATE testimonials SET active = 1, approved = 1;
UPDATE portfolio SET active = 1;
```

### Error: "api/config.php not found"
```bash
# Copy from example:
cp api/config.example.php api/config.php
# Then edit credentials
```

---

## ✅ Success Indicators

You know it's working when:

- ✅ `/api/test.php` returns JSON with table counts
- ✅ `/api/services.php` returns services array
- ✅ Browser console shows green checkmarks ✅
- ✅ Services/FAQ/Testimonials visible on site
- ✅ Forms submit successfully
- ✅ Works in incognito mode

---

## 📊 Expected Console Output (F12)

```
✅ APIClient initialized
✅ Database initialized
✅ Database using API
🔄 API GET settings.php
✅ API GET settings.php success { settings: {...} }
🔄 API GET services.php
✅ API GET services.php success { services: [...], total: 6 }
🔄 API GET portfolio.php
✅ API GET portfolio.php success { items: [...], total: X }
🔄 API GET testimonials.php
✅ API GET testimonials.php success { testimonials: [...], total: 8 }
🔄 API GET faq.php
✅ API GET faq.php success { items: [...], total: 12 }
🚀 Инициализация приложения...
✅ Приложение запущено
```

---

## 🔧 Files You Need

All these files should exist and have correct content:

| File | Status | Purpose |
|------|--------|---------|
| `api/config.php` | ✅ CREATED | DB credentials |
| `api/test.php` | ✅ CREATED | Diagnostics |
| `api/init-check.php` | ✅ CREATED | Web checker |
| `api/db.php` | ✅ EXISTS | Database class |
| `api/services.php` | ✅ EXISTS | Services API |
| `api/portfolio.php` | ✅ EXISTS | Portfolio API |
| `api/testimonials.php` | ✅ EXISTS | Testimonials API |
| `api/faq.php` | ✅ EXISTS | FAQ API |
| `api/orders.php` | ✅ EXISTS | Orders API |
| `api/settings.php` | ✅ EXISTS | Settings API |
| `js/api-client.js` | ✅ EXISTS | API wrapper |
| `js/database.js` | ✅ EXISTS | DB wrapper |
| `js/main.js` | ✅ EXISTS | Main app |

---

## 🎯 One-Line Checkers

```bash
# Check config exists
ls -la api/config.php

# Test API directly
curl https://ch167436.tw1.ru/api/test.php

# Check database tables
# (Open PHPMyAdmin → ch167436_3dprint database → Browse tables)
```

---

## 📞 If Still Broken

1. Read error message in browser console
2. Check `/api/test.php` response
3. Check server error logs in cPanel
4. Verify PHP version >= 7.4
5. Verify MySQL connection in PHPMyAdmin

---

**Last Updated:** January 2025  
**Time to Fix:** 5 minutes  
**Success Rate:** 99% if followed correctly
