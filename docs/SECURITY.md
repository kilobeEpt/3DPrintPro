# Security Hardening Guide

## Table of Contents

1. [Overview](#overview)
2. [Security Headers](#security-headers)
3. [Rate Limiting](#rate-limiting)
4. [CSRF Protection](#csrf-protection)
5. [Admin Action Logging](#admin-action-logging)
6. [Database Backups](#database-backups)
7. [Authentication & Authorization](#authentication--authorization)
8. [Best Practices](#best-practices)
9. [Security Testing](#security-testing)
10. [Incident Response](#incident-response)

---

## Overview

3D Print Pro implements multiple layers of security hardening:

- **Security Headers**: CSP, XSS protection, referrer policies
- **Rate Limiting**: Configurable thresholds for all endpoints
- **CSRF Protection**: Token-based protection for state-changing operations
- **Admin Logging**: Comprehensive audit trail of all admin actions
- **Database Backups**: Automated backups with rotation and integrity checks
- **RBAC**: Role-based access control with session management

---

## Security Headers

### Implementation

Enhanced security headers are applied via `SecurityHeaders` class with context-aware policies:

```php
require_once 'api/helpers/security_headers.php';

// For API endpoints
SecurityHeaders::apply(SecurityHeaders::CONTEXT_API);

// For admin pages
SecurityHeaders::apply(SecurityHeaders::CONTEXT_ADMIN);

// For public pages
SecurityHeaders::apply(SecurityHeaders::CONTEXT_PUBLIC);
```

### Headers Applied

#### Universal Headers

- **X-Content-Type-Options**: `nosniff` - Prevents MIME sniffing
- **X-Frame-Options**: `DENY` - Prevents clickjacking
- **X-XSS-Protection**: `1; mode=block` - Legacy XSS protection
- **Referrer-Policy**: `strict-origin-when-cross-origin` - Controls referrer information

#### HTTPS-Only Headers

- **Strict-Transport-Security**: `max-age=31536000; includeSubDomains; preload` - HSTS enforcement

#### Context-Specific Headers

**API Context** (JSON endpoints):
```
Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'
```

**Admin Context**:
```
Content-Security-Policy: 
  default-src 'self'; 
  script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;
  style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com;
  font-src 'self' https://fonts.gstatic.com;
  img-src 'self' data: https:;
  connect-src 'self';
  frame-ancestors 'none';
  base-uri 'self';
  form-action 'self';
  upgrade-insecure-requests
```

**Public Context**:
```
Content-Security-Policy:
  default-src 'self';
  script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://www.googletagmanager.com;
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  img-src 'self' data: https:;
  connect-src 'self' https://www.google-analytics.com;
  frame-src https://www.youtube.com;
  upgrade-insecure-requests
```

#### Permissions Policy

```
Permissions-Policy: 
  camera=(), 
  microphone=(), 
  geolocation=(), 
  interest-cohort=(), 
  payment=(), 
  usb=()
```

### HTML Meta Tags

For static HTML pages where headers cannot be set:

```html
<?php
require_once 'api/helpers/security_headers.php';
echo SecurityHeaders::getSecurityMetaTags(SecurityHeaders::CONTEXT_PUBLIC);
?>
```

Generates:
```html
<meta http-equiv="Content-Security-Policy" content="...">
<meta http-equiv="X-Content-Type-Options" content="nosniff">
<meta name="referrer" content="strict-origin-when-cross-origin">
```

### Verification

Test security headers using browser DevTools or online tools:

1. **Browser DevTools**: Network tab → Select request → Headers
2. **securityheaders.com**: Automated scanning and grading
3. **observatory.mozilla.org**: Mozilla's security scanner

---

## Rate Limiting

### Implementation

Rate limiting is implemented via `RateLimiter` class with predefined profiles:

#### Profiles

| Profile | Max Requests | Time Window | Use Case |
|---------|-------------|-------------|----------|
| `PROFILE_AUTH` | 5 | 15 minutes | Login attempts |
| `PROFILE_API_READ` | 100 | 1 minute | GET requests |
| `PROFILE_API_WRITE` | 30 | 1 minute | POST/PUT/DELETE |
| `PROFILE_ADMIN` | 60 | 1 minute | Admin panel |
| `PROFILE_PUBLIC` | 60 | 1 minute | Public pages |

#### Usage

**In PHP endpoints:**

```php
require_once 'api/helpers/rate_limiter.php';

// Quick application using helper
applyRateLimit(RateLimiter::PROFILE_AUTH, 'login');

// Or with custom limits
$limiter = new RateLimiter(null, 10, 60); // 10 requests per minute
$limiter->apply('custom_endpoint');
```

**In API controllers:**

```php
class MyController extends BaseApiController
{
    public function handle()
    {
        // Rate limiting is automatically applied based on HTTP method
        // GET = PROFILE_API_READ, POST/PUT/DELETE = PROFILE_API_WRITE
        
        // For stricter limits on specific actions:
        if ($this->method === 'POST') {
            $this->applyRateLimit('critical_action');
        }
        
        // ... controller logic
    }
}
```

**In login handler:**

```php
// Already applied in admin/login-handler.php
$rateLimiter = new RateLimiter(RateLimiter::PROFILE_AUTH);
$rateCheck = $rateLimiter->check('admin_login');
if (!$rateCheck['allowed']) {
    $_SESSION['LOGIN_ERROR'] = 'Too many login attempts. Try again in ' . $rateCheck['retry_after'] . ' seconds.';
    exit;
}
```

### Configuration

Rate limits can be customized via settings:

```php
// In database settings table:
rate_limit_default_max = 60
rate_limit_default_window = 60
```

### Violation Logging

Rate limit violations are automatically logged to `admin_action_logs`:

```json
{
  "action": "rate_limit_violation",
  "entity_type": "rate_limiter",
  "ip_address": "203.0.113.42",
  "payload": {
    "endpoint": "admin_login",
    "violation_count": 3,
    "limit": 5,
    "window": 900,
    "url": "/admin/login-handler.php",
    "method": "POST"
  }
}
```

### Response Headers

All rate-limited endpoints return:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1699876543
```

On limit exceeded (HTTP 429):

```
Retry-After: 45
```

### Monitoring

View rate limit violations in Admin → Audit Logs:

1. Filter by Action: `rate_limit_violation`
2. Check IP addresses for patterns
3. Adjust limits if necessary via settings

### Testing

```php
// tests/Unit/RateLimiterTest.php
php vendor/bin/phpunit tests/Unit/RateLimiterTest.php
```

---

## CSRF Protection

### Implementation

CSRF tokens are generated on login and validated on all state-changing operations.

#### Token Generation

```php
// Automatic on login via AdminAuthService
$result = $authService->authenticate($email, $password, $ip, $userAgent);
$csrfToken = $result['csrf_token']; // 64-character hex string
```

#### Token Storage

- **Database**: `admin_sessions.csrf_token`
- **Session**: `$_SESSION['CSRF_TOKEN']`

#### Token Validation

**In admin pages:**

```php
require_once 'includes/csrf.php';
CSRF::verifyPostToken(); // Throws exception on mismatch
```

**In API endpoints:**

```php
require_once 'api/helpers/admin_auth.php';

// For authenticated + CSRF protected endpoints
requireAdminAuthWithCsrf();

// Or separately
requireAdminAuth();
verifyCsrfToken(); // Checks X-CSRF-Token header or POST csrf_token field
```

**In AJAX requests:**

```javascript
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(data)
});
```

#### HTML Form Example

```html
<form method="POST" action="/admin/action.php">
    <?php CSRF::renderTokenField(); ?>
    <!-- or manually: -->
    <input type="hidden" name="csrf_token" value="<?= CSRF::getToken(); ?>">
    
    <!-- form fields -->
    <button type="submit">Submit</button>
</form>
```

### Token Rotation

CSRF tokens are rotated on:

1. **Password change**: All sessions destroyed, new tokens on re-login
2. **Logout**: Session destroyed
3. **Session expiry**: Token invalidated with session

### Testing

```php
// tests/Unit/CsrfProtectionTest.php
php vendor/bin/phpunit tests/Unit/CsrfProtectionTest.php
```

Key tests:
- ✓ Token generation on login
- ✓ Token validation (valid/invalid/empty)
- ✓ Token rotation on password change
- ✓ Timing-safe comparison
- ✓ Multiple sessions have different tokens

---

## Admin Action Logging

### Implementation

All admin actions are logged to `admin_action_logs` table for audit trail.

#### Automatic Logging

Controllers using `BaseApiController` automatically log CRUD operations.

#### Manual Logging

```php
require_once 'api/helpers/admin_auth.php';

logAdminAction(
    'action_name',           // e.g., 'create', 'update', 'delete'
    'entity_type',           // e.g., 'order', 'service', 'setting'
    $entityId,               // Primary key or null
    ['key' => 'value']       // Optional payload
);
```

#### Examples

**Content creation:**
```php
$service = Service::create($data);
logAdminAction('create', 'service', $service->id, $data);
```

**Settings change:**
```php
$settings->set('key', 'value');
logAdminAction('settings_change', 'setting', null, ['key' => 'key', 'value' => 'value']);
```

**Backup trigger:**
```php
logAdminAction('trigger_backup', 'backup', null, $options);
```

### Log Viewer

Access audit logs at `/admin/audit.php`.

#### Features

- **Filters**: User, action, entity type, date range, search
- **Stats**: Total logs, today's activity, violations, unique IPs
- **Details Modal**: Full payload inspection
- **Export**: CSV export with filters
- **Cleanup**: Delete logs older than N days

#### Filters

```
User: All users / Specific admin / System actions
Action: Login, Logout, Create, Update, Delete, Status change, etc.
Entity Type: Order, Service, Portfolio, Setting, etc.
Date: From/To range
Search: IP address, User Agent, Entity ID, Payload content
```

#### Stats Dashboard

```
┌─────────────┬─────────────┬─────────────┬─────────────┐
│ Total Logs  │ Today       │ Violations  │ Unique IPs  │
│    12,345   │     234     │      12     │     89      │
└─────────────┴─────────────┴─────────────┴─────────────┘
```

### Retention Policy

Logs can be cleaned up via UI or CLI:

```bash
# Delete logs older than 90 days
curl -X DELETE "https://yoursite.com/api/admin/audit-logs.php?older_than=90" \
  -H "X-CSRF-Token: YOUR_TOKEN"
```

### Integration Points

| Component | Logged Actions |
|-----------|---------------|
| **Authentication** | login, logout, login_failed |
| **Content** | create, update, delete, view |
| **Orders** | status_change, archive, unarchive, add_note, generate_export_url |
| **Settings** | settings_change |
| **Backups** | trigger_backup |
| **Rate Limiter** | rate_limit_violation |
| **Audit Logs** | export_audit_logs, cleanup_audit_logs |

---

## Database Backups

### Implementation

Enhanced backup system with rotation, checksums, and scheduling.

### CLI Usage

```bash
# Full backup (default)
php database/backup.php

# Schema only
php database/backup.php --schema-only

# Specific tables
php database/backup.php --tables=orders,settings

# With retention (keep 30 most recent)
php database/backup.php --retention=30

# Verify integrity
php database/backup.php --verify

# No compression
php database/backup.php --no-compress
```

### HTTP Usage (Admin Panel)

Backups can be triggered from admin panel (requires authentication):

```
https://yoursite.com/database/backup.php?verify=1&retention=30
```

### Storage Location

```
storage/backups/
├── dbname_full_2024-01-15_02-00-00.sql
├── dbname_full_2024-01-15_02-00-00.sql.gz
├── dbname_full_2024-01-15_02-00-00.sql.md5
├── dbname_full_2024-01-15_02-00-00.sql.gz.md5
└── backup.log
```

### Features

#### 1. Checksums

MD5 checksums are generated for all backups:

```
dbname_full_2024-01-15_02-00-00.sql.md5:
a1b2c3d4e5f6...  dbname_full_2024-01-15_02-00-00.sql
```

Verify manually:
```bash
md5sum -c dbname_full_2024-01-15_02-00-00.sql.md5
```

#### 2. Compression

Gzip compression is applied by default (70-90% size reduction):

```json
{
  "compression_ratio": "85.3%"
}
```

#### 3. Verification

With `--verify` flag:

```json
{
  "verification": {
    "valid": true,
    "checksum_match": true,
    "readable": true,
    "has_data": true
  }
}
```

#### 4. Retention Policy

Automatically removes old backups:

```bash
# Keep only 30 most recent
php database/backup.php --retention=30
```

#### 5. Backup Log

All operations logged to `storage/backups/backup.log`:

```
2024-01-15 02:00:00 - OK - Backup completed successfully. 2 file(s) created.
2024-01-15 02:00:00 - OK - Deleted 5 old backup(s) per retention policy
```

### Cron Scheduling

Add to crontab for automated backups:

```bash
# Daily full backup at 2 AM (keep 30 days)
0 2 * * * cd /var/www/html && php database/backup.php --retention=30 >> logs/backup.log 2>&1

# Weekly schema-only backup (keep 12 weeks)
0 3 * * 0 cd /var/www/html && php database/backup.php --schema-only --retention=12 >> logs/backup.log 2>&1

# Monthly archive backup (keep indefinitely, no retention)
0 4 1 * * cd /var/www/html && php database/backup.php --retention=0 >> logs/backup.log 2>&1
```

### Backup Response Format

```json
{
  "status": "OK",
  "timestamp": "2024-01-15 02:00:00",
  "database": "3dprint_db",
  "host": "localhost",
  "options": {
    "retention": 30,
    "compress": true,
    "verify": true
  },
  "files_created": [
    {
      "filename": "3dprint_db_full_2024-01-15_02-00-00.sql",
      "size": 5242880,
      "size_formatted": "5.00 MB",
      "type": "full",
      "checksum": "a1b2c3d4..."
    },
    {
      "filename": "3dprint_db_full_2024-01-15_02-00-00.sql.gz",
      "size": 786432,
      "size_formatted": "768.00 KB",
      "type": "compressed",
      "compression_ratio": "85.0%",
      "checksum": "e5f6a1b2..."
    }
  ],
  "verification": {
    "valid": true,
    "checksum_match": true,
    "readable": true,
    "has_data": true
  },
  "info": [
    "Backing up all 18 tables",
    "Deleted 3 old backup(s) per retention policy"
  ],
  "summary": "Backup completed successfully. 2 file(s) created."
}
```

### Restore Procedure

```bash
# Decompress if needed
gunzip dbname_full_2024-01-15_02-00-00.sql.gz

# Verify checksum
md5sum -c dbname_full_2024-01-15_02-00-00.sql.md5

# Restore
mysql -h localhost -u dbuser -p dbname < dbname_full_2024-01-15_02-00-00.sql
```

### Monitoring

Check backup log regularly:

```bash
tail -f storage/backups/backup.log
```

Alert on errors:

```bash
if grep -q "ERROR" storage/backups/backup.log; then
    echo "Backup failure detected!" | mail -s "Backup Alert" admin@example.com
fi
```

---

## Authentication & Authorization

### Overview

RBAC system with:
- **3 roles**: `super_admin`, `admin`, `editor`
- **Session management**: DB-backed with 30-minute timeout
- **Rate limiting**: 5 login attempts per 15 minutes
- **Audit logging**: All auth events logged

### Implementation Details

See comprehensive guides:
- [RBAC Authentication](./RBAC_AUTHENTICATION.md)
- [Admin Guide](./ADMIN_GUIDE.md)

---

## Best Practices

### 1. HTTPS Enforcement

**Required** for production. HSTS headers only apply over HTTPS.

```apache
# .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. Environment Variables

Never commit `.env` file. Use secure values:

```bash
DB_PASSWORD=<strong-random-password>
APP_KEY=<64-character-hex-string>
```

Generate strong passwords:
```bash
openssl rand -hex 32
```

### 3. File Permissions

```bash
# Application files: read-only for web server
chmod 644 *.php
chmod 755 directories

# Writable directories
chmod 755 storage/
chmod 755 storage/cache/
chmod 755 storage/backups/
chmod 755 storage/uploads/

# Sensitive files: not web-accessible
chmod 600 .env
chmod 600 api/config.php
```

### 4. Rate Limit Tuning

Monitor `admin_action_logs` for rate limit violations:

```sql
SELECT COUNT(*) as violations, ip_address
FROM admin_action_logs
WHERE action = 'rate_limit_violation'
AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY ip_address
ORDER BY violations DESC;
```

Adjust profiles if needed:
- Increase limits for legitimate high-traffic APIs
- Decrease limits for sensitive endpoints

### 5. Regular Security Audits

**Weekly:**
- Review audit logs for suspicious activity
- Check rate limit violations
- Verify backup success

**Monthly:**
- Update dependencies: `composer update`
- Review CSP violations (if CSP reporting configured)
- Test backup restoration

**Quarterly:**
- Security header scan (securityheaders.com)
- Penetration testing
- Review user roles and permissions

### 6. Input Validation

Always validate and sanitize:

```php
use App\Http\Traits\ValidatesRequests;

$this->validateInput($data, [
    'email' => 'required|email',
    'name' => 'required|maxLength:255',
    'age' => 'int|min:18|max:120'
]);
```

### 7. Output Encoding

Prevent XSS:

```php
// Blade templates (when available)
{{ $userInput }} // Auto-escaped

// Plain PHP
<?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8'); ?>
```

### 8. SQL Injection Prevention

Always use prepared statements:

```php
// Eloquent (safe by default)
$users = AdminUser::where('email', $email)->get();

// PDO
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### 9. Session Security

```php
// includes/session-config.php already sets:
session.cookie_httponly = 1
session.cookie_secure = 1 (on HTTPS)
session.cookie_samesite = Strict
session.use_strict_mode = 1
```

### 10. Dependency Updates

Keep dependencies up-to-date:

```bash
composer outdated
composer update --with-dependencies
```

---

## Security Testing

### Automated Tests

```bash
# All security tests
composer test -- --testsuite Security

# Specific test suites
php vendor/bin/phpunit tests/Unit/RateLimiterTest.php
php vendor/bin/phpunit tests/Unit/CsrfProtectionTest.php
php vendor/bin/phpunit tests/Integration/AdminAuthIntegrationTest.php
```

### Manual Testing Checklist

#### Security Headers

- [ ] Open DevTools → Network → Check response headers
- [ ] Verify CSP header present and correct
- [ ] Verify X-Frame-Options: DENY
- [ ] Verify X-Content-Type-Options: nosniff
- [ ] Run securityheaders.com scan

#### Rate Limiting

- [ ] Make 6+ rapid login attempts → blocked
- [ ] Check `X-RateLimit-*` headers in response
- [ ] Verify violation logged in audit logs
- [ ] Test different profiles (auth, API read, API write)

#### CSRF Protection

- [ ] Submit form without token → 403 Forbidden
- [ ] Submit form with invalid token → 403 Forbidden
- [ ] Submit form with valid token → Success
- [ ] Verify token rotation on password change

#### Audit Logging

- [ ] Login → verify login action logged
- [ ] Create content → verify create action logged
- [ ] Update settings → verify settings_change logged
- [ ] Access /admin/audit.php → all logs visible
- [ ] Filter logs by user/action/entity → works correctly

#### Database Backups

- [ ] Run `php database/backup.php --verify`
- [ ] Verify files created in `storage/backups/`
- [ ] Verify `.md5` checksum files present
- [ ] Run `md5sum -c *.md5` → all OK
- [ ] Test restore on dev environment

### Penetration Testing Tools

**OWASP ZAP**: https://www.zaproxy.org/
```bash
# Quick scan
zap-cli quick-scan https://yoursite.com

# Full scan (longer)
zap-cli active-scan https://yoursite.com
```

**Nikto**: Web server scanner
```bash
nikto -h https://yoursite.com
```

**SQLMap**: SQL injection testing
```bash
sqlmap -u "https://yoursite.com/api/endpoint?id=1" --batch
```

---

## Incident Response

### Suspected Breach

1. **Immediate Actions**
   - Change all admin passwords
   - Rotate database credentials
   - Review audit logs for unauthorized access
   - Check rate limit violations for attack patterns

2. **Investigation**
   ```sql
   -- Recent admin actions
   SELECT * FROM admin_action_logs 
   WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
   ORDER BY created_at DESC;
   
   -- Failed login attempts
   SELECT * FROM admin_login_attempts
   WHERE success = 0
   AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);
   
   -- Rate limit violations by IP
   SELECT ip_address, COUNT(*) as violations
   FROM admin_action_logs
   WHERE action = 'rate_limit_violation'
   GROUP BY ip_address
   ORDER BY violations DESC;
   ```

3. **Mitigation**
   - Block malicious IPs at firewall level
   - Tighten rate limits temporarily
   - Enable additional logging
   - Force logout all sessions:
     ```php
     AdminSession::truncate(); // Nuclear option
     ```

4. **Recovery**
   - Restore from verified backup if data compromised
   - Verify backup checksum before restore
   - Re-run migrations if schema changed
   - Test thoroughly before going live

### Reporting Vulnerabilities

If you discover a security vulnerability:

1. **DO NOT** open a public issue
2. Email: security@3dprint-omsk.ru
3. Include:
   - Description of vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

---

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Content Security Policy Reference](https://content-security-policy.com/)
- [Mozilla Security Guidelines](https://infosec.mozilla.org/guidelines/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)

---

## Changelog

### v2.0 (2024-01-15)
- Enhanced security headers with context-aware CSP
- Integrated rate limiting across all endpoints
- Added comprehensive admin action logging and UI
- Enhanced database backups with rotation and checksums
- Added CSRF protection tests
- Created comprehensive security documentation

### v1.0 (Initial)
- Basic authentication system
- CSRF protection
- Session management
- Input validation
