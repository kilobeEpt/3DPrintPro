# Deployment Checklist - Admin Session Synchronization

## Pre-Deployment Checks

### ✅ Code Review
- [ ] Review all changed files
  - [ ] `includes/admin-session.php` (NEW)
  - [ ] `admin/includes/session-config.php` (UPDATED)
  - [ ] `api/helpers/admin_auth.php` (UPDATED)
- [ ] Verify no syntax errors: `php -l includes/admin-session.php`
- [ ] Verify no syntax errors: `php -l admin/includes/session-config.php`
- [ ] Verify no syntax errors: `php -l api/helpers/admin_auth.php`

### ✅ Testing
- [ ] Run automated test suite: `php scripts/test-admin-session-sync.php`
- [ ] Manual login test: Access `/admin/login.php` and log in
- [ ] Check cookie name: Should be `3DPRINT_ADMIN_SESSION` (not `PHPSESSID`)
- [ ] Test authenticated API: `fetch('/api/orders.php')` should return 200
- [ ] Test unauthenticated API: Should return 401
- [ ] Test CSRF validation: POST without token should return 403
- [ ] Test session timeout: Wait 31 minutes, should return 401
- [ ] Test all admin pages: index, orders, services, settings, etc.

### ✅ Documentation
- [ ] `docs/ADMIN_SESSION_SYNC.md` created
- [ ] `docs/SESSION_QUICKSTART.md` created
- [ ] `ADMIN_SESSION_SYNC_IMPLEMENTATION.md` created
- [ ] `scripts/test-admin-session-sync.php` created
- [ ] Update main README if necessary

### ✅ Backup
- [ ] Backup current session files: `cp -r /tmp/sess_* /backup/sessions/`
- [ ] Backup database: `php database/backup.php`
- [ ] Tag current commit: `git tag -a pre-session-sync -m "Before session sync"`

## Deployment Steps

### Step 1: Deploy New Files
```bash
# Upload new shared bootstrap
scp includes/admin-session.php server:/path/to/project/includes/

# Verify file uploaded
ssh server "ls -la /path/to/project/includes/admin-session.php"
```

### Step 2: Update Existing Files
```bash
# Upload updated session config
scp admin/includes/session-config.php server:/path/to/project/admin/includes/

# Upload updated admin auth
scp api/helpers/admin_auth.php server:/path/to/project/api/helpers/

# Verify files updated
ssh server "ls -la /path/to/project/admin/includes/session-config.php"
ssh server "ls -la /path/to/project/api/helpers/admin_auth.php"
```

### Step 3: Deploy Documentation (Optional)
```bash
# Upload documentation
scp docs/ADMIN_SESSION_SYNC.md server:/path/to/project/docs/
scp docs/SESSION_QUICKSTART.md server:/path/to/project/docs/
scp ADMIN_SESSION_SYNC_IMPLEMENTATION.md server:/path/to/project/

# Upload test suite
scp scripts/test-admin-session-sync.php server:/path/to/project/scripts/
```

### Step 4: Verify Deployment
```bash
# Check file permissions
ssh server "ls -la /path/to/project/includes/admin-session.php"
# Should be readable by web server (644 or similar)

# Check file ownership
ssh server "stat -c '%U:%G' /path/to/project/includes/admin-session.php"
# Should be web server user (www-data, apache, etc.)
```

### Step 5: Clear Old Sessions (Optional)
```bash
# CAUTION: This will log out all active admin users
ssh server "rm -rf /tmp/sess_*"
# Or: ssh server "php -r \"session_start(); session_destroy();\""
```

### Step 6: Test in Production
```bash
# Test login
curl -c cookies.txt -X POST https://yoursite.com/admin/login-handler.php \
  -d "login=admin&password=yourpass&csrf_token=..."

# Test authenticated API request
curl -b cookies.txt https://yoursite.com/api/orders.php

# Expected: {"success":true,"data":[...]} (not 401)
```

## Post-Deployment Checks

### ✅ Functionality Tests
- [ ] Admin login works
- [ ] Admin dashboard loads
- [ ] API requests return 200 (not 401)
- [ ] CSRF validation works (403 without token)
- [ ] Session timeout works (401 after 30 minutes)
- [ ] Logout works correctly

### ✅ Browser Tests
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari (if applicable)
- [ ] Edge (if applicable)

### ✅ Server Logs
```bash
# Check for PHP errors
ssh server "tail -f /var/log/php_errors.log"

# Check for web server errors
ssh server "tail -f /var/log/apache2/error.log"
# or: ssh server "tail -f /var/log/nginx/error.log"

# Should see no errors related to sessions
```

### ✅ Session Storage
```bash
# Check session files
ssh server "ls -la /tmp/ | grep sess_"
# Should see session files with 3DPRINT_ADMIN_SESSION prefix

# Check session directory permissions
ssh server "ls -ld /var/lib/php/sessions"
# Should be writable by web server
```

### ✅ Performance
- [ ] Page load times unchanged
- [ ] API response times unchanged
- [ ] No increase in server errors
- [ ] No increase in memory usage

## Monitoring

### First 24 Hours
- [ ] Monitor error logs for session-related errors
- [ ] Check for 401 errors in API requests
- [ ] Verify no CSRF validation failures
- [ ] Confirm admin users can log in and work normally

### Metrics to Watch
```bash
# Count 401 errors (should be low, only for unauthenticated)
grep "401" /var/log/apache2/access.log | wc -l

# Count 403 errors (should be low, only for invalid CSRF)
grep "403" /var/log/apache2/access.log | wc -l

# Count successful API requests (should be high)
grep "api/" /var/log/apache2/access.log | grep "200" | wc -l
```

## Rollback Procedure

### If Issues Arise

#### Step 1: Revert Files
```bash
# Restore original files from backup
scp backup/admin/includes/session-config.php server:/path/to/project/admin/includes/
scp backup/api/helpers/admin_auth.php server:/path/to/project/api/helpers/

# Remove new shared bootstrap
ssh server "rm /path/to/project/includes/admin-session.php"
```

#### Step 2: Clear Sessions
```bash
# Clear all sessions to force fresh start
ssh server "rm -rf /tmp/sess_*"
```

#### Step 3: Verify Rollback
```bash
# Test login
curl -c cookies.txt -X POST https://yoursite.com/admin/login-handler.php \
  -d "login=admin&password=yourpass&csrf_token=..."

# Test admin page access
curl -b cookies.txt https://yoursite.com/admin/index.php
# Should return 200 OK
```

#### Step 4: Investigate Issue
```bash
# Check PHP error log
ssh server "tail -100 /var/log/php_errors.log"

# Check web server error log
ssh server "tail -100 /var/log/apache2/error.log"

# Analyze session behavior
ssh server "php -r 'phpinfo();' | grep session"
```

## Common Issues

### Issue: 401 Errors After Deployment

**Possible Causes:**
1. Old session cookies in browser cache
2. Session files not writable
3. Shared bootstrap not properly included

**Resolution:**
```bash
# Check file permissions
ssh server "ls -la /path/to/project/includes/admin-session.php"
# Should be: -rw-r--r-- (644)

# Check file syntax
ssh server "php -l /path/to/project/includes/admin-session.php"
# Should output: No syntax errors detected

# Clear old sessions
ssh server "rm -rf /tmp/sess_*"

# Test with fresh browser session
# Open incognito/private window and login again
```

### Issue: CSRF Validation Failures

**Possible Causes:**
1. Session not shared between admin pages and API
2. CSRF token not accessible in API context

**Resolution:**
```bash
# Verify admin_auth.php includes bootstrap
ssh server "grep 'admin-session.php' /path/to/project/api/helpers/admin_auth.php"
# Should output: require_once __DIR__ . '/../../includes/admin-session.php';

# Test CSRF token accessibility
# Login and check: console.log(window.ADMIN_SESSION.csrfToken)
# Should output a 64-character hex string
```

### Issue: Session Files Not Created

**Possible Causes:**
1. Session directory not writable
2. PHP session configuration issue

**Resolution:**
```bash
# Check session save path
ssh server "php -r 'echo session_save_path();'"

# Check directory permissions
ssh server "ls -ld $(php -r 'echo session_save_path();')"
# Should be writable by web server (drwxrwx---)

# Set correct permissions
ssh server "chmod 1733 $(php -r 'echo session_save_path();')"
ssh server "chown root:www-data $(php -r 'echo session_save_path();')"
```

## Support Contacts

### Development Team
- **Technical Lead:** [Contact info]
- **DevOps Engineer:** [Contact info]
- **Security Team:** [Contact info]

### Documentation
- Full docs: `docs/ADMIN_SESSION_SYNC.md`
- Quick reference: `docs/SESSION_QUICKSTART.md`
- Implementation summary: `ADMIN_SESSION_SYNC_IMPLEMENTATION.md`

### Emergency Rollback
If critical issues arise and rollback is needed:
1. SSH to server
2. Run: `git checkout HEAD~1 -- admin/includes/session-config.php api/helpers/admin_auth.php`
3. Run: `rm includes/admin-session.php`
4. Clear sessions: `rm -rf /tmp/sess_*`
5. Notify team of rollback

## Sign-Off

- [ ] **Developer:** Code reviewed and tested
- [ ] **QA:** All tests passed
- [ ] **Security:** Security review approved
- [ ] **DevOps:** Deployment procedure reviewed
- [ ] **Product Owner:** Acceptance criteria met

**Deployment Date:** _________________  
**Deployed By:** _________________  
**Verified By:** _________________  

---

**Notes:**
- This is a low-risk deployment (isolated session management change)
- No database migrations required
- No config changes required
- Rollback is simple and quick if needed
- Expected downtime: 0 seconds (zero-downtime deployment)

**Success Criteria:**
✅ Admin login works  
✅ API requests return 200 (not 401)  
✅ CSRF validation works  
✅ No increase in errors  
✅ No user complaints  

**Go/No-Go Decision:** _________________ (Signature)
