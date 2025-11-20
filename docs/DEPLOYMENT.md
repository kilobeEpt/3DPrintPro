# Deployment Guide

Complete guide for deploying 3D Print Pro to production hosting.

**📖 For comprehensive end-to-end production operations, see [PRODUCTION_RUNBOOK.md](PRODUCTION_RUNBOOK.md).**
**🤖 For CI/CD automation with GitHub Actions, see [CI_CD.md](CI_CD.md).**

## Quick Deployment

### CI/CD Deployment (Recommended for Production)

Use GitHub Actions for automated, audited deployments with built-in rollback:

```bash
# Push to main branch (triggers automatic deployment)
git push origin main

# Or manually trigger via GitHub CLI
gh workflow run deploy.yml --ref main

# Rollback to previous release
gh workflow run deploy.yml --ref main -f rollback_release=release_20240120_120530
```

**Features:**
- ✅ Automated testing (PHPUnit)
- ✅ Manual approval gate (production environment)
- ✅ Timestamped releases with rollback support
- ✅ Post-deployment smoke tests
- ✅ Deployment logs as artifacts (30-day retention)
- ✅ One-click rollback capability

📖 **See [CI_CD.md](CI_CD.md) for complete CI/CD pipeline documentation, GitHub secrets setup, and rollback procedures.**

### Automated Script Deployment

Use the automated deployment script for manual or server-side deployments:

```bash
# Test deployment (dry run)
bash scripts/deploy.sh --dry-run

# Production deployment
bash scripts/deploy.sh

# CI/CD deployment (non-interactive)
bash scripts/deploy.sh --ci
```

**What it does:**
1. ✅ Validates hosting environment (`hosting-audit.php --strict`)
2. ✅ Installs composer dependencies (`--no-dev --optimize-autoloader`)
3. ✅ Configures environment (`.env` from template)
4. ✅ Sets permissions (`storage/`, `logs/`, `.env`)
5. ✅ Verifies database schema
6. ✅ Runs smoke tests (`api_smoke.php`)
7. ✅ Generates deployment report

**Deployment logs:** `storage/logs/deploy_YYYYMMDD_HHMMSS.log`

📖 **See [PRODUCTION_RUNBOOK.md](PRODUCTION_RUNBOOK.md) for complete production deployment guide with post-deployment configuration, monitoring, and troubleshooting.**

---

## Pre-Deployment Checklist

### Hosting Environment Audit

**⚠️ CRITICAL STEP: Run hosting audit before proceeding with deployment**

Validate your hosting environment meets all requirements:

```bash
# Navigate to project directory
cd /path/to/project

# Run hosting audit
php scripts/hosting-audit.php

# Or for shared hosting (skip Redis checks)
php scripts/hosting-audit.php --skip-redis

# Generate JSON report for documentation
php scripts/hosting-audit.php --format=json > hosting-audit-report.json
```

**Expected Result**: All CRITICAL checks must PASS before deployment.

📖 **See [HOSTING_AUDIT.md](HOSTING_AUDIT.md) for detailed instructions, troubleshooting, and remediation steps.**

**Hosting Audit Checklist**:

- [ ] PHP version >= 7.4 (recommended: 8.1+)
- [ ] Required PHP extensions installed (pdo_mysql, mbstring, intl, json, curl, openssl, zip)
- [ ] CLI tools available (composer, php, mysql, mysqldump)
- [ ] MySQL service running
- [ ] Minimum 1 GB disk space free
- [ ] Minimum 256 MB memory available
- [ ] Storage directories writable (storage/, logs/, storage/cache/)
- [ ] Project root writable by SSH user
- [ ] Audit report attached to deployment ticket

### Files and Code

- [ ] All HTML files present (10+ pages)
- [ ] All CSS files: `style.css`, `responsive.css`, `animations.css`
- [ ] All JavaScript files in `/js` directory
- [ ] Admin panel files in `/admin` directory
- [ ] API endpoints in `/api` directory
- [ ] Database schema: `database/schema.sql`
- [ ] Configuration template: `api/config.example.php`
- [ ] Documentation in `/docs`
- [ ] `robots.txt` configured
- [ ] `sitemap.xml` configured

### Local Testing

- [ ] Site loads without errors
- [ ] Calculator functions correctly
- [ ] Forms validate properly
- [ ] Navigation works on all pages
- [ ] Mobile responsive (test on device)
- [ ] No console errors (F12)
- [ ] Database connection works locally
- [ ] Admin panel accessible

## Deployment Process

### Option A: Automated Deployment (Recommended)

**Use the deployment script for streamlined, consistent deployments:**

```bash
# 1. Validate hosting environment
php scripts/hosting-audit.php --strict

# 2. Upload files to server
rsync -avz --exclude 'vendor' --exclude '.git' --exclude 'node_modules' \
  /local/path/to/project/ user@3dprint-omsk.ru:/var/www/html/

# 3. SSH into server
ssh user@3dprint-omsk.ru
cd /var/www/html

# 4. Run deployment script
bash scripts/deploy.sh

# 5. Configure environment
nano .env  # Edit with production credentials

# 6. Create admin user
php scripts/create-admin.php

# 7. Verify
php scripts/api_smoke.php
```

**Deployment complete!** See [PRODUCTION_RUNBOOK.md](PRODUCTION_RUNBOOK.md) for post-deployment configuration (Telegram, email, monitoring, etc.).

---

### Option B: Manual Deployment

Follow these steps for manual deployment:

### Step 1: Validate Hosting & Upload Files

**⚠️ Before uploading files, ensure hosting audit passed (see Pre-Deployment Checklist above)**

If you haven't run the hosting audit yet, do so now:

```bash
# On the target server, upload and run the audit script first
scp scripts/hosting-audit.php user@your-server.com:/tmp/
ssh user@your-server.com 'php /tmp/hosting-audit.php --skip-redis'
```

Once the audit passes, proceed with file upload.

#### Via FTP/SFTP

```bash
# Connect to server
sftp user@your-domain.com

# Upload files
put -r /local/path/to/project/* /remote/path/

# Verify upload
ls -la
```

#### Maintain Directory Structure

```
your-domain.com/
├── admin/              # Admin panel
├── api/                # PHP API
├── css/                # Stylesheets
├── database/           # SQL files
├── docs/               # Documentation
├── js/                 # JavaScript
├── logs/               # Logs (create if missing)
├── scripts/            # Utilities
├── *.html              # Public pages
├── config.js           # Frontend config
├── robots.txt
└── sitemap.xml
```

#### Set Permissions

```bash
# SSH into server
ssh user@your-domain.com

cd /path/to/site

# Set directory permissions
chmod 755 admin api css database docs js scripts
chmod 755 logs  # Must be writable

# Set file permissions
find . -type f -name "*.html" -exec chmod 644 {} \;
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;

# Secure configuration
chmod 600 api/config.php

# Ensure logs writable
chmod 644 logs/*.log 2>/dev/null || true
```

### Step 2: Database Setup

📖 **Complete guide available in [DATABASE_OPERATIONS.md](DATABASE_OPERATIONS.md)**

#### Automated Provisioning (Recommended)

The fastest way to set up your database is using the automated provisioning script:

```bash
# Navigate to project directory
cd /path/to/project

# Configure database credentials in .env
cp .env.example .env
nano .env  # Edit DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Run provisioning with seeding
php scripts/provision-database.php --seed
```

This will:
- ✅ Create database with UTF8MB4 collation
- ✅ Create application user with restricted privileges
- ✅ Import complete schema (18 tables)
- ✅ Seed baseline data (services, forms, settings)
- ✅ Verify schema integrity
- ✅ Display backup automation commands

**Expected output:**
```
✅ Database Provisioning Complete!

Database: ch167436_3dprint
User:     ch167436_3dprint
Host:     localhost

📦 Backup Automation
Add these cron jobs for automated backups:

# Daily full backup at 2 AM (keep 30 days)
0 2 * * * cd /path/to/project && php database/backup.php --retention=30 >> logs/backup.log 2>&1
```

#### Manual Setup (Alternative)

If you prefer manual setup or don't have admin MySQL credentials:

**Via PHPMyAdmin:**
1. Login to PHPMyAdmin
2. Click "New" → Create Database
3. Database name: `your_database_name`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Go to "Import" tab
7. Choose file: `database/schema.sql`
8. Click "Go"

**Via MySQL CLI:**
```bash
# Create database
mysql -u root -p
```
```sql
CREATE DATABASE your_database_name 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

CREATE USER 'your_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Import schema
mysql -u your_user -p your_database_name < database/schema.sql

# Verify tables
mysql -u your_user -p your_database_name -e "SHOW TABLES;"
```

Expected output: 18 tables including `orders`, `services`, `portfolio`, `admin_users`, etc.

#### Verify Schema

```bash
# Run verification script
php database/verify-schema.php
```

Expected result: All 18 tables present with correct structure.

#### Setup Backup Automation

Add to crontab (`crontab -e`):

```bash
# Daily full backup at 2 AM (keep 30 days)
0 2 * * * cd /path/to/project && php database/backup.php --retention=30 >> logs/backup.log 2>&1

# Weekly schema-only backup (keep 12 weeks)
0 3 * * 0 cd /path/to/project && php database/backup.php --schema-only --retention=12 >> logs/backup.log 2>&1
```

**Note**: The provision script outputs ready-to-copy cron commands with correct paths.

📖 **For detailed database operations, backup strategies, and restore procedures, see [DATABASE_OPERATIONS.md](DATABASE_OPERATIONS.md)**

### Step 3: Configure Backend

1. **Copy Production Environment Template**
   ```bash
   cp .env.production.example .env
   chmod 600 .env
   ```

2. **Edit Environment Configuration**
   ```bash
   nano .env
   ```
   
   **Required values:**
   - `DB_PASSWORD` - MySQL database password
   - `TELEGRAM_BOT_TOKEN` - Telegram bot token
   - `TELEGRAM_CHAT_ID` - Your Telegram chat ID
   - `SMTP_PASSWORD` - SMTP password for emails
   
   **Security settings:**
   - `APP_DEBUG=false` (CRITICAL for production)
   - `APP_ENV=production`
   - `SESSION_SECURE_COOKIE=true` (requires HTTPS)

3. **Install Composer Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   ```

4. **Test Connection**
   ```bash
   curl https://3dprint-omsk.ru/api/test.php
   ```
   
   Expected response:
   ```json
   {
     "success": true,
     "database_status": "Connected",
     "mysql_version": "8.0.32"
   }
   ```

### Step 4: Initialize Data

1. **Seed Database**
   Open in browser:
   ```
   https://your-domain.com/api/init-database.php
   ```
   
   This creates:
   - 6 default services
   - 4 sample testimonials
   - 8 FAQ items
   - 3 content blocks
   - 15+ settings including Telegram config

2. **Verify Data**
   ```bash
   curl https://your-domain.com/api/services.php
   curl https://your-domain.com/api/faq.php
   ```

### Step 5: Setup Admin Access

1. **Create Admin Credentials**
   ```bash
   cd /path/to/site
   php scripts/setup-admin-credentials.php
   ```
   
   Follow prompts:
   - Enter username (e.g., `admin`)
   - Enter strong password
   - Confirm

2. **Test Admin Login**
   - Open: `https://your-domain.com/admin/login.php`
   - Login with credentials
   - Should see dashboard ✅

### Steps 4-6: Web Server, DNS & SSL Configuration

📖 **Complete guide available in [WEB_SERVER_CONFIG.md](WEB_SERVER_CONFIG.md)**

For detailed configuration of Nginx/Apache, DNS records, and SSL certificates, see the dedicated guide which covers:

- ✅ **Nginx configuration** - Full production config with PHP-FPM, SSE, security headers
- ✅ **Apache configuration** - VirtualHost setup with mod_php/FPM support
- ✅ **DNS setup** - A/AAAA records, MX/SPF/DKIM for email, propagation verification
- ✅ **SSL certificates** - Let's Encrypt with certbot, automatic renewal, troubleshooting
- ✅ **Security verification** - SSL Labs testing, security header validation, penetration testing
- ✅ **Rate limiting & firewall** - Nginx/Apache rate limits, UFW/firewalld, Fail2Ban, IP whitelisting

#### Quick Setup (Nginx + SSL)

```bash
# 1. Copy configuration
sudo cp deploy/webserver/nginx.3dprint-omsk.conf /etc/nginx/sites-available/3dprint-omsk.conf
sudo cp deploy/webserver/snippets/security.conf /etc/nginx/snippets/3dprint-security.conf

# 2. Customize paths (edit PHP-FPM socket, project root)
sudo nano /etc/nginx/sites-available/3dprint-omsk.conf

# 3. Enable site
sudo ln -s /etc/nginx/sites-available/3dprint-omsk.conf /etc/nginx/sites-enabled/

# 4. Test configuration
sudo nginx -t

# 5. Obtain SSL certificate
sudo certbot --nginx -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# 6. Reload Nginx
sudo systemctl reload nginx
```

#### Quick Setup (Apache + SSL)

```bash
# 1. Copy configuration
sudo cp deploy/webserver/apache.3dprint-omsk.conf /etc/apache2/sites-available/3dprint-omsk.conf

# 2. Enable required modules
sudo a2enmod rewrite ssl headers expires deflate http2

# 3. Customize paths (edit DocumentRoot, SSL paths)
sudo nano /etc/apache2/sites-available/3dprint-omsk.conf

# 4. Enable site
sudo a2ensite 3dprint-omsk.conf

# 5. Test configuration
sudo apachectl configtest

# 6. Obtain SSL certificate
sudo certbot --apache -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# 7. Reload Apache
sudo systemctl reload apache2
```

#### DNS Configuration Summary

```
# A Records (IPv4)
3dprint-omsk.ru        A      YOUR_SERVER_IP
www.3dprint-omsk.ru    A      YOUR_SERVER_IP

# MX Record (Email)
3dprint-omsk.ru        MX     10 mx.yandex.ru

# TXT Records (Email Authentication)
3dprint-omsk.ru        TXT    v=spf1 mx ~all
_dmarc.3dprint-omsk.ru TXT    v=DMARC1; p=quarantine; rua=mailto:dmarc@3dprint-omsk.ru
```

**Verify DNS propagation:**
```bash
dig 3dprint-omsk.ru A
dig www.3dprint-omsk.ru A
nslookup 3dprint-omsk.ru
```

📖 **For comprehensive DNS/SSL/webserver setup, see [WEB_SERVER_CONFIG.md](WEB_SERVER_CONFIG.md)**

### Step 8: Final Verification

#### Run Smoke Tests

```bash
# Run comprehensive API smoke tests
php scripts/api_smoke.php

# Expected output:
# ✅ Services API - GET/POST/PUT/DELETE
# ✅ Portfolio API - GET/POST/PUT/DELETE
# ✅ FAQ API - GET/POST/PUT/DELETE
# ✅ Testimonials API - GET/POST/PUT/DELETE
# ✅ Orders API - GET/POST
```

All checks should pass ✅

#### Test Frontend

1. Open: `https://3dprint-omsk.ru/`
2. Open DevTools (F12) → Console
3. Verify:
   ```
   ✅ APIClient initialized
   ✅ Database initialized
   ✅ Database using API
   ✅ Приложение запущено
   ```

#### Test Form Submission

1. Scroll to contact form
2. Fill in:
   - Name: Test User
   - Phone: +7 (999) 123-45-67
   - Message: Test message
3. Submit
4. Should see success notification ✅
5. Check database:
   ```sql
   SELECT * FROM orders ORDER BY id DESC LIMIT 1;
   ```

#### Test Admin Panel

- [ ] Login works
- [ ] Dashboard loads
- [ ] Orders list displays
- [ ] Services CRUD works
- [ ] Settings page accessible

#### Review Deployment Log

```bash
# View latest deployment log (if using deploy.sh)
cat storage/logs/deploy_*.log | tail -100

# Check for errors
grep -i error storage/logs/deploy_*.log
```

## Post-Deployment Configuration

**📖 For complete post-deployment setup, see [PRODUCTION_RUNBOOK.md](PRODUCTION_RUNBOOK.md) which covers:**

- ✅ **Step 9-10**: Admin user creation and global settings configuration
- ✅ **Step 11**: Content management (services, portfolio, testimonials, FAQ)
- ✅ **Step 12**: Email & Telegram notification setup
- ✅ **Step 13**: Monitoring & logging (error logs, uptime monitoring, performance)
- ✅ **Step 14**: Analytics integration (Google Analytics, Yandex.Metrica)
- ✅ **Step 15**: Performance optimization (Redis, database, caching, CDN)
- ✅ **Step 16**: Quality assurance testing (functional, mobile, browser, automated)
- ✅ **Step 17**: Security validation (SSL, headers, permissions, CSRF, XSS)
- ✅ **Step 18**: Rollback procedures
- ✅ **Step 19**: Backup strategy and automation
- ✅ **Step 20**: Final launch checklist

### Quick Post-Deployment Checklist

**Essential tasks:**

1. **Configure Telegram Bot** (see [PRODUCTION_RUNBOOK.md](PRODUCTION_RUNBOOK.md#telegram-setup))
   - Message @BotFather: `/newbot`
   - Get bot token and chat ID
   - Configure in Admin → Settings → Telegram
   - Test notification

2. **Configure SMTP Email** (see [PRODUCTION_RUNBOOK.md](PRODUCTION_RUNBOOK.md#emailsmtp-setup))
   - Configure SMTP settings in .env
   - Test in Admin → Settings → Email
   - Enable order notifications

3. **Add Initial Content**
   - Add services (minimum 5)
   - Upload portfolio projects (minimum 10)
   - Add testimonials (minimum 5)
   - Configure FAQ (minimum 10 questions)

4. **Setup Monitoring**
   - Configure error logging
   - Enable uptime monitoring (UptimeRobot)
   - Setup log rotation
   - Schedule database backups

5. **Verify Everything Works**
   - Submit test order → Check Telegram + Email
   - Test calculator
   - Test admin panel
   - Test on mobile devices

### Customize Content

Via admin panel:
1. **Services** - Edit/add your services
2. **Portfolio** - Add your projects
3. **Testimonials** - Add client reviews
4. **FAQ** - Update questions/answers
5. **Settings** - Update company info, social links, SEO metadata

### Setup Monitoring

#### Error Logs

```bash
# PHP error log
tail -f /var/log/apache2/error.log

# API logs
tail -f /path/to/site/logs/api.log

# Watch for errors
grep -i "error\|failed" logs/api.log
```

#### Create Log Rotation

Create `/etc/logrotate.d/3dprint`:
```
/path/to/site/logs/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
}
```

#### Setup Cron Jobs

```bash
crontab -e
```

Add:
```bash
# Clean rate limit files (daily at 2 AM)
0 2 * * * find /path/to/site/logs/rate_limits/ -type f -mtime +1 -delete

# Backup database (daily at 3 AM)
0 3 * * * /usr/bin/php /path/to/site/database/backup.php

# Check database health (hourly)
0 * * * * /usr/bin/php /path/to/site/scripts/db_audit.php --json > /path/to/site/logs/db_health.log
```

### SEO Configuration

1. **Update robots.txt**
   ```
   User-agent: *
   Allow: /
   Disallow: /admin/
   Disallow: /api/
   Disallow: /logs/
   
   Sitemap: https://your-domain.com/sitemap.xml
   ```

2. **Update sitemap.xml**
   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
     <url>
       <loc>https://your-domain.com/</loc>
       <lastmod>2025-01-19</lastmod>
       <changefreq>weekly</changefreq>
       <priority>1.0</priority>
     </url>
     <!-- Add all pages -->
   </urlset>
   ```

3. **Google Search Console**
   - Add property
   - Verify ownership
   - Submit sitemap

## Production Hardening

### Security Checklist

- [x] HTTPS enabled and forced
- [x] Admin credentials strong and unique
- [x] `api/config.php` protected (chmod 600)
- [x] Database credentials secure
- [x] SQL injection protected (PDO prepared statements)
- [x] XSS protected (htmlspecialchars)
- [x] CSRF tokens on admin operations
- [x] Rate limiting enabled (60/min)
- [x] Session security (HttpOnly, SameSite, Secure)
- [x] Security headers (X-Frame-Options, etc.)
- [ ] Firewall rules configured
- [ ] Regular backups scheduled
- [ ] Error reporting disabled in production

### Disable Debug Mode

In `api/config.php`:
```php
// Production settings
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
```

### Regular Maintenance

**Daily:**
- Check error logs
- Monitor order submissions
- Verify Telegram notifications

**Weekly:**
- Review database backups
- Check disk space
- Update admin password

**Monthly:**
- Security updates
- Database optimization
- Performance review

## Rollback Procedure

If deployment fails:

1. **Restore Previous Version**
   ```bash
   # Restore files from backup
   rsync -av /backup/site/ /var/www/html/
   ```

2. **Restore Database**
   ```bash
   # Restore database backup
   mysql -u user -p database_name < backup.sql
   ```

3. **Verify Rollback**
   ```bash
   curl https://your-domain.com/api/test.php
   ```

## Support

### Common Issues

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for solutions.

### Health Check Commands

```bash
# Database connection
curl https://your-domain.com/api/test.php

# Full audit
curl https://your-domain.com/api/test.php?audit=full

# CLI audit
php scripts/db_audit.php

# Check permissions
ls -la api/config.php logs/

# Check PHP version
php -v

# Check MySQL version
mysql --version
```

## Next Steps

After successful deployment:

1. Test all features thoroughly
2. Configure Telegram notifications
3. Customize content via admin panel
4. Setup regular backups
5. Monitor logs for errors
6. Add real client testimonials
7. Update services and pricing

## Production Checklist

Use this final checklist before going live:

- [ ] All files uploaded
- [ ] Database created and seeded
- [ ] HTTPS configured and working
- [ ] Admin access tested
- [ ] Telegram notifications tested
- [ ] All pages load correctly
- [ ] Forms submit successfully
- [ ] Mobile responsive verified
- [ ] SEO tags configured
- [ ] Analytics integrated (if needed)
- [ ] Error monitoring setup
- [ ] Backups configured
- [ ] Contact information updated
- [ ] Terms and privacy pages added (if required)
- [ ] Final testing completed

🚀 **Ready to Launch!**
