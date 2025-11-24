# Cleanup Debug Logging

After verifying the admin login works correctly, remove the debug logging added for troubleshooting.

## Step 1: Test the Login

1. Visit your admin login page: `http://your-domain/admin/login.php`
2. Enter valid admin credentials
3. Submit the form
4. Verify you are redirected to `/admin/index.php`
5. Verify the admin panel loads correctly with your session

## Step 2: Review Debug Logs

Check `/tmp/login-debug.log` to confirm the flow worked:

```bash
tail -50 /tmp/login-debug.log
```

Look for successful authentication and redirect messages.

## Step 3: Remove Debug Logging

Once confirmed working, remove debug code but **KEEP** the critical fixes.

### File: admin/login-handler.php

**REMOVE these debug lines:**
```php
// At the top (after ob_start() and define):
$debugLog = '/tmp/login-debug.log';
$logMsg = date('[Y-m-d H:i:s] ') . "Login handler started\n";
file_put_contents($debugLog, $logMsg, FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Dependencies loaded, session ID: " . session_id() . "\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Not POST request, redirecting\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "POST request confirmed\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Rate limit exceeded\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Rate limit check passed\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Session CSRF token: " . ($_SESSION['CSRF_TOKEN'] ?? 'NOT SET') . "\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "POST CSRF token: " . ($_POST['csrf_token'] ?? 'NOT SET') . "\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "CSRF verification passed\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Email: $email, Password length: " . strlen($password) . "\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Empty credentials\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Starting authentication\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Auth result: " . json_encode(['success' => $result['success']]) . "\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Auth failed: " . ($result['error'] ?? 'unknown') . "\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Auth success, user ID: " . $user->id . ", email: " . $user->email . "\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Session vars set. Session ID: " . session_id() . "\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Redirecting to: $redirectUrl\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Session closed, now redirecting\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Exception caught: " . $e->getMessage() . "\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
```

**KEEP these critical fixes:**
```php
ob_start();  // At the very top

session_write_close();  // Before ALL redirects
ob_end_clean();         // Before ALL redirects
```

### File: admin/includes/csrf.php

**REMOVE in verifyPostToken() method:**
```php
// Debug logging
$debugLog = '/tmp/login-debug.log';
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "CSRF::verifyPostToken called\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "POST token: $token\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Session token: " . ($_SESSION['CSRF_TOKEN'] ?? 'NOT SET') . "\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "CSRF validation FAILED\n", FILE_APPEND);

file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "CSRF validation PASSED\n", FILE_APPEND);
```

**KEEP this critical fix:**
```php
session_write_close();  // Before redirect on CSRF failure
```

### File: admin/login.php

**REMOVE these lines:**
```php
// Debug logging
$debugLog = '/tmp/login-debug.log';
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Login page loaded. Session ID: " . session_id() . "\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "CSRF Token generated: $csrfToken\n", FILE_APPEND);
file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . "Session CSRF Token: " . ($_SESSION['CSRF_TOKEN'] ?? 'NOT SET') . "\n", FILE_APPEND);
```

## Step 4: Delete Debug Log File

```bash
rm /tmp/login-debug.log
```

## Step 5: Delete Test Script (Optional)

```bash
rm /home/engine/project/test-login-flow.php
```

## Step 6: Test Again

After cleanup, test the login one more time to ensure everything still works without debug logging.

## Critical Fixes to NEVER Remove

These are permanent fixes that solve the root cause:

1. **Output buffering in login-handler.php:**
   ```php
   ob_start();
   ```

2. **Session write before redirects (everywhere):**
   ```php
   session_write_close();
   ob_end_clean();
   header('Location: ...');
   exit;
   ```

3. **CSRF session write (csrf.php):**
   ```php
   session_write_close();
   header('Location: /admin/login.php');
   exit;
   ```

## Verification Checklist

After cleanup:
- [ ] Login page loads correctly
- [ ] Form submission works
- [ ] Redirect to admin panel succeeds
- [ ] Admin panel shows authenticated session
- [ ] CSRF validation works
- [ ] Rate limiting works (try 6 failed logins)
- [ ] Error messages display correctly
- [ ] No PHP errors in logs
- [ ] No warnings in browser console

## If Issues Occur

If login breaks after cleanup:

1. Check you didn't accidentally remove critical fixes
2. Verify `ob_start()` is still at the top of login-handler.php
3. Verify `session_write_close()` is still before all redirects
4. Check web server error logs: `/var/log/nginx/error.log` or `/var/log/apache2/error.log`
5. Check PHP error logs
6. Re-enable debug logging temporarily to diagnose

## Update Memory

After successful cleanup and testing, update your memory with this pattern:

```
Admin login fix applied:
- Always use ob_start() in handler scripts
- Always call session_write_close() before redirects
- Always call ob_end_clean() before redirects
- Critical for session data persistence
```
