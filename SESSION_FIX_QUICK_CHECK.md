# Quick Session Fix Verification Guide

## ✅ Status: Implementation Complete

The admin API session fix is **FULLY IMPLEMENTED**. This guide helps you verify it's working correctly.

---

## 🚀 Quick Check (30 seconds)

### 1. Open Diagnostic Tool in Browser

```
https://3dprint-omsk.ru/diagnose-session-cookies.php
```

**Expected Result**: All tests show green ✓ (PASSED)

**If you see red ✗ (FAILED)**: Follow troubleshooting steps below.

---

### 2. Check Session Cookie in DevTools

1. Open: `https://3dprint-omsk.ru/admin/login.php`
2. Press **F12** (open DevTools)
3. Go to **Network** tab
4. Log in with admin credentials
5. Click on any `/api/` request
6. Check **Request Headers** section

**Expected Cookie Header**:
```
Cookie: 3DPRINT_ADMIN_SESSION=abc123def456...
```

**Expected Path** (in Cookies tab):
```
Path: /
```

✅ **If you see this**: Everything is working!
❌ **If Cookie is missing**: See troubleshooting below.

---

### 3. Test Admin Dashboard

1. Log in to admin panel
2. Navigate to Dashboard
3. Check if widgets load data (Recent Orders, Services, etc.)

**Expected**: All widgets load without errors

**If you see** "No session found. Please log in.": 
- Clear browser cookies
- Log in again
- If still fails, see troubleshooting below

---

## 🔧 Troubleshooting

### Issue: Cookie Path is NOT `/`

**Symptom**: Cookie path shows `/admin/` or something else

**Fix**:

1. Edit `includes/admin-session.php`
2. Find line 31: `ini_set('session.cookie_path', '/');`
3. Ensure it's EXACTLY as shown (with `/`)
4. Save file
5. **Clear browser cookies completely**
6. Log in again

### Issue: Cookie Not Sent to /api/*

**Symptom**: Cookie exists but not sent with API requests

**Fix Option 1 - Force Cookie Parameters**:

Edit `includes/admin-session.php`, add after line 45:

```php
// Force cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

**Fix Option 2 - .htaccess** (if on Apache):

Create/edit `.htaccess` in project root:

```apache
<IfModule mod_php.c>
    php_value session.cookie_path /
    php_value session.name 3DPRINT_ADMIN_SESSION
</IfModule>
```

**Fix Option 3 - .user.ini** (shared hosting):

Create `.user.ini` in project root:

```ini
session.cookie_path = /
session.name = 3DPRINT_ADMIN_SESSION
```

Wait 5 minutes for hosting to reload config.

### Issue: Multiple Session Names

**Symptom**: Sometimes `PHPSESSID`, sometimes `3DPRINT_ADMIN_SESSION`

**Fix**:

1. Check all files for `session_start()` calls
2. Ensure they ALL load `includes/admin-session.php` FIRST
3. Never call `session_name()` directly in code

**Verify**:
```bash
grep -r "session_start()" --include="*.php" | grep -v "admin-session.php"
```

All results should load `admin-session.php` or `session-config.php` first.

---

## 🧪 Test Commands

### Check Session Config

```bash
cd /home/engine/project
php -r "require 'includes/admin-session.php'; echo 'Name: ' . ini_get('session.name') . PHP_EOL; echo 'Path: ' . ini_get('session.cookie_path') . PHP_EOL;"
```

**Expected Output**:
```
Name: 3DPRINT_ADMIN_SESSION
Path: /
```

### Test API with Session

```bash
# First, get your session ID from browser DevTools → Cookies
SESSION_ID="your_session_id_here"

curl -v -H "Cookie: 3DPRINT_ADMIN_SESSION=$SESSION_ID" \
  https://3dprint-omsk.ru/api/orders.php
```

**Expected**: JSON response with orders data
**NOT Expected**: "No session found" error

---

## 📞 Need Help?

If after following all steps above, the issue persists:

1. **Capture diagnostics**:
   ```bash
   curl https://3dprint-omsk.ru/test-api-session.php > session-test-results.json
   ```

2. **Get session path**:
   ```bash
   php -r "echo session_save_path();"
   ```

3. **Check permissions**:
   ```bash
   ls -la /var/lib/php/sessions/
   # Or wherever session_save_path points
   ```

4. **Check PHP version**:
   ```bash
   php -v
   ```

5. **Browser info**: Which browser and version?

6. **Hosting environment**: Shared hosting? VPS? Provider name?

With this info, we can diagnose deeper issues.

---

## ✅ Success Criteria

**All of these must be true**:

- ✅ `diagnose-session-cookies.php` shows all tests PASSED
- ✅ Cookie name is `3DPRINT_ADMIN_SESSION` (not `PHPSESSID`)
- ✅ Cookie path is `/` (not `/admin/` or empty)
- ✅ Cookie is sent with `/api/*` requests (visible in DevTools)
- ✅ Admin dashboard loads all widgets without errors
- ✅ Orders, Services, Portfolio modules all work
- ✅ No "No session found" errors in console or UI

**If all above are ✅**: You're done! The fix is working correctly.

---

## 📚 Full Documentation

For complete technical details:
- `ADMIN_API_SESSION_FINAL_FIX.md` - Full implementation guide (100+ lines)
- `ADMIN_API_SESSION_COMPREHENSIVE_FIX.md` - Version 2.0 details (452 lines)
- `test-api-session.php` - Automated test script (7 tests)
- `diagnose-session-cookies.php` - Visual diagnostic tool (HTML)

---

**Last Updated**: <?php echo date('Y-m-d H:i:s'); ?>
