# 🚀 Deployment Notes - Database Integration Fix

## ⚠️ IMPORTANT: Critical File Not in Git

### api/config.php - MUST BE ON SERVER

**This file contains sensitive database credentials and is NOT tracked in git.**

**Location:** `/api/config.php`

**Content (for ch167436.tw1.ru):**
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ch167436_3dprint');
define('DB_USER', 'ch167436_3dprint');
define('DB_PASS', '852789456');
define('DB_CHARSET', 'utf8mb4');

define('TELEGRAM_BOT_TOKEN', '8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI');
define('TELEGRAM_CHAT_ID', '');

define('SITE_URL', 'https://ch167436.tw1.ru');
define('SITE_NAME', '3D Print Pro');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
?>
```

---

## 📦 Deployment Steps

### 1. Verify api/config.php exists on server

**Check file:**
```bash
# SSH to server
ssh your_server

# Check file exists
ls -la /path/to/project/api/config.php
```

**If file doesn't exist:**
```bash
# Create from example
cp api/config.example.php api/config.php

# Edit credentials
nano api/config.php
```

**Set correct permissions:**
```bash
chmod 600 api/config.php
```

### 2. Upload new files from git

These files are NEW in this branch:
- `api/test.php` ← Diagnostics endpoint
- `api/init-check.php` ← Web checker
- `DEBUG_DATABASE_INTEGRATION.md` ← Documentation
- `QUICK_FIX_CHECKLIST.md` ← Quick fixes
- `DATABASE_FIX_SUMMARY.md` ← Summary
- `README_DATABASE_FIX.md` ← Main readme

**Upload via:**
- FTP/SFTP
- Git pull on server
- cPanel File Manager
- SSH/rsync

### 3. Test deployment

**A. Test API diagnostics:**
```bash
curl https://ch167436.tw1.ru/api/test.php
```

Expected: JSON with `"success": true`

**B. Open web checker:**
```
https://ch167436.tw1.ru/api/init-check.php
```

Expected: Green checkmarks ✅

**C. Test frontend:**
1. Clear browser cache
2. Open https://ch167436.tw1.ru/
3. Press F12 → Console
4. Look for ✅ success logs

### 4. Fix common issues

**If tables show 0 active records:**
- Open `/api/init-check.php`
- Click "Fix: Set all to active=1"
- Refresh page

**If "config.php not found":**
- Create file as shown in step 1
- Set permissions to 600

**If CORS errors:**
- Verify CORS headers in config.php
- Check `.htaccess` allows headers

---

## 🔒 Security Checklist

- [ ] `api/config.php` file permissions set to 600 (or 644)
- [ ] `api/config.php` NOT committed to git (check .gitignore)
- [ ] Database credentials correct
- [ ] HTTPS enabled on production
- [ ] Error display OFF (`display_errors = 0`)
- [ ] Error logging ON (`log_errors = 1`)

---

## 🧪 Post-Deployment Testing

### Test Checklist:

- [ ] `/api/test.php` returns valid JSON
- [ ] `/api/init-check.php` shows all green ✅
- [ ] Services load on homepage
- [ ] FAQ loads
- [ ] Testimonials load
- [ ] Portfolio loads
- [ ] Calculator form works
- [ ] Contact form works
- [ ] Orders save to database
- [ ] Browser console clean (no ❌ errors)
- [ ] Works in incognito mode
- [ ] Works on mobile

---

## 📊 What Changed in This Deploy

### New Files:
- ✅ `api/test.php` - Diagnostics
- ✅ `api/init-check.php` - Web checker
- ✅ Documentation files (4 files)

### Modified Files:
- None (all existing API files unchanged)

### Critical File (NOT in git):
- ⚠️ `api/config.php` - MUST be on server with correct credentials

---

## 🔄 Rollback Plan

If something goes wrong:

### Option 1: Keep new files, fix config
```bash
# Just fix api/config.php credentials
nano api/config.php
```

### Option 2: Remove new diagnostic files
```bash
# Remove new test files (safe, won't break site)
rm api/test.php
rm api/init-check.php
```

### Option 3: Full rollback
```bash
# Revert to previous git commit
git checkout previous_branch
```

---

## 📞 Monitoring

After deployment, monitor:

1. **Server Logs**
   - Check for PHP errors
   - Check for database connection errors
   - Path: `/var/log/apache2/error.log` or cPanel logs

2. **API Endpoints**
   - Monitor `/api/test.php` for status
   - Check response times

3. **Browser Console**
   - Check for JavaScript errors
   - Verify API calls succeed

---

## ✅ Success Criteria

Deployment is successful when:

- ✅ No errors in server logs
- ✅ `/api/test.php` returns success
- ✅ `/api/init-check.php` shows green
- ✅ Frontend loads data correctly
- ✅ Forms submit successfully
- ✅ No console errors
- ✅ Works in multiple browsers
- ✅ Works in incognito mode

---

## 📝 Notes for Production

### Database Credentials (ch167436.tw1.ru):
```
Host: localhost
Database: ch167436_3dprint
User: ch167436_3dprint
Password: 852789456
```

### File Paths:
```
Project root: /home/ch167436/domains/ch167436.tw1.ru/public_html/
Config file: /home/ch167436/domains/ch167436.tw1.ru/public_html/api/config.php
```

### URLs:
```
Site: https://ch167436.tw1.ru/
Test API: https://ch167436.tw1.ru/api/test.php
Web Checker: https://ch167436.tw1.ru/api/init-check.php
```

---

## 🎯 Key Points

1. **api/config.php is critical** - Site won't work without it
2. **File must have correct credentials** - Test with /api/test.php
3. **File is NOT in git** - Must be on server separately
4. **Permissions should be 600** - For security
5. **Test after deployment** - Use init-check.php

---

**Deployment Date:** January 2025  
**Branch:** bugfix-db-integration-complete-ch167436  
**Critical Files:** 1 (api/config.php - NOT in git)  
**New Files:** 6 (in git)  
**Risk Level:** Low (diagnostic tools only)  

---

**🚀 Ready for deployment!**
