# Admin API Session Fix - Deployment Checklist

## Pre-Deployment Verification

### 1. Session Configuration Check
- [ ] File `includes/admin-session.php` contains:
  ```php
  ini_set('session.cookie_path', '/');
  ini_set('session.cookie_domain', '');
  ```
- [ ] Session name is set to `3DPRINT_ADMIN_SESSION`
- [ ] HttpOnly, SameSite=Lax, Secure flags enabled

### 2. Bootstrap Integration Check
- [ ] `/api/bootstrap.php` loads `includes/admin-session.php` at line 19
- [ ] Load happens BEFORE any session_start() calls
- [ ] Load happens BEFORE autoloader and Eloquent

### 3. API Endpoints Check
- [ ] All API endpoints load `bootstrap.php` OR `admin_auth.php`
- [ ] `admin_auth.php` has conditional check for ADMIN_SESSION_NAME
- [ ] No endpoint calls session_start() before loading session config

### 4. Frontend Configuration Check
- [ ] `js/api-client.js` uses `credentials: 'include'` (line 122)
- [ ] All fetch requests send cookies
- [ ] CSRF token sent in `X-CSRF-Token` header

---

## Deployment Steps

### Step 1: Backup Current State
```bash
# Backup session config file
cp includes/admin-session.php includes/admin-session.php.backup

# Backup database (optional)
php database/backup.php
```

### Step 2: Deploy Modified Files
```bash
# Upload modified files via rsync or git pull
rsync -avz includes/admin-session.php user@server:/var/www/3dprint-omsk.ru/includes/
```

### Step 3: Clear Server-Side Sessions
```bash
# SSH to server
ssh user@server

# Clear old session files
sudo rm -f /var/lib/php/sessions/sess_*

# Or use custom session.save_path if configured
rm -f /path/to/sessions/sess_*
```

### Step 4: Restart PHP-FPM
```bash
# For PHP 8.1
sudo systemctl restart php8.1-fpm

# For PHP 7.4
sudo systemctl restart php7.4-fpm

# For Apache with mod_php
sudo systemctl restart apache2

# For nginx
sudo systemctl reload nginx
```

---

## Post-Deployment Testing

### Test 1: Automated Session Test
```bash
# Via CLI (if PHP CLI available on server)
php test-api-session.php

# Via HTTP
curl -s https://3dprint-omsk.ru/test-api-session.php | jq .
```

**Expected Result**: `"overall_status": "PASSED"`

### Test 2: Manual Login Test
1. Open browser (incognito mode)
2. Navigate to `https://3dprint-omsk.ru/admin/`
3. Enter credentials and login
4. Verify dashboard loads without errors
5. Open DevTools → Network tab
6. Check any API request (e.g., orders, settings)
7. Verify Cookie header is present in request
8. Verify response is 200 OK

### Test 3: Browser DevTools Inspection
**Open DevTools → Application → Cookies**

Expected cookie:
```
Name:       3DPRINT_ADMIN_SESSION
Value:      [32-char session ID]
Domain:     3dprint-omsk.ru
Path:       /                    ← CRITICAL
Expires:    Session
HttpOnly:   ✓
Secure:     ✓ (if HTTPS)
SameSite:   Lax
```

### Test 4: API Request Verification
**Open DevTools → Network → Select any /api/ request**

Request Headers should include:
```
Cookie: 3DPRINT_ADMIN_SESSION=abc123...
X-CSRF-Token: xyz789...
```

Response should be:
```
Status: 200 OK
Body: { "success": true, "data": {...} }
```

### Test 5: All Admin Modules
Test each admin module to ensure API access works:

- [ ] Dashboard → Statistics widgets load
- [ ] Orders → Orders list displays
- [ ] Services → Services list displays
- [ ] Portfolio → Portfolio items display
- [ ] Testimonials → Testimonials display
- [ ] FAQ → FAQ items display
- [ ] Forms → Forms list displays
- [ ] Submissions → Submissions list displays
- [ ] Settings → Settings groups load
- [ ] Calculator Settings → Settings load
- [ ] Users → Users list displays (super_admin only)
- [ ] Audit Logs → Logs display

---

## Validation Criteria

### ✅ Success Indicators
- [ ] Automated test passes (overall_status: PASSED)
- [ ] Login successful without blank page
- [ ] Dashboard loads with data
- [ ] All API requests return 200 OK
- [ ] Cookie header present in all API requests
- [ ] Cookie path is `/` (not `/admin/`)
- [ ] No "No session found" errors in browser console
- [ ] All admin modules functional
- [ ] Session persists across page navigation

### ❌ Failure Indicators
- Automated test fails
- API returns 401 Unauthorized
- Cookie path is not `/`
- "No session found" errors in console
- Admin modules don't load data
- Session lost after refresh

---

## Rollback Plan

If issues occur after deployment:

### Step 1: Restore Backup
```bash
# SSH to server
ssh user@server

# Restore backup file
cp includes/admin-session.php.backup includes/admin-session.php
```

### Step 2: Restart Services
```bash
sudo systemctl restart php8.1-fpm
sudo systemctl reload nginx
```

### Step 3: Clear Browser State
```
1. DevTools → Application → Clear site data
2. Close all browser tabs
3. Try login again
```

### Step 4: Verify Rollback
```bash
curl -s https://3dprint-omsk.ru/test-api-session.php | jq '.tests.session_config'
```

---

## Troubleshooting

### Issue: Test Script Fails

**Check session.cookie_path:**
```bash
php -r "require 'includes/admin-session.php'; echo ini_get('session.cookie_path');"
# Should output: /
```

### Issue: Cookie Not Sent

**Check browser DevTools:**
1. Network tab → Select API request
2. Request Headers → Look for Cookie
3. If missing, check Application → Cookies
4. Verify path is `/` (not `/admin/`)

### Issue: Cookie Path Wrong

**Verify web server config:**
```bash
# Check nginx config
grep -r "cookie" /etc/nginx/sites-available/

# Check apache config
grep -r "cookie" /etc/apache2/sites-available/
```

Remove any lines like:
- `add_header Set-Cookie ...` (nginx)
- `Header set Set-Cookie ...` (apache)

### Issue: Session Data Lost

**Check session save path:**
```bash
# Check current save path
php -r "echo ini_get('session.save_path');"

# Check permissions
ls -la /var/lib/php/sessions/

# Should be: drwx-wx-wt (writable by www-data)
```

---

## Support Documentation

- **Full Documentation**: [ADMIN_API_SESSION_COMPREHENSIVE_FIX.md](ADMIN_API_SESSION_COMPREHENSIVE_FIX.md)
- **Quick Summary**: [ADMIN_API_SESSION_FIX_SUMMARY.md](ADMIN_API_SESSION_FIX_SUMMARY.md)
- **Testing**: Run `php test-api-session.php`
- **Troubleshooting**: [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)
- **Security Guide**: [docs/SECURITY.md](docs/SECURITY.md)

---

## Completion Sign-Off

**Deployment Date**: _______________  
**Deployed By**: _______________  
**Test Results**: PASS / FAIL  
**Issues Found**: _______________  
**Sign-Off**: _______________  

---

**Last Updated**: 2024  
**Version**: 2.0  
**Status**: Ready for Production ✅
