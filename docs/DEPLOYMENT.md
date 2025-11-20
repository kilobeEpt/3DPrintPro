# Deployment Guide

Complete guide for deploying 3D Print Pro to production hosting.

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

1. **Copy Configuration**
   ```bash
   cp api/config.example.php api/config.php
   chmod 600 api/config.php
   ```

2. **Edit Configuration**
   ```php
   <?php
   // Database Configuration
   define('DB_HOST', 'localhost');  // Usually 'localhost'
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   
   // Rate Limiting
   define('RATE_LIMIT_MAX_REQUESTS', 60);
   define('RATE_LIMIT_TIME_WINDOW', 60);
   
   // Security Tokens (CHANGE THESE!)
   define('RESET_TOKEN', 'YOUR_RANDOM_TOKEN_HERE');
   ```

3. **Test Connection**
   ```bash
   curl https://your-domain.com/api/test.php
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

### Step 6: Configure HTTPS

#### Install SSL Certificate

**Let's Encrypt (Free):**
```bash
# Install certbot
sudo apt-get install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d your-domain.com -d www.your-domain.com

# Auto-renewal
sudo certbot renew --dry-run
```

**Or use hosting panel SSL installer**

#### Force HTTPS Redirect

Add to root `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [L,R=301]
```

### Step 7: Configure Domain

1. **DNS Settings**
   - A Record: `your-domain.com` → `server_ip_address`
   - A Record: `www.your-domain.com` → `server_ip_address`
   - Wait for propagation (up to 48 hours)

2. **Verify DNS**
   ```bash
   ping your-domain.com
   nslookup your-domain.com
   ```

### Step 8: Final Verification

#### Run Database Audit

```bash
# Via CLI
php scripts/db_audit.php

# Via HTTP
curl https://your-domain.com/api/test.php?audit=full
```

All checks should pass ✅

#### Test Frontend

1. Open: `https://your-domain.com/`
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

## Post-Deployment Configuration

### Configure Telegram Notifications

1. **Create Telegram Bot** (if not done)
   - Message @BotFather: `/newbot`
   - Follow prompts
   - Save bot token

2. **Get Chat ID**
   - Start conversation with your bot
   - Send a message
   - Visit: `https://api.telegram.org/bot{TOKEN}/getUpdates`
   - Find `"chat":{"id":123456789}`

3. **Configure in Admin**
   - Login to admin panel
   - Go to Settings
   - Enter Bot Token
   - Enter Chat ID
   - Save
   - Click "Send Test Message"
   - Verify message received ✅

See [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md) for details.

### Customize Content

Via admin panel:
1. **Services** - Edit/add your services
2. **Portfolio** - Add your projects
3. **Testimonials** - Add client reviews
4. **FAQ** - Update questions/answers
5. **Settings** - Update company info

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
