# Production Runbook

Complete end-to-end guide for deploying and operating **3D Print Pro** (3dprint-omsk.ru) in production.

## Table of Contents

- [Overview](#overview)
- [Pre-Deployment](#pre-deployment)
- [Automated Deployment](#automated-deployment)
- [Manual Deployment Steps](#manual-deployment-steps)
- [Post-Deployment Configuration](#post-deployment-configuration)
- [Content Management](#content-management)
- [Email & Telegram Setup](#email--telegram-setup)
- [Monitoring & Logging](#monitoring--logging)
- [Analytics Integration](#analytics-integration)
- [Performance Optimization](#performance-optimization)
- [Quality Assurance](#quality-assurance)
- [Security Validation](#security-validation)
- [Rollback Procedures](#rollback-procedures)
- [Backup & Documentation](#backup--documentation)
- [Final Launch Checklist](#final-launch-checklist)
- [Troubleshooting](#troubleshooting)

---

## Overview

This runbook covers Steps 3 and 7–14 of the production deployment workflow for 3dprint-omsk.ru. It assumes you have completed:

- **Step 1**: Hosting environment validation (see [HOSTING_AUDIT.md](HOSTING_AUDIT.md))
- **Step 2**: Database provisioning (see [DATABASE_OPERATIONS.md](DATABASE_OPERATIONS.md))
- **Steps 4-6**: Web server, DNS & SSL configuration (see [WEB_SERVER_CONFIG.md](WEB_SERVER_CONFIG.md))

### Quick Links

- **Deployment Script**: `scripts/deploy.sh`
- **Hosting Audit**: `scripts/hosting-audit.php`
- **Database Provisioning**: `scripts/provision-database.php`
- **Web Server Config**: [WEB_SERVER_CONFIG.md](WEB_SERVER_CONFIG.md)
- **Smoke Tests**: `scripts/api_smoke.php`

---

## Pre-Deployment

### Validate Hosting Environment

**⚠️ CRITICAL**: Always validate the hosting environment before deployment.

```bash
# Run hosting audit with strict mode
php scripts/hosting-audit.php --strict

# For shared hosting (skip Redis checks)
php scripts/hosting-audit.php --strict --skip-redis

# Generate JSON report for documentation
php scripts/hosting-audit.php --format=json > hosting-audit-report.json
```

**Requirements:**
- ✅ PHP 7.4+ (recommended: 8.1+)
- ✅ Required extensions: pdo_mysql, mbstring, intl, json, curl, gd, openssl, zip
- ✅ MySQL 5.7+ or MariaDB 10.5+
- ✅ 1 GB+ disk space free
- ✅ 256 MB+ memory available
- ✅ Writable: `storage/`, `logs/`, `storage/cache/`

📖 **See [HOSTING_AUDIT.md](HOSTING_AUDIT.md) for detailed requirements and remediation.**

### Prepare Environment Configuration

Review and customize `.env.production.example`:

```bash
cp .env.production.example .env
nano .env
```

**Required credentials:**
- `DB_PASSWORD` - MySQL database password
- `TELEGRAM_BOT_TOKEN` - Telegram bot token (see [Telegram Setup](#telegram-setup))
- `TELEGRAM_CHAT_ID` - Your Telegram chat ID
- `SMTP_PASSWORD` - SMTP password for email notifications

**Security checklist:**
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Strong database password (16+ characters)
- [ ] Unique CSRF token lifetime configured
- [ ] Rate limiting configured appropriately

---

## Automated Deployment

### Using deploy.sh Script

The recommended deployment method uses the automated script:

```bash
# Test what would happen (dry run)
bash scripts/deploy.sh --dry-run

# Full production deployment
bash scripts/deploy.sh

# CI/CD pipeline deployment (non-interactive)
bash scripts/deploy.sh --ci

# Quick deployment skipping tests (not recommended)
bash scripts/deploy.sh --skip-tests
```

**What the script does:**

1. ✅ **Validates prerequisites** - PHP version, required tools
2. ✅ **Runs hosting audit** - Ensures environment meets requirements
3. ✅ **Installs dependencies** - `composer install --no-dev --optimize-autoloader`
4. ✅ **Configures environment** - Copies `.env.production.example` to `.env` if missing
5. ✅ **Sets permissions** - Creates directories, sets 755/644/600 permissions
6. ✅ **Verifies database** - Checks schema and connectivity
7. ✅ **Runs smoke tests** - Validates API endpoints
8. ✅ **Generates report** - Creates deployment summary

**Exit codes:**
- `0` - Deployment successful
- `1` - Pre-deployment checks failed
- `2` - Composer installation failed
- `3` - Permission setup failed
- `4` - Database migration failed
- `5` - Smoke tests failed

**Deployment logs:**

Logs are written to `storage/logs/deploy_YYYYMMDD_HHMMSS.log`:

```bash
# View latest deployment log
tail -f storage/logs/deploy_*.log

# Check for errors
grep -i error storage/logs/deploy_*.log
```

---

## Manual Deployment Steps

### Step 3: Upload Files

**Via SFTP/SCP:**

```bash
# Upload project files
scp -r /local/path/to/project/* user@3dprint-omsk.ru:/var/www/html/

# Or use rsync for faster incremental uploads
rsync -avz --exclude 'node_modules' --exclude '.git' --exclude 'vendor' \
  /local/path/to/project/ user@3dprint-omsk.ru:/var/www/html/
```

**Via FTP:**

Upload all files maintaining directory structure:

```
3dprint-omsk.ru/
├── admin/           # Admin panel
├── api/             # REST API
├── app/             # Eloquent models & services
├── bootstrap/       # Framework bootstrap
├── css/             # Stylesheets
├── database/        # Schema and migrations
├── docs/            # Documentation
├── js/              # JavaScript modules
├── logs/            # Log files (create if missing)
├── scripts/         # Utility scripts
├── storage/         # Uploads and cache
├── tests/           # PHPUnit tests (optional in production)
├── vendor/          # Composer dependencies (install via composer)
├── *.html           # Public pages
├── composer.json
└── .htaccess
```

**Important:** Do NOT upload:
- `.env` (will be created from template)
- `vendor/` (will be installed via Composer)
- `node_modules/`
- `.git/`

### Step 4: Install Dependencies

```bash
# SSH into server
ssh user@3dprint-omsk.ru

# Navigate to project directory
cd /var/www/html

# Install Composer dependencies (production mode)
composer install --no-dev --optimize-autoloader --no-interaction

# Verify installation
composer dump-autoload
```

### Step 5: Configure Environment

```bash
# Copy production environment template
cp .env.production.example .env

# Edit with production credentials
nano .env

# Set secure permissions
chmod 600 .env
```

**Required edits:**
- Database credentials
- Telegram bot token and chat ID
- SMTP configuration
- Set `APP_DEBUG=false`
- Set `SESSION_SECURE_COOKIE=true`

### Step 6: Set Permissions

```bash
# Create required directories
mkdir -p storage/{cache,uploads/portfolio,uploads/testimonials,backups,logs}
mkdir -p logs

# Set directory permissions (755 = rwxr-xr-x)
find . -type d -exec chmod 755 {} \;

# Set file permissions (644 = rw-r--r--)
find . -type f -exec chmod 644 {} \;

# Set secure permissions on sensitive files (600 = rw-------)
chmod 600 .env
chmod 600 api/config.php

# Make scripts executable
chmod +x scripts/*.sh
chmod +x scripts/*.php

# Ensure web server can write to storage and logs
chmod -R 775 storage logs
chown -R www-data:www-data storage logs  # Adjust user/group as needed
```

**Verify permissions:**

```bash
ls -la storage/
ls -la logs/
ls -la .env
```

### Step 7: Database Setup

If you haven't already provisioned the database (Step 2), do so now:

```bash
# Automated provisioning with seeding
php scripts/provision-database.php --seed

# Or import schema manually
mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql

# Verify schema
php database/verify-schema.php
```

📖 **See [DATABASE_OPERATIONS.md](DATABASE_OPERATIONS.md) for complete database setup guide.**

### Step 8: Run Smoke Tests

```bash
# Run API smoke tests
php scripts/api_smoke.php

# Expected output: All endpoints should return 200 OK
# ✅ Services API
# ✅ Portfolio API
# ✅ FAQ API
# ✅ Testimonials API
# ✅ Orders API
```

If tests fail, check:
- Database connectivity (`.env` credentials)
- File permissions (storage, logs)
- PHP error log: `tail -f /var/log/php-fpm/error.log`
- API log: `tail -f logs/api.log`

---

## Post-Deployment Configuration

### Step 9: Create First Administrator

Create the initial admin user:

```bash
# Interactive mode
php scripts/create-admin.php

# Non-interactive (for automation)
php scripts/create-admin.php \
  admin@3dprint-omsk.ru \
  "Admin User" \
  "SecurePassword123!" \
  super_admin \
  active
```

**Parameters:**
- Email: admin@3dprint-omsk.ru
- Name: Admin User
- Password: Strong password (12+ chars, mixed case, numbers)
- Role: `super_admin` (full access), `admin` (standard), or `editor` (limited)
- Status: `active`, `inactive`, or `locked`

**Test admin login:**

1. Open: `https://3dprint-omsk.ru/admin/login.php`
2. Login with credentials
3. Verify dashboard loads ✅

### Step 10: Configure Global Settings

Login to admin panel and configure:

**Settings → Contacts**
- Phone: +7 (XXX) XXX-XX-XX
- Email: info@3dprint-omsk.ru
- Address: Омск, улица Ленина, 1
- Working hours: Пн-Пт 9:00-18:00
- GPS coordinates (for maps)

**Settings → Social Media**
- Telegram: @3dprint_omsk
- VK: vk.com/3dprint_omsk
- Instagram: @3dprint_omsk
- WhatsApp: +7XXXXXXXXXX

**Settings → SEO**
- Site title: 3D Print Pro - Омск
- Meta description
- Keywords
- OG image URL
- Canonical URL: https://3dprint-omsk.ru

**Test settings propagation:**

1. Open homepage: `https://3dprint-omsk.ru/`
2. Verify contact info displays correctly
3. Check page title and meta tags (View Source)
4. Verify social links work

---

## Content Management

### Step 11: Load Initial Content

#### Services

Login to admin panel → Services → Add New

**Example services:**
1. **3D Печать** - Печать на профессиональных 3D принтерах
2. **3D Моделирование** - Создание 3D моделей по вашим чертежам
3. **Прототипирование** - Быстрое изготовление прототипов
4. **Малые серии** - Производство малых серий изделий
5. **Постобработка** - Шлифовка, окраска, сборка

For each service, set:
- Name (title)
- Description (HTML supported)
- Price (optional)
- Icon class (Font Awesome)
- Active status
- Display order

#### Portfolio

Admin → Portfolio → Add New

Upload project images:
- Format: JPEG, PNG, WebP
- Max size: 5 MB
- Recommended: 1200x900px

For each project:
- Title
- Description
- Category/tags
- Image upload
- Featured (checkbox for homepage)
- Active status

#### Testimonials

Admin → Testimonials → Add New

- Customer name
- Rating (1-5 stars)
- Review text
- Avatar upload (optional)
- Company name (optional)
- Date
- Approved status

#### FAQ

Admin → FAQ → Add New

Common questions:
- Какие материалы вы используете?
- Сколько стоит 3D печать?
- Как долго делается заказ?
- Какой минимальный заказ?
- Доставка по России?

For each FAQ:
- Question
- Answer (HTML supported)
- Category
- Display order
- Active status

### Verify Content Display

1. Homepage: `https://3dprint-omsk.ru/` - Check featured portfolio
2. Services: `https://3dprint-omsk.ru/services.html` - Verify services list
3. Portfolio: `https://3dprint-omsk.ru/portfolio.html` - Check project gallery
4. FAQ: `https://3dprint-omsk.ru/faq.html` - Verify questions display
5. About: `https://3dprint-omsk.ru/about.html` - Check testimonials

---

## Email & Telegram Setup

### Step 12: Configure Notifications

### Telegram Setup

**Create Telegram Bot:**

1. Open Telegram app
2. Message **@BotFather**
3. Send command: `/newbot`
4. Follow prompts:
   - Bot name: 3D Print Pro Bot
   - Username: 3dprint_omsk_bot (must be unique)
5. Copy bot token: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`

**Get Chat ID:**

1. Start conversation with your bot
2. Send any message
3. Visit: `https://api.telegram.org/bot{YOUR_TOKEN}/getUpdates`
4. Find `"chat":{"id":123456789}` in JSON response
5. Copy chat ID

**Configure in Admin Panel:**

1. Login → Settings → Telegram
2. Paste Bot Token
3. Paste Chat ID
4. Enable notifications
5. Click **Test Telegram** button
6. Verify message received in Telegram ✅

**Test Order Notification:**

1. Submit contact form on public site
2. Check Telegram - should receive order notification
3. Verify format: Order #XXX, Name, Phone, Message

📖 **See [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md) for advanced configuration.**

### Email/SMTP Setup

**Configure SMTP (Yandex Mail example):**

1. Login → Settings → Email
2. Configure SMTP:
   ```
   Host:       smtp.yandex.ru
   Port:       465
   Encryption: SSL
   Username:   notify@3dprint-omsk.ru
   Password:   [app-specific password]
   From Email: notify@3dprint-omsk.ru
   From Name:  3D Print Pro
   ```

**Create App-Specific Password (Yandex):**

1. Login to Yandex Mail
2. Go to Security settings
3. Enable 2FA (if not enabled)
4. Create app password
5. Copy password to `.env` → `SMTP_PASSWORD`

**Alternative SMTP Providers:**

**Gmail:**
```
Host:       smtp.gmail.com
Port:       587
Encryption: TLS
Note:       Requires app password with 2FA enabled
```

**Mailgun:**
```
Host:       smtp.mailgun.org
Port:       587
Encryption: TLS
Note:       Sign up at mailgun.com
```

**Test Email Notifications:**

1. Admin → Settings → Email
2. Click **Test Email** button
3. Check inbox for test message
4. Verify HTML formatting

**Enable Order Notifications:**

1. Settings → Notifications
2. Enable **Email notifications** (checkbox)
3. Enable **Status change notifications**
4. Set recipient: `admin@3dprint-omsk.ru`
5. Save settings

**Test Order Email:**

1. Submit order from public site
2. Check email inbox
3. Verify order details received

---

## Monitoring & Logging

### Step 13: Setup Monitoring

### Error Logging

**Configure PHP error logging:**

Edit `php.ini` (or via hosting panel):

```ini
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/www/html/logs/php_errors.log
display_errors = Off  # CRITICAL: Must be Off in production
```

**Application logs:**

Logs are written to:
- `logs/api.log` - API requests and errors
- `logs/admin.log` - Admin actions
- `storage/logs/deploy_*.log` - Deployment logs
- `storage/backups/backup.log` - Database backups

**Monitor logs:**

```bash
# Watch API errors in real-time
tail -f logs/api.log | grep ERROR

# Check for 500 errors
grep "500" logs/api.log

# View last 100 errors
tail -n 100 logs/api.log
```

### Log Rotation

**Create logrotate config:**

```bash
sudo nano /etc/logrotate.d/3dprint-omsk
```

Add:

```
/var/www/html/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
    sharedscripts
    postrotate
        systemctl reload php-fpm >/dev/null 2>&1 || true
    endscript
}

/var/www/html/storage/logs/*.log {
    weekly
    rotate 12
    compress
    delaycompress
    missingok
    notifempty
}
```

Test:

```bash
sudo logrotate -d /etc/logrotate.d/3dprint-omsk
sudo logrotate -f /etc/logrotate.d/3dprint-omsk
```

### Uptime Monitoring

**UptimeRobot (Free tier):**

1. Sign up: https://uptimerobot.com
2. Add monitor:
   - Type: HTTP(s)
   - URL: `https://3dprint-omsk.ru/api/test.php`
   - Interval: 5 minutes
   - Alert contacts: Your email
3. Expected response: `"success":true`

**Alternative: StatusCake, Pingdom**

### Application Monitoring (Optional)

**Sentry (Error Tracking):**

1. Sign up: https://sentry.io
2. Create project: PHP
3. Install SDK:
   ```bash
   composer require sentry/sentry
   ```
4. Configure in `bootstrap/error-handler.php`:
   ```php
   \Sentry\init([
       'dsn' => 'https://your-dsn@sentry.io/project-id',
       'environment' => 'production'
   ]);
   ```

**Graylog (Centralized Logging):**

For advanced logging and analysis:

1. Setup Graylog server (Docker recommended)
2. Install Graylog PHP SDK
3. Configure log forwarding
4. Create dashboards for error trends

### Database Monitoring

**Monitor database health:**

```bash
# Add to crontab (runs hourly)
0 * * * * cd /var/www/html && php scripts/db_audit.php --json > logs/db_health.log 2>&1
```

**Check for slow queries:**

```sql
-- Enable slow query log in MySQL
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;  -- Queries > 2 seconds
SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-queries.log';
```

### Performance Monitoring

**Monitor response times:**

```bash
# Create monitoring script
cat > scripts/monitor-performance.sh << 'EOF'
#!/bin/bash
LOG_FILE="logs/performance.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Test API response time
TIME=$(curl -o /dev/null -s -w '%{time_total}' https://3dprint-omsk.ru/api/services.php)
echo "[$TIMESTAMP] API Response Time: ${TIME}s" >> $LOG_FILE

# Test homepage load time
TIME=$(curl -o /dev/null -s -w '%{time_total}' https://3dprint-omsk.ru/)
echo "[$TIMESTAMP] Homepage Load Time: ${TIME}s" >> $LOG_FILE
EOF

chmod +x scripts/monitor-performance.sh

# Add to crontab (runs every 15 minutes)
*/15 * * * * /var/www/html/scripts/monitor-performance.sh
```

---

## Analytics Integration

### Step 14: Setup Analytics

### Google Analytics

**Setup GA4:**

1. Create Google Analytics account: https://analytics.google.com
2. Create property: 3D Print Pro - Омск
3. Get Measurement ID: `G-XXXXXXXXXX`
4. Configure in `.env`:
   ```
   GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
   ```

**Add tracking code:**

In admin panel → Settings → Analytics:
- Paste Google Analytics ID
- Enable tracking
- Save

Or manually add to all HTML pages before `</head>`:

```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>
```

**Track events:**

Add event tracking to forms:

```javascript
// Track form submissions
gtag('event', 'form_submit', {
  'event_category': 'engagement',
  'event_label': 'contact_form'
});

// Track calculator usage
gtag('event', 'calculator_use', {
  'event_category': 'engagement',
  'value': totalCost
});
```

### Yandex.Metrica

**Setup Yandex.Metrica:**

1. Create account: https://metrica.yandex.ru
2. Add site: 3dprint-omsk.ru
3. Get Counter ID: `XXXXXXXX`
4. Configure in `.env`:
   ```
   YANDEX_METRICA_ID=XXXXXXXX
   ```

**Add tracking code:**

In admin panel → Settings → Analytics:
- Paste Yandex Metrica ID
- Enable tracking
- Enable Webvisor (optional, for session recordings)
- Save

Or manually add before `</head>`:

```html
<!-- Yandex.Metrica -->
<script type="text/javascript" >
   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
   m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

   ym(XXXXXXXX, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true,
        webvisor:true
   });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/XXXXXXXX" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
```

**Track goals:**

Define goals in Yandex.Metrica:
- Form submission → `goal_form_submit`
- Calculator usage → `goal_calculator_use`
- Phone click → `goal_phone_click`
- Email click → `goal_email_click`

### Verify Analytics

1. Open site in incognito window
2. Navigate pages, submit form
3. Check Google Analytics Real-Time reports (5-10 min delay)
4. Check Yandex.Metrica Real-Time reports (instant)
5. Verify events tracked correctly

---

## Performance Optimization

### Step 15: Optimize Performance

### Enable Redis Caching (Optional)

If Redis is available on your hosting:

**Install Redis PHP extension:**

```bash
# Ubuntu/Debian
sudo apt-get install php-redis

# Via PECL
pecl install redis
```

**Configure Redis in `.env`:**

```
CACHE_DRIVER=redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
```

**Test Redis:**

```bash
# Check Redis is running
redis-cli ping
# Expected: PONG

# Test PHP Redis extension
php -r "var_dump(extension_loaded('redis'));"
# Expected: bool(true)
```

**Performance improvement:**
- File cache: ~50-100ms
- Redis cache: ~5-10ms (5-10x faster)

### Optimize Database

**Add indexes for frequently queried columns:**

```sql
-- Already indexed in schema.sql, but verify:
CREATE INDEX idx_services_active ON services(active);
CREATE INDEX idx_portfolio_featured ON portfolio(featured, active);
CREATE INDEX idx_orders_status ON orders(status, created_at);
CREATE INDEX idx_testimonials_rating ON testimonials(rating, active);

-- Check index usage
SHOW INDEX FROM services;
```

**Optimize tables:**

```bash
# Add to crontab (runs weekly)
0 3 * * 0 mysql -u user -p database -e "OPTIMIZE TABLE orders, services, portfolio, testimonials, faq;"
```

### Enable HTTP Caching

**Configure cache headers in `.htaccess`:**

Add to root `.htaccess`:

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    
    # Images
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    
    # CSS and JavaScript
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    
    # Fonts
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
</IfModule>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css
    AddOutputFilterByType DEFLATE application/javascript application/json
    AddOutputFilterByType DEFLATE image/svg+xml
</IfModule>
```

**Verify caching:**

```bash
curl -I https://3dprint-omsk.ru/css/style.css | grep -i cache
# Expected: Cache-Control: max-age=...
```

### Optimize Images

**Compress uploaded images:**

```bash
# Install image optimization tools
sudo apt-get install optipng jpegoptim webp

# Create optimization script
cat > scripts/optimize-images.sh << 'EOF'
#!/bin/bash
find storage/uploads -name "*.jpg" -exec jpegoptim --strip-all --max=85 {} \;
find storage/uploads -name "*.png" -exec optipng -o5 {} \;
EOF

chmod +x scripts/optimize-images.sh

# Run weekly via cron
0 4 * * 0 /var/www/html/scripts/optimize-images.sh
```

### Enable OPcache

**Configure PHP OPcache:**

Edit `php.ini`:

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

Restart PHP-FPM:

```bash
sudo systemctl restart php-fpm
```

**Verify OPcache:**

```bash
php -i | grep opcache
# Expected: opcache.enable => On
```

### CDN Integration (Optional)

For high-traffic sites, consider CDN:

**Cloudflare (Free tier):**

1. Sign up: https://cloudflare.com
2. Add site: 3dprint-omsk.ru
3. Update nameservers at domain registrar
4. Enable:
   - Auto Minify (JS, CSS, HTML)
   - Brotli compression
   - Browser cache TTL: 4 hours
5. Create page rules:
   - Cache Level: Cache Everything for static assets
   - Edge Cache TTL: 1 month for images

**Performance gains:**
- Global CDN: Faster load times worldwide
- DDoS protection
- Free SSL certificate
- Image optimization (Cloudflare Polish)

---

## Quality Assurance

### Step 16: QA Testing

### Functional Testing

**Test all pages:**

✅ **Homepage** (`/`)
- [ ] Logo and navigation visible
- [ ] Hero section loads
- [ ] Services cards display
- [ ] Featured portfolio items show
- [ ] Contact form visible
- [ ] Footer with social links

✅ **Services** (`/services.html`)
- [ ] All services display
- [ ] Service icons load
- [ ] Descriptions readable
- [ ] Pricing (if shown) correct

✅ **Portfolio** (`/portfolio.html`)
- [ ] Project grid displays
- [ ] Images load correctly
- [ ] Lightbox/modal works
- [ ] Filtering works (if implemented)

✅ **Calculator** (`/calculator.html`)
- [ ] Calculator loads
- [ ] Material selection works
- [ ] Quantity input functional
- [ ] Price updates dynamically
- [ ] Form submission works

✅ **About** (`/about.html`)
- [ ] Company info displays
- [ ] Team section (if present)
- [ ] Testimonials visible

✅ **Contact** (`/contact.html`)
- [ ] Contact form displays
- [ ] Form validation works
- [ ] Phone/email links clickable
- [ ] Map embedded (if present)

✅ **FAQ** (`/faq.html`)
- [ ] Questions display
- [ ] Accordion/expand works
- [ ] Categories functional

### Form Testing

**Test contact form submission:**

1. Fill form with test data:
   ```
   Name:    Тест Тестович
   Phone:   +7 (999) 123-45-67
   Email:   test@example.com
   Message: Тестовое сообщение
   ```
2. Submit form
3. Verify success message displayed
4. Check Telegram notification received
5. Check email notification received
6. Check order in admin panel
7. Verify all fields saved correctly

**Test calculator + order flow:**

1. Open calculator page
2. Select material: PLA
3. Set quantity: 10
4. Verify price calculation
5. Fill order form
6. Submit
7. Verify order created with `calculator_data` JSON

### Admin Panel Testing

**Test admin functionality:**

✅ **Login**
- [ ] Login page loads
- [ ] Correct credentials work
- [ ] Incorrect credentials rejected
- [ ] Rate limiting works (5 failed attempts)
- [ ] Session persists

✅ **Dashboard**
- [ ] Statistics display correctly
- [ ] Charts render (Chart.js)
- [ ] Recent orders list

✅ **Orders Management**
- [ ] Orders list displays
- [ ] Filtering works
- [ ] Status change works
- [ ] Notes can be added
- [ ] Export to CSV works

✅ **Content Management**
- [ ] Services CRUD works
- [ ] Portfolio CRUD works
- [ ] FAQ CRUD works
- [ ] Image uploads work (5MB limit)

✅ **Settings**
- [ ] All tabs load
- [ ] Settings save correctly
- [ ] Test buttons work (Telegram, Email)
- [ ] Audit history displays

✅ **Users** (super_admin only)
- [ ] User list displays
- [ ] Create user works
- [ ] Edit user works
- [ ] Role change works
- [ ] Delete user works

### Mobile Testing

**Test on real devices or emulators:**

✅ **Responsive Design**
- [ ] iPhone SE (375px)
- [ ] iPhone 12 (390px)
- [ ] Samsung Galaxy (360px)
- [ ] iPad (768px)
- [ ] iPad Pro (1024px)

✅ **Mobile Features**
- [ ] Hamburger menu works
- [ ] Touch targets large enough (44px minimum)
- [ ] Forms usable on mobile
- [ ] Images scale correctly
- [ ] No horizontal scroll

**Test with Chrome DevTools:**

```
F12 → Toggle Device Toolbar (Ctrl+Shift+M)
Test: iPhone 12 Pro, iPad, Galaxy S20
```

### Browser Testing

**Test in multiple browsers:**

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

**Check for:**
- Layout consistency
- JavaScript functionality
- CSS rendering
- Form validation
- Console errors (F12)

### Automated Testing

**Run PHPUnit tests:**

```bash
# Run all tests
composer test

# Run specific test suites
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration

# Generate coverage report
composer test-coverage
```

**Run smoke tests:**

```bash
# API smoke tests
php scripts/api_smoke.php

# Expected: All endpoints return 200 OK
```

📖 **See [QA_REGRESSION.md](QA_REGRESSION.md) for complete QA checklist.**

---

## Security Validation

### Step 17: Security Hardening

### SSL/TLS Configuration

**Verify HTTPS:**

```bash
# Check SSL certificate
openssl s_client -connect 3dprint-omsk.ru:443 -servername 3dprint-omsk.ru

# Check SSL rating
curl https://www.ssllabs.com/ssltest/analyze.html?d=3dprint-omsk.ru
# Target: A or A+ rating
```

**Force HTTPS redirect:**

Verify `.htaccess` contains:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [L,R=301]
```

### Security Headers

**Verify security headers:**

```bash
curl -I https://3dprint-omsk.ru | grep -E "X-Frame-Options|X-Content-Type|X-XSS|Strict-Transport"
```

**Expected headers:**
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000
Content-Security-Policy: default-src 'self'
```

### File Permissions Audit

**Verify file permissions:**

```bash
# Check sensitive files are 600 (owner-only read/write)
ls -la .env api/config.php
# Expected: -rw------- (600)

# Check directories are 755
ls -ld storage/ logs/
# Expected: drwxr-xr-x (755)

# Check PHP files are 644
ls -la admin/*.php
# Expected: -rw-r--r-- (644)
```

**Fix incorrect permissions:**

```bash
# Secure sensitive files
chmod 600 .env api/config.php

# Fix directory permissions
find . -type d -exec chmod 755 {} \;

# Fix file permissions
find . -type f -exec chmod 644 {} \;

# Make scripts executable
chmod +x scripts/*.sh scripts/*.php
```

### SQL Injection Testing

**Verify PDO prepared statements:**

All database queries should use prepared statements:

```php
// ✅ SECURE - Prepared statement
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);

// ❌ INSECURE - String concatenation (DO NOT USE)
$query = "SELECT * FROM orders WHERE id = " . $_GET['id'];
```

**Test for SQL injection:**

Try injecting in URL parameters:
- `?id=1' OR '1'='1`
- `?id=1; DROP TABLE orders--`

Expected: Parameters should be treated as strings, not executed.

### XSS Testing

**Verify output escaping:**

All user input should be escaped:

```php
// ✅ SECURE - Escaped output
echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

// ❌ INSECURE - Raw output (DO NOT USE)
echo $userName;
```

**Test for XSS:**

Try submitting in forms:
- `<script>alert('XSS')</script>`
- `<img src=x onerror=alert('XSS')>`

Expected: Should be displayed as text, not executed.

### CSRF Protection

**Verify CSRF tokens:**

All state-changing requests should have CSRF tokens:

```php
// In admin forms
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// In API requests
verifyCsrfToken($_POST['csrf_token']);
```

**Test CSRF protection:**

1. Login to admin panel
2. Copy form HTML
3. Open in different browser (not logged in)
4. Submit form

Expected: Request should be rejected (401 Unauthorized).

### Rate Limiting

**Verify rate limiting:**

```bash
# Test login rate limiting (should lock after 5 attempts)
for i in {1..6}; do
  curl -X POST https://3dprint-omsk.ru/admin/login-handler.php \
    -d "email=test@example.com&password=wrong"
  echo "Attempt $i"
done
# Expected: Attempt 6 should return 429 Too Many Requests
```

**Check rate limit logs:**

```bash
ls -la storage/cache/rate_limits/
# Should contain IP-based rate limit files
```

### Audit Logs

**Verify audit logging:**

```bash
# Check admin action logs
mysql -u user -p database -e "SELECT * FROM admin_action_logs ORDER BY created_at DESC LIMIT 10;"
```

Expected: All admin actions logged (login, CRUD operations, settings changes).

### Password Security

**Verify password hashing:**

```bash
# Check admin users table
mysql -u user -p database -e "SELECT id, email, password_hash FROM admin_users LIMIT 1;"
```

Expected: Password hash starts with `$2y$` (bcrypt) and is 60 characters.

**Enforce password policy:**

- Minimum 12 characters
- Mixed case letters
- Numbers
- Special characters (recommended)

---

## Rollback Procedures

### Step 18: Rollback Strategy

### Quick Rollback

If critical issues discovered post-deployment:

**Option 1: Restore from backup**

```bash
# 1. Stop site (maintenance mode)
touch maintenance.html

# 2. Restore database from backup
cd storage/backups
gunzip latest-backup.sql.gz
mysql -u user -p database < latest-backup.sql

# 3. Restore file backup
tar -xzf site-backup-YYYYMMDD.tar.gz -C /var/www/html/

# 4. Clear cache
rm -rf storage/cache/*

# 5. Remove maintenance mode
rm maintenance.html
```

**Option 2: Revert git commit (if using git)**

```bash
# Check current commit
git log --oneline -5

# Revert to previous commit
git revert HEAD
# Or hard reset (DANGEROUS)
git reset --hard COMMIT_HASH

# Re-deploy
bash scripts/deploy.sh
```

**Option 3: Restore specific files**

```bash
# Restore only changed files from backup
tar -xzf backup.tar.gz --wildcards '*.php'

# Or copy from previous deployment
cp /backups/previous-deploy/.env .env
cp /backups/previous-deploy/api/config.php api/config.php
```

### Database Rollback

**Restore database to previous state:**

```bash
# List available backups
ls -lh storage/backups/*.sql.gz

# Restore specific backup
cd storage/backups
gunzip ch167436_3dprint_backup_20240115_020000.sql.gz
mysql -u ch167436_3dprint -p ch167436_3dprint < ch167436_3dprint_backup_20240115_020000.sql

# Verify restoration
php database/verify-schema.php
```

### Configuration Rollback

**Restore previous configuration:**

```bash
# Restore .env from backup
cp /backups/.env.backup .env

# Restore API config
cp /backups/api/config.php.backup api/config.php

# Restart services
sudo systemctl restart php-fpm
```

### Rollback Checklist

After rollback:

- [ ] Database restored and verified
- [ ] Files restored
- [ ] Configuration restored
- [ ] Cache cleared
- [ ] Services restarted
- [ ] Site loads without errors
- [ ] Admin panel accessible
- [ ] Forms work
- [ ] Run smoke tests: `php scripts/api_smoke.php`
- [ ] Monitor error logs
- [ ] Notify team of rollback
- [ ] Document rollback reason
- [ ] Plan fix for next deployment

---

## Backup & Documentation

### Step 19: Backup Strategy

### Automated Database Backups

**Setup daily backups via cron:**

```bash
crontab -e
```

Add:

```bash
# Daily full backup at 2 AM (keep 30 days)
0 2 * * * cd /var/www/html && php database/backup.php --retention=30 >> logs/backup.log 2>&1

# Weekly schema-only backup (keep 12 weeks)
0 3 * * 0 cd /var/www/html && php database/backup.php --schema-only --retention=12 >> logs/backup.log 2>&1

# Monthly archive (keep 12 months)
0 4 1 * * cd /var/www/html && php database/backup.php --retention=365 >> logs/backup.log 2>&1
```

**Verify backup automation:**

```bash
# Run backup manually
php database/backup.php --verify

# Check backup logs
tail -f logs/backup.log

# List backups
ls -lh storage/backups/*.sql.gz

# Verify latest backup
cd storage/backups
gunzip -c latest-backup.sql.gz | head -20
```

📖 **See [DATABASE_OPERATIONS.md](DATABASE_OPERATIONS.md) for backup strategies.**

### File Backups

**Create site backup script:**

```bash
cat > scripts/backup-site.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/backups/3dprint-omsk"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
PROJECT_ROOT="/var/www/html"

mkdir -p $BACKUP_DIR

# Backup files (exclude vendor, node_modules, cache)
tar -czf $BACKUP_DIR/site_${TIMESTAMP}.tar.gz \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='storage/cache/*' \
  --exclude='.git' \
  $PROJECT_ROOT

# Backup .env separately (encrypted)
gpg --encrypt --recipient admin@3dprint-omsk.ru \
  -o $BACKUP_DIR/env_${TIMESTAMP}.gpg \
  $PROJECT_ROOT/.env

# Remove backups older than 30 days
find $BACKUP_DIR -name "site_*.tar.gz" -mtime +30 -delete

echo "Backup complete: $BACKUP_DIR/site_${TIMESTAMP}.tar.gz"
EOF

chmod +x scripts/backup-site.sh
```

**Schedule file backups:**

```bash
# Weekly full site backup (Sundays at 1 AM)
0 1 * * 0 /var/www/html/scripts/backup-site.sh
```

### Off-Site Backups

**Sync backups to remote storage:**

**Option 1: rsync to backup server**

```bash
# Setup SSH key authentication
ssh-keygen -t ed25519
ssh-copy-id backup@backup-server.com

# Create sync script
cat > scripts/sync-backups.sh << 'EOF'
#!/bin/bash
rsync -avz --delete \
  /var/www/html/storage/backups/ \
  backup@backup-server.com:/backups/3dprint-omsk/
EOF

chmod +x scripts/sync-backups.sh

# Run daily after backups
30 2 * * * /var/www/html/scripts/sync-backups.sh
```

**Option 2: AWS S3**

```bash
# Install AWS CLI
sudo apt-get install awscli

# Configure credentials
aws configure

# Create sync script
cat > scripts/sync-to-s3.sh << 'EOF'
#!/bin/bash
aws s3 sync /var/www/html/storage/backups/ \
  s3://3dprint-omsk-backups/db/ \
  --storage-class STANDARD_IA \
  --delete
EOF

chmod +x scripts/sync-to-s3.sh

# Run daily
0 5 * * * /var/www/html/scripts/sync-to-s3.sh
```

**Option 3: Dropbox/Google Drive**

Use `rclone`:

```bash
# Install rclone
curl https://rclone.org/install.sh | sudo bash

# Configure remote
rclone config

# Sync backups
rclone sync /var/www/html/storage/backups/ \
  dropbox:3dprint-omsk-backups/
```

### Documentation Handoff

**Create operations documentation:**

```bash
# Create handoff document
cat > OPERATIONS.md << 'EOF'
# 3D Print Pro - Operations Guide

## Server Access
- SSH: ssh user@3dprint-omsk.ru
- SSH Key: /path/to/key.pem
- Server IP: XXX.XXX.XXX.XXX

## Database Access
- Host: localhost
- Database: ch167436_3dprint
- User: ch167436_3dprint
- Password: [see password manager]

## Admin Access
- URL: https://3dprint-omsk.ru/admin/
- Super Admin: admin@3dprint-omsk.ru
- Password: [see password manager]

## Monitoring
- Uptime: https://uptimerobot.com/dashboard
- Analytics: https://analytics.google.com
- Errors: /var/www/html/logs/api.log

## Backup Locations
- Database: /var/www/html/storage/backups/
- Files: /backups/3dprint-omsk/
- Off-site: s3://3dprint-omsk-backups/

## Emergency Contacts
- Developer: +7 XXX XXX-XX-XX
- Hosting: support@hosting.com
- Domain: registrar@domain.com

## Common Tasks
- Deploy update: `bash scripts/deploy.sh`
- Database backup: `php database/backup.php`
- Create admin: `php scripts/create-admin.php`
- Check logs: `tail -f logs/api.log`
- Run tests: `composer test`

## Troubleshooting
See docs/TROUBLESHOOTING.md

## Runbook
See docs/PRODUCTION_RUNBOOK.md
EOF
```

**Create password documentation (store securely):**

Use password manager (1Password, LastPass, Bitwarden) to store:
- Server SSH credentials
- Database passwords
- Admin panel passwords
- SMTP passwords
- API keys (Telegram, Analytics)
- SSL certificate passwords

---

## Final Launch Checklist

### Step 20: Go-Live Validation

Before announcing site launch, verify:

### Pre-Launch Checklist

**Environment:**
- [ ] Production environment validated (`php scripts/hosting-audit.php --strict`)
- [ ] All dependencies installed (`composer install --no-dev`)
- [ ] `.env` configured with production values
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] HTTPS enabled and working
- [ ] SSL certificate valid (A+ rating on SSLLabs)

**Database:**
- [ ] Database provisioned with correct collation (utf8mb4_unicode_ci)
- [ ] Schema imported and verified (18 tables)
- [ ] Initial data seeded
- [ ] Automated backups scheduled (cron)
- [ ] Backup rotation configured (30 days retention)

**Admin Panel:**
- [ ] First admin user created
- [ ] Login working
- [ ] Dashboard displays correctly
- [ ] All CRUD operations tested
- [ ] Users management working (super_admin only)
- [ ] Audit logs recording actions

**Content:**
- [ ] Services added (minimum 5)
- [ ] Portfolio items added (minimum 10)
- [ ] Testimonials added (minimum 5)
- [ ] FAQ items added (minimum 10)
- [ ] Content blocks configured
- [ ] All images uploaded and optimized

**Settings:**
- [ ] Contact info configured (phone, email, address)
- [ ] Social media links added
- [ ] SEO metadata configured (title, description, keywords)
- [ ] Working hours set
- [ ] GPS coordinates configured

**Notifications:**
- [ ] Telegram bot configured
- [ ] Telegram test message successful
- [ ] SMTP configured
- [ ] Email test successful
- [ ] Order notification tested (Telegram + Email)

**Forms:**
- [ ] Contact form tested
- [ ] Calculator tested
- [ ] Form validation working
- [ ] Order creation verified
- [ ] Notifications received

**Performance:**
- [ ] Page load times < 3 seconds
- [ ] API response times < 500ms
- [ ] Images optimized
- [ ] Caching enabled (Redis or file)
- [ ] HTTP compression enabled
- [ ] Browser caching configured

**Security:**
- [ ] SSL/HTTPS enforced
- [ ] Security headers configured
- [ ] CSRF protection working
- [ ] Rate limiting active
- [ ] File permissions correct (755/644/600)
- [ ] Sensitive files secured (.env, config.php)
- [ ] SQL injection tested
- [ ] XSS protection verified

**Monitoring:**
- [ ] Error logging configured
- [ ] Log rotation setup
- [ ] Uptime monitoring enabled (UptimeRobot)
- [ ] Analytics installed (Google Analytics, Yandex.Metrica)
- [ ] Performance monitoring setup
- [ ] Database health checks scheduled

**Backups:**
- [ ] Automated database backups scheduled (daily)
- [ ] File backups scheduled (weekly)
- [ ] Off-site backups configured
- [ ] Backup verification tested
- [ ] Restore procedure tested

**QA:**
- [ ] All pages tested
- [ ] All forms tested
- [ ] Admin panel tested
- [ ] Mobile responsive verified
- [ ] Cross-browser tested (Chrome, Firefox, Safari, Edge)
- [ ] Smoke tests passed (`php scripts/api_smoke.php`)
- [ ] PHPUnit tests passed (`composer test`)

**Documentation:**
- [ ] Operations guide created
- [ ] Passwords documented (secure storage)
- [ ] Server access documented
- [ ] Backup locations documented
- [ ] Emergency contacts documented
- [ ] Runbook reviewed

**Legal:**
- [ ] Privacy policy page added
- [ ] Terms of service page added
- [ ] Cookie consent (if required)
- [ ] GDPR compliance (if applicable)

### Launch Day Tasks

**Morning of launch:**

1. **Final backup:**
   ```bash
   php database/backup.php --verify
   ```

2. **Smoke tests:**
   ```bash
   php scripts/api_smoke.php
   composer test
   ```

3. **Clear cache:**
   ```bash
   rm -rf storage/cache/*
   ```

4. **Verify monitoring:**
   - Check UptimeRobot active
   - Check Sentry/Graylog (if configured)
   - Verify analytics tracking

5. **Test from multiple locations:**
   - Open incognito: https://3dprint-omsk.ru/
   - Test from mobile device
   - Test from different network

6. **Verify notifications:**
   - Submit test order
   - Check Telegram notification
   - Check email notification

**After launch:**

1. **Monitor first hour:**
   ```bash
   tail -f logs/api.log
   tail -f /var/log/nginx/error.log
   ```

2. **Check real-time analytics:**
   - Google Analytics Real-Time
   - Yandex.Metrica Real-Time

3. **Monitor server resources:**
   ```bash
   htop
   df -h
   ```

4. **Test key workflows:**
   - Homepage load
   - Service browsing
   - Calculator usage
   - Form submission
   - Admin login

5. **Check for errors:**
   ```bash
   grep -i error logs/api.log
   grep -i error /var/log/nginx/error.log
   ```

**First 24 hours:**

- [ ] Monitor uptime (should be 100%)
- [ ] Check for error spikes
- [ ] Verify order notifications working
- [ ] Monitor database performance
- [ ] Check backup automation ran
- [ ] Review analytics data
- [ ] Test from various devices/networks
- [ ] Monitor social media mentions
- [ ] Collect user feedback

**First week:**

- [ ] Review error logs daily
- [ ] Monitor performance metrics
- [ ] Check backup integrity
- [ ] Review analytics trends
- [ ] Optimize based on real usage
- [ ] Address any user-reported issues
- [ ] Update documentation as needed

### Success Criteria

Site is considered successfully launched when:

✅ **Availability**
- Uptime: 99.9%+ (first week)
- No critical errors
- All pages accessible

✅ **Performance**
- Page load: < 3 seconds
- API response: < 500ms
- No timeout errors

✅ **Functionality**
- All forms working
- Orders being received
- Notifications sending
- Admin panel functional

✅ **Security**
- No security incidents
- SSL working
- No vulnerabilities reported

✅ **Monitoring**
- Uptime monitoring active
- Analytics tracking users
- Error logging working
- Backups running

---

## Troubleshooting

### Common Issues

#### Site Not Loading

**Symptoms:** White screen, 500 error, or connection timeout

**Solutions:**

1. Check web server status:
   ```bash
   sudo systemctl status nginx
   sudo systemctl status php-fpm
   ```

2. Check error logs:
   ```bash
   tail -50 /var/log/nginx/error.log
   tail -50 logs/api.log
   ```

3. Verify file permissions:
   ```bash
   ls -la .env
   ls -la storage/
   ```

4. Test PHP:
   ```bash
   php -v
   php -m  # Check extensions
   ```

5. Check database connection:
   ```bash
   php -r "require 'vendor/autoload.php'; \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); \$dotenv->load(); echo 'DB: ' . getenv('DB_DATABASE');"
   ```

#### Forms Not Submitting

**Symptoms:** Form hangs, no success message, no order created

**Solutions:**

1. Check browser console (F12):
   - Look for JavaScript errors
   - Check network tab for failed requests

2. Check API logs:
   ```bash
   tail -f logs/api.log | grep POST
   ```

3. Test API directly:
   ```bash
   curl -X POST https://3dprint-omsk.ru/api/orders.php \
     -H "Content-Type: application/json" \
     -d '{"name":"Test","phone":"+79991234567","message":"Test"}'
   ```

4. Verify database connectivity:
   ```bash
   mysql -u ch167436_3dprint -p -e "SHOW TABLES;"
   ```

5. Check CSRF token (for admin forms):
   - Verify session cookie present
   - Check token in form HTML
   - Verify token validation logic

#### Telegram Notifications Not Sending

**Symptoms:** Orders created but no Telegram message

**Solutions:**

1. Test Telegram from admin:
   - Settings → Telegram → Test Telegram

2. Verify bot token and chat ID:
   ```bash
   grep TELEGRAM .env
   ```

3. Test bot manually:
   ```bash
   curl "https://api.telegram.org/bot{TOKEN}/getMe"
   ```

4. Check Telegram service logs:
   ```bash
   grep -i telegram logs/api.log
   ```

5. Verify bot not blocked:
   - Open Telegram
   - Check conversation with bot
   - Send `/start` command

#### Email Notifications Not Sending

**Symptoms:** No email notifications received

**Solutions:**

1. Test email from admin:
   - Settings → Email → Test Email

2. Check SMTP settings:
   ```bash
   grep SMTP .env
   ```

3. Test SMTP connection:
   ```bash
   telnet smtp.yandex.ru 465
   ```

4. Check email logs:
   ```bash
   grep -i smtp logs/api.log
   grep -i mail /var/log/mail.log
   ```

5. Verify SPF/DKIM records:
   ```bash
   dig txt 3dprint-omsk.ru
   ```

#### Slow Performance

**Symptoms:** Pages load slowly, timeouts

**Solutions:**

1. Check server resources:
   ```bash
   htop
   df -h
   ```

2. Enable query logging:
   ```sql
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 1;
   ```

3. Optimize database:
   ```bash
   mysql -e "OPTIMIZE TABLE orders, services, portfolio;"
   ```

4. Enable Redis caching (if available):
   ```bash
   # In .env
   CACHE_DRIVER=redis
   ```

5. Check for large log files:
   ```bash
   du -sh logs/*
   ```

#### Database Connection Failed

**Symptoms:** "Cannot connect to database" error

**Solutions:**

1. Verify MySQL running:
   ```bash
   sudo systemctl status mysql
   ```

2. Test connection:
   ```bash
   mysql -u ch167436_3dprint -p ch167436_3dprint -e "SELECT 1;"
   ```

3. Check credentials:
   ```bash
   cat .env | grep DB_
   ```

4. Verify user privileges:
   ```sql
   SHOW GRANTS FOR 'ch167436_3dprint'@'localhost';
   ```

5. Check MySQL error log:
   ```bash
   tail -50 /var/log/mysql/error.log
   ```

### Support Resources

- **Documentation:** `/docs` directory
- **Runbook:** `docs/PRODUCTION_RUNBOOK.md`
- **Deployment:** `docs/DEPLOYMENT.md`
- **Troubleshooting:** `docs/TROUBLESHOOTING.md`
- **API Reference:** `docs/API_REFERENCE.md`
- **Admin Guide:** `docs/ADMIN_GUIDE.md`

---

## Conclusion

This runbook provides a comprehensive guide for deploying and operating 3D Print Pro (3dprint-omsk.ru) in production. Follow each section carefully, verify all checklist items, and maintain regular backups.

**Key takeaways:**

1. ✅ **Always validate** hosting environment before deployment
2. ✅ **Use automation** (`scripts/deploy.sh`) for consistent deployments
3. ✅ **Test thoroughly** before going live
4. ✅ **Monitor continuously** after launch
5. ✅ **Backup regularly** (database + files)
6. ✅ **Document everything** for future maintenance

**For additional help:**
- Review specific documentation in `/docs`
- Run smoke tests to validate changes
- Check error logs for debugging
- Consult `TROUBLESHOOTING.md` for common issues

**Deployment complete! 🚀**

Your 3D Print Pro site is now live at **https://3dprint-omsk.ru**
