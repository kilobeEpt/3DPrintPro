# Troubleshooting Guide

Common issues and solutions for 3D Print Pro.

## Quick Diagnostics

Before troubleshooting, run these diagnostic commands:

### 1. Database Health Check
```bash
# Via browser
https://your-domain.com/api/test.php?audit=full

# Via CLI
php scripts/db_audit.php
```

### 2. API Status
```bash
curl https://your-domain.com/api/test.php
```

### 3. Browser Console
1. Open your site
2. Press `F12` to open DevTools
3. Go to **Console** tab
4. Look for errors (red text)

### 4. Network Requests
1. Open DevTools (`F12`)
2. Go to **Network** tab
3. Reload page
4. Check for failed requests (red)

---

## Database Issues

### Database Connection Failed

**Symptoms:**
- API returns 500 errors
- "Database connection failed" message
- Empty data on frontend
- Forms don't submit

**Diagnostic:**
```bash
curl https://your-domain.com/api/test.php
```

**Solutions:**

1. **Check credentials** in `api/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```

2. **Verify MySQL is running:**
   ```bash
   systemctl status mysql
   # or
   service mysql status
   ```
   If stopped:
   ```bash
   systemctl start mysql
   ```

3. **Test database login:**
   ```bash
   mysql -u your_user -p your_database
   ```
   If fails, password is wrong or user doesn't exist.

4. **Check user privileges:**
   ```sql
   SHOW GRANTS FOR 'your_user'@'localhost';
   ```
   Should have: SELECT, INSERT, UPDATE, DELETE

5. **Run full audit:**
   ```bash
   php scripts/db_audit.php
   ```

### Unknown Database Error

**Symptoms:**
- "Unknown database 'dbname'" error

**Solutions:**

1. **Create database:**
   ```sql
   CREATE DATABASE your_database_name 
   CHARACTER SET utf8mb4 
   COLLATE utf8mb4_unicode_ci;
   ```

2. **Verify database exists:**
   ```sql
   SHOW DATABASES LIKE 'your_database%';
   ```

3. **Check database name** in `api/config.php` matches actual database

### Tables Not Found

**Symptoms:**
- "Table 'dbname.services' doesn't exist"
- API returns empty arrays
- Console shows "undefined" errors

**Diagnostic:**
```sql
SHOW TABLES;
```

**Solutions:**

1. **Import schema:**
   ```bash
   mysql -u user -p database < database/schema.sql
   ```

2. **Verify tables created:**
   ```bash
   php scripts/db_audit.php
   ```

3. **Re-seed data:**
   ```
   https://your-domain.com/api/init-database.php
   ```

### No Data Showing

**Symptoms:**
- Services section empty
- FAQ section empty
- Portfolio empty
- Console shows no errors

**Diagnostic:**
```sql
SELECT COUNT(*) as total, 
       SUM(active=1) as active,
       SUM(active=0) as inactive
FROM services;
```

**Solutions:**

1. **Check if data exists:**
   ```sql
   SELECT * FROM services WHERE active = 1;
   ```

2. **If zero records, seed database:**
   ```
   https://your-domain.com/api/init-database.php
   ```

3. **If data exists but not showing, fix active flags:**
   ```
   https://your-domain.com/api/init-check.php?fix_active=1
   ```

4. **Check API response:**
   ```bash
   curl https://your-domain.com/api/services.php
   ```
   Should return array of services, not empty.

### Schema Drift

**Symptoms:**
- Audit shows "Schema drift detected"
- Missing columns or indexes

**Diagnostic:**
```bash
php database/verify-schema.php
```

**Solutions:**

1. **Backup database first:**
   ```bash
   mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
   ```

2. **Review schema differences:**
   - Compare audit output with `database/schema.sql`

3. **Option A: Manual fix via ALTER TABLE**
   ```sql
   ALTER TABLE services ADD COLUMN featured BOOLEAN DEFAULT FALSE;
   ALTER TABLE orders ADD INDEX idx_status (status);
   ```

4. **Option B: Re-import schema (may lose data)**
   ```bash
   # Only if you have backup!
   mysql -u user -p database < database/schema.sql
   ```

---

## API Issues

### API Returns 500 Error

**Symptoms:**
- All API calls fail with 500
- Console shows "Server Error"

**Diagnostic:**
```bash
curl -i https://your-domain.com/api/test.php
```

**Solutions:**

1. **Check PHP error log:**
   ```bash
   tail -f /var/log/apache2/error.log
   # or
   tail -f /var/log/php-fpm/error.log
   ```

2. **Check API log:**
   ```bash
   tail -f logs/api.log
   ```

3. **Common causes:**
   - Database connection failed (see above)
   - PDO extension not installed
   - Syntax error in PHP files
   - File permissions wrong

4. **Check PHP extensions:**
   ```bash
   php -m | grep -i pdo
   ```
   Should show: PDO, PDO_MySQL

5. **Check file permissions:**
   ```bash
   ls -la api/*.php
   ```
   Should be 644 or 644

### API Returns Empty Response

**Symptoms:**
- No JSON returned
- Blank page

**Diagnostic:**
```bash
curl -v https://your-domain.com/api/services.php
```

**Solutions:**

1. **Check for PHP errors:**
   - Enable error display temporarily:
   ```php
   // In api file, add at top:
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

2. **Check .htaccess:**
   - Verify `api/.htaccess` exists
   - Check no syntax errors

3. **Check mod_rewrite:**
   ```bash
   apache2ctl -M | grep rewrite
   ```

### Rate Limit Exceeded (429)

**Symptoms:**
- "Rate limit exceeded" error
- 429 status code
- `Retry-After` header

**Solutions:**

1. **Wait for rate limit to reset:**
   - Check `Retry-After` header value
   - Wait specified seconds

2. **Increase rate limit** in `api/config.php`:
   ```php
   define('RATE_LIMIT_MAX_REQUESTS', 120); // Was 60
   define('RATE_LIMIT_TIME_WINDOW', 60);
   ```

3. **Clear rate limit files:**
   ```bash
   rm -f logs/rate_limits/*.json
   ```

4. **Check for bot/crawler:**
   - Review access logs
   - Block abusive IPs

### CORS Errors

**Symptoms:**
- "CORS policy blocked" in console
- API calls fail from different domain

**Solutions:**

1. **Check `api/.htaccess`:**
   ```apache
   Header set Access-Control-Allow-Origin "*"
   Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
   ```

2. **Check mod_headers enabled:**
   ```bash
   apache2ctl -M | grep headers
   ```
   If not enabled:
   ```bash
   a2enmod headers
   systemctl restart apache2
   ```

3. **For production, restrict origin:**
   ```apache
   Header set Access-Control-Allow-Origin "https://your-domain.com"
   ```

---

## Frontend Issues

### Console Errors

**Symptoms:**
- Errors in browser console (F12)
- Features not working

**Common Errors:**

#### "APIClient is not defined"

**Cause:** Scripts loaded in wrong order

**Solution:** Check script order in HTML:
```html
<script src="config.js"></script>
<script src="js/api-client.js"></script>
<script src="js/database.js"></script>
<script src="js/utils.js"></script>
<!-- ... other scripts ... -->
<script src="js/main.js"></script>
```

#### "Cannot read property of undefined"

**Cause:** Object not initialized

**Solution:**
- Check if API call succeeded
- Check data structure
- Add null checks:
```javascript
if (data && data.services) {
    // Use data.services
}
```

#### "NetworkError when attempting to fetch"

**Cause:** API not accessible

**Solution:**
- Check API URL in config.js
- Check network connectivity
- Check API returns valid JSON

### Page Not Loading

**Symptoms:**
- Blank page
- Spinner forever
- No content displayed

**Diagnostic:**
1. Open DevTools (F12)
2. Check Console for errors
3. Check Network tab for failed requests

**Solutions:**

1. **Clear browser cache:**
   - Ctrl+Shift+Delete
   - Clear cache and reload

2. **Check API responses:**
   ```bash
   curl https://your-domain.com/api/services.php
   curl https://your-domain.com/api/faq.php
   ```

3. **Check database has data:**
   ```sql
   SELECT COUNT(*) FROM services WHERE active = 1;
   ```

4. **Check JavaScript errors:**
   - Look in console for red errors
   - Fix any syntax errors

### Forms Not Submitting

**Symptoms:**
- Submit button does nothing
- No success/error message
- Order not saved

**Diagnostic:**
1. Check browser console
2. Check Network tab - look for POST request
3. Check API response

**Solutions:**

1. **Check validation:**
   - Ensure all required fields filled
   - Check phone/email format

2. **Check API endpoint:**
   ```bash
   curl -X POST https://your-domain.com/api/orders.php \
     -H "Content-Type: application/json" \
     -d '{"name":"Test","phone":"+79991234567","message":"Test"}'
   ```

3. **Check CSRF token** (if admin form):
   - Verify `window.ADMIN_SESSION.csrfToken` exists
   - Check token sent in request headers

4. **Check rate limiting:**
   - If testing repeatedly, may hit rate limit
   - Wait 60 seconds and try again

### Calculator Not Working

**Symptoms:**
- Price doesn't calculate
- Fields don't respond
- Submit fails

**Solutions:**

1. **Check config.js loaded:**
   ```javascript
   console.log(CONFIG);
   ```

2. **Check calculator.js loaded:**
   ```javascript
   console.log(typeof calculator);
   ```

3. **Check no errors in console**

4. **Verify calculator config:**
   ```javascript
   console.log(CONFIG.calculator);
   ```

---

## Admin Panel Issues

### Cannot Login

**Symptoms:**
- "Invalid credentials" error
- Cannot access admin panel

**Diagnostic:**
```sql
SELECT * FROM settings WHERE setting_key LIKE 'admin%';
```

**Solutions:**

1. **Reset credentials:**
   ```bash
   php scripts/setup-admin-credentials.php admin NewPassword123
   ```

2. **Verify credentials in database:**
   ```sql
   SELECT setting_key, setting_value 
   FROM settings 
   WHERE setting_key IN ('admin_login', 'admin_password_hash');
   ```

3. **Check case-sensitive:**
   - Username is case-sensitive
   - Try exact username from database

4. **Clear browser cookies:**
   - Ctrl+Shift+Delete
   - Clear cookies and try again

### Session Expired Immediately

**Symptoms:**
- Logged out right after login
- Session doesn't persist

**Solutions:**

1. **Check cookies enabled:**
   - Browser settings → Enable cookies
   - Disable any cookie-blocking extensions

2. **Use HTTPS not HTTP:**
   - Session cookies require HTTPS in production
   - Change URL to `https://...`

3. **Check session directory writable:**
   ```bash
   php -i | grep session.save_path
   ls -la /var/lib/php/sessions/
   ```

4. **Check server time:**
   ```bash
   date
   ```
   Time must be accurate for session timeouts.

### CSRF Token Invalid

**Symptoms:**
- 403 error on form submit
- "Invalid CSRF token" error

**Solutions:**

1. **Refresh page:**
   - Press F5 to get new token

2. **Check cookies not blocked:**
   - Disable browser extensions
   - Check browser settings

3. **Check session valid:**
   - Verify logged in
   - Check `window.ADMIN_SESSION` in console

4. **Clear cache:**
   - Ctrl+Shift+R (hard refresh)

### Data Not Loading in Admin

**Symptoms:**
- Empty lists
- "Loading..." forever
- No data displayed

**Solutions:**

1. **Check authentication:**
   ```javascript
   console.log(window.ADMIN_SESSION);
   ```
   Should show `authenticated: true`

2. **Check API responses:**
   - Open Network tab
   - Look for 401/403 errors
   - Check response JSON

3. **Check database:**
   ```bash
   php scripts/db_audit.php
   ```

4. **Re-login:**
   - Logout and login again
   - Session may have expired

---

## Telegram Issues

### Test Message Fails

**Symptoms:**
- "Test message failed" error
- No message received

**Diagnostic:**
```bash
curl -X POST https://your-domain.com/api/telegram-test.php \
  -H "Cookie: PHPSESSID=..." \
  -H "X-CSRF-Token: ..."
```

**Solutions:**

1. **Verify bot token:**
   - Check in admin settings
   - Test manually:
   ```bash
   curl https://api.telegram.org/bot{TOKEN}/getMe
   ```
   Should return bot info.

2. **Verify chat ID:**
   - Check format: `-1001234567890` (with minus for groups)
   - Get from: `https://api.telegram.org/bot{TOKEN}/getUpdates`

3. **Check bot not blocked:**
   - Open Telegram
   - Search for your bot
   - Click "Start" or "Unblock"

4. **Check bot in group:**
   - Bot must be member of group chat
   - Make bot admin if using group

5. **Check error details:**
   ```bash
   tail -f logs/api.log | grep -i telegram
   ```

### Notifications Not Arriving

**Symptoms:**
- Orders created but no Telegram message
- Test works but real orders don't notify

**Solutions:**

1. **Check notifications enabled:**
   - Admin → Settings
   - Verify checkboxes ticked:
     - ☑ Notify about new orders
     - ☑ Notify about status changes

2. **Check order telegram_sent flag:**
   ```sql
   SELECT id, order_number, telegram_sent, telegram_error 
   FROM orders 
   ORDER BY id DESC 
   LIMIT 5;
   ```

3. **Check telegram_error field:**
   ```sql
   SELECT telegram_error FROM orders WHERE telegram_error IS NOT NULL;
   ```

4. **Check API logs:**
   ```bash
   grep -i "telegram" logs/api.log
   ```

5. **Test manually:**
   - Go to admin settings
   - Click "Send Test Message"
   - If works, configuration is correct

### Bot Token Security

**Issue:** Token exposed in logs or frontend

**Solutions:**

1. **Check config.js:**
   ```javascript
   // Should be empty or placeholder
   botToken: ''  // Never put real token here!
   ```

2. **Check logs sanitized:**
   ```bash
   grep -i "bot.*token" logs/api.log
   ```
   Tokens should show as `***REDACTED***`

3. **Regenerate token if exposed:**
   - Message @BotFather
   - Send `/revoke_token`
   - Get new token
   - Update in admin settings

---

## Performance Issues

### Site Loading Slowly

**Symptoms:**
- Pages take 5+ seconds to load
- Images loading slowly
- API calls timing out

**Solutions:**

1. **Check database queries:**
   ```sql
   SHOW PROCESSLIST;
   ```
   Look for long-running queries.

2. **Optimize images:**
   - Compress images under 500KB
   - Use WebP format
   - Lazy load images

3. **Enable caching:**
   ```apache
   # In .htaccess
   ExpiresActive On
   ExpiresByType image/jpg "access plus 1 year"
   ExpiresByType text/css "access plus 1 month"
   ExpiresByType application/javascript "access plus 1 month"
   ```

4. **Check server resources:**
   ```bash
   htop
   # or
   free -h
   df -h
   ```

5. **Optimize database:**
   ```sql
   OPTIMIZE TABLE orders, services, portfolio, testimonials, faq;
   ```

### High Memory Usage

**Symptoms:**
- Server running out of memory
- 502/504 errors
- PHP timeout errors

**Solutions:**

1. **Increase PHP memory limit:**
   ```php
   // In php.ini or .htaccess
   memory_limit = 256M
   ```

2. **Check for memory leaks:**
   ```bash
   top -u www-data
   ```

3. **Optimize queries:**
   - Add LIMIT to queries
   - Use pagination
   - Avoid SELECT *

4. **Clear old logs:**
   ```bash
   find logs/ -name "*.log" -mtime +30 -delete
   find logs/rate_limits/ -name "*.json" -mtime +1 -delete
   ```

---

## Security Issues

### Suspicious Activity

**Symptoms:**
- Unknown orders
- Settings changed
- Unusual login attempts

**Actions:**

1. **Change admin password immediately:**
   ```bash
   php scripts/setup-admin-credentials.php admin NewStrongPassword123
   ```

2. **Check recent orders:**
   ```sql
   SELECT * FROM orders ORDER BY created_at DESC LIMIT 20;
   ```

3. **Check access logs:**
   ```bash
   tail -100 /var/log/apache2/access.log
   ```

4. **Check API logs:**
   ```bash
   grep -i "error\|failed\|unauthorized" logs/api.log
   ```

5. **Review settings changes:**
   ```sql
   SELECT * FROM settings ORDER BY updated_at DESC;
   ```

6. **Enable IP whitelisting** (if possible via hosting panel)

### File Permissions Wrong

**Symptoms:**
- Cannot write logs
- Cannot upload files
- Permission denied errors

**Solutions:**

1. **Fix permissions:**
   ```bash
   # Directories
   chmod 755 admin api css database docs js scripts
   chmod 755 logs
   
   # PHP files
   chmod 644 api/*.php admin/*.php scripts/*.php
   
   # Secure config
   chmod 600 api/config.php
   ```

2. **Check ownership:**
   ```bash
   ls -la api/config.php
   # Should be owned by web server user (www-data, apache, nginx)
   ```

3. **Fix ownership if wrong:**
   ```bash
   chown -R www-data:www-data /path/to/site
   ```

---

## Production Issues

### Site Down / 502 Error

**Diagnostic:**
```bash
# Check if web server running
systemctl status apache2
# or
systemctl status nginx

# Check if PHP-FPM running
systemctl status php-fpm
# or
systemctl status php7.4-fpm

# Check error logs
tail -f /var/log/apache2/error.log
```

**Solutions:**

1. **Restart web server:**
   ```bash
   systemctl restart apache2
   # or
   systemctl restart nginx
   ```

2. **Restart PHP-FPM:**
   ```bash
   systemctl restart php-fpm
   ```

3. **Check disk space:**
   ```bash
   df -h
   ```
   If 100% full, clear logs/cache.

4. **Check memory:**
   ```bash
   free -h
   ```

### SSL Certificate Expired

**Symptoms:**
- Browser shows "Your connection is not private"
- HTTPS not working

**Solutions:**

1. **Renew Let's Encrypt certificate:**
   ```bash
   certbot renew
   systemctl restart apache2
   ```

2. **Check certificate expiry:**
   ```bash
   certbot certificates
   ```

3. **Set up auto-renewal:**
   ```bash
   crontab -e
   # Add:
   0 3 * * * certbot renew --quiet && systemctl reload apache2
   ```

---

## Getting Help

### Diagnostic Information to Collect

When seeking help, provide:

1. **Error messages** (exact text)
2. **Database audit output:**
   ```bash
   php scripts/db_audit.php > audit.txt
   ```
3. **API test output:**
   ```bash
   curl https://your-domain.com/api/test.php?audit=full > api_test.json
   ```
4. **PHP version:** `php -v`
5. **MySQL version:** `mysql --version`
6. **Browser console errors** (screenshot)
7. **Network tab** (screenshot of failed requests)
8. **Recent logs:**
   ```bash
   tail -100 logs/api.log > recent_logs.txt
   ```

### Resources

- [SETUP_GUIDE.md](SETUP_GUIDE.md) - Initial setup
- [DEPLOYMENT.md](DEPLOYMENT.md) - Deployment guide
- [API_REFERENCE.md](API_REFERENCE.md) - API documentation
- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) - Admin panel guide
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) - Database reference
- [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md) - Telegram setup

### Still Having Issues?

1. Review relevant documentation above
2. Check logs for specific error messages
3. Run full diagnostic: `php scripts/db_audit.php --json`
4. Test API manually: `curl https://your-domain.com/api/test.php?audit=full`
5. Search error message online
6. Check GitHub issues (if applicable)
