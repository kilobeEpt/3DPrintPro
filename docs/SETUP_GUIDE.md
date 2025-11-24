# Setup Guide

Complete guide for setting up the 3D Print Pro website from scratch.

## Prerequisites

- **Web Server**: Apache/Nginx with PHP 7.4+ support
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **PHP Extensions**: PDO, PDO_MySQL, cURL, mbstring, JSON
- **HTTPS**: SSL certificate (recommended, required for secure sessions)
- **Access**: FTP/SFTP or SSH access to your hosting

## Quick Start (7 Minutes)

### Step 1: Database Setup (2 minutes)

1. **Create Database**
   ```sql
   CREATE DATABASE ch167436_3dprint 
   CHARACTER SET utf8mb4 
   COLLATE utf8mb4_unicode_ci;
   ```

2. **Create Schema**
   - Open PHPMyAdmin
   - Select your database
   - Go to **SQL** tab
   - Import `database/schema.sql`
   - Or via CLI:
     ```bash
     mysql -u username -p database_name < database/schema.sql
     ```

3. **Seed Initial Data**
   - Open in browser: `https://your-domain.com/api/init-database.php`
   - Wait for success message
   - This creates:
     - 6 default services
     - 4 sample testimonials
     - 8 FAQ items
     - 3 content blocks
     - 15+ settings

### Step 2: Configure Backend (3 minutes)

1. **Copy Configuration Template**
   ```bash
   cp api/config.example.php api/config.php
   ```

2. **Edit Database Credentials** (`api/config.php`)
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```

3. **Set Security Tokens**
   ```php
   // Change these defaults!
   define('RESET_TOKEN', 'YOUR_RANDOM_TOKEN_HERE');
   ```

4. **Test Connection**
   - Open: `https://your-domain.com/api/test.php`
   - Look for: `"database_status": "Connected"`
   - If successful, you're connected! ✅

### Step 3: Setup Admin Access (2 minutes)

1. **Create Admin Credentials** (via SSH)
   ```bash
   cd /path/to/your/site
   php scripts/setup-admin-credentials.php
   ```
   
   Follow prompts to set:
   - Admin username (default: `admin`)
   - Admin password (choose strong password)

2. **Access Admin Panel**
   - Open: `https://your-domain.com/admin/login.php`
   - Login with your credentials
   - You should see the dashboard ✅

3. **Configure Telegram** (optional but recommended)
   - Go to **Settings** page
   - Enter Telegram Bot Token and Chat ID
   - Click **Send Test Message**
   - See [Telegram Integration Guide](TELEGRAM_INTEGRATION.md) for details

## Detailed Setup Instructions

### File Upload

1. **Upload Files via FTP/SFTP**
   ```
   Upload all files maintaining directory structure:
   ├── admin/          # Admin panel files
   ├── api/            # PHP API endpoints
   ├── css/            # Stylesheets
   ├── database/       # SQL schema and seed data
   ├── docs/           # Documentation
   ├── js/             # JavaScript files
   ├── logs/           # Log directory (create if missing)
   ├── scripts/        # Utility scripts
   ├── *.html          # Public pages
   ├── config.js       # Frontend configuration
   └── robots.txt      # SEO files
   ```

2. **Set File Permissions**
   ```bash
   # Directories
   chmod 755 admin api css database docs js scripts logs
   
   # PHP files
   chmod 644 api/*.php admin/*.php scripts/*.php
   
   # Configuration (more restrictive)
   chmod 600 api/config.php
   
   # Logs directory (writable)
   chmod 755 logs
   chmod 644 logs/*.log
   ```

3. **Verify .htaccess Files**
   - `api/.htaccess` - Protects config.php, sets CORS headers
   - Check if mod_rewrite is enabled on your server

### Database Configuration

#### Option A: PHPMyAdmin

1. Login to PHPMyAdmin
2. Create new database:
   - Name: `your_database_name`
   - Collation: `utf8mb4_unicode_ci`
3. Select database
4. Go to **Import** tab
5. Choose file: `database/schema.sql`
6. Click **Go**
7. Verify 7 tables created:
   - orders
   - settings
   - services
   - portfolio
   - testimonials
   - faq
   - content_blocks

#### Option B: MySQL CLI

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Grant privileges
GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;

# Exit and import schema
exit
mysql -u your_user -p your_database_name < database/schema.sql

# Verify
mysql -u your_user -p your_database_name -e "SHOW TABLES;"
```

### Frontend Configuration

Edit `config.js` to set your site details:

```javascript
const CONFIG = {
    siteName: '3D Print Pro',
    siteUrl: 'https://your-domain.com',
    
    // API endpoints (usually keep defaults)
    api: {
        baseUrl: '/api',
        endpoints: {
            orders: 'orders.php',
            services: 'services.php',
            // ...
        }
    },
    
    // Calculator settings
    calculator: {
        basePricePerGram: 2.5,
        currency: '₽',
        // ...
    }
};
```

### Verification Steps

1. **Check API Health**
   ```bash
   curl https://your-domain.com/api/test.php
   ```
   Expected: `{"success":true,"database_status":"Connected",...}`

2. **Check Database Audit**
   ```bash
   curl https://your-domain.com/api/test.php?audit=full
   ```
   Expected: All checks ✅

3. **Test Frontend**
   - Open: `https://your-domain.com/`
   - Open DevTools (F12) → Console
   - Should see:
     ```
     ✅ APIClient initialized
     ✅ Database initialized
     ✅ Приложение запущено
     ```

4. **Test Form Submission**
   - Fill contact form
   - Submit
   - Check orders table in database
   - Should see new order ✅

5. **Test Admin Panel**
   - Login to admin panel
   - Check dashboard loads
   - View orders list
   - Verify data displays ✅

## Troubleshooting

### Database Connection Failed

**Symptoms:**
- API returns 500 errors
- "Database connection failed" message
- Empty data on frontend

**Solutions:**
1. Check credentials in `api/config.php`
2. Verify MySQL is running: `systemctl status mysql`
3. Check user permissions:
   ```sql
   SHOW GRANTS FOR 'your_user'@'localhost';
   ```
4. Run database audit:
   ```bash
   php scripts/db_audit.php
   ```

### Tables Not Found

**Symptoms:**
- "Table 'dbname.services' doesn't exist"
- API returns empty arrays

**Solutions:**
1. Verify schema was imported:
   ```sql
   SHOW TABLES;
   ```
2. Re-import schema:
   ```bash
   mysql -u user -p database < database/schema.sql
   ```
3. Run seed script:
   ```
   https://your-domain.com/api/init-database.php
   ```

### No Data Showing

**Symptoms:**
- Services section empty
- FAQ section empty
- Console shows no errors

**Solutions:**
1. Check if data exists:
   ```sql
   SELECT COUNT(*) FROM services WHERE active = 1;
   ```
2. If zero, run seed:
   ```
   https://your-domain.com/api/init-database.php
   ```
3. If data exists but not showing, check `active` flag:
   ```
   https://your-domain.com/api/init-check.php?fix_active=1
   ```

### Admin Login Fails

**Symptoms:**
- "Invalid credentials" error
- Cannot access admin panel

**Solutions:**
1. Reset admin credentials:
   ```bash
   php scripts/setup-admin-credentials.php admin NewPassword123
   ```
2. Check settings table:
   ```sql
   SELECT * FROM settings WHERE setting_key LIKE 'admin%';
   ```
3. Clear browser cookies and try again

### Permissions Errors

**Symptoms:**
- "Permission denied" errors
- Cannot write to logs
- Session errors

**Solutions:**
1. Check file ownership:
   ```bash
   ls -la api/config.php
   ls -la logs/
   ```
2. Fix permissions:
   ```bash
   chmod 644 api/config.php
   chmod 755 logs/
   ```
3. Check PHP user:
   ```php
   <?php echo exec('whoami'); ?>
   ```

## Next Steps

After successful setup:

1. **Configure Telegram** - [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md)
2. **Customize Content** - Edit services, portfolio, FAQ via admin panel
3. **Deploy to Production** - [DEPLOYMENT.md](DEPLOYMENT.md)
4. **Test Everything** - Follow admin guide for testing procedures

## Advanced Configuration

### Enable HTTPS (Recommended)

1. Install Let's Encrypt certificate:
   ```bash
   certbot --apache -d your-domain.com
   ```

2. Enable HTTPS redirect in `.htaccess`:
   ```apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [L,R=301]
   ```

### Configure PHP Settings

Add to `php.ini` or `.htaccess`:

```ini
; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Lax"

; Upload limits
upload_max_filesize = 10M
post_max_size = 10M

; Error logging
error_log = /path/to/logs/php-error.log
log_errors = On
display_errors = Off
```

### Setup Cron Jobs (Optional)

```bash
# Clean old rate limit files (daily)
0 2 * * * find /path/to/logs/rate_limits/ -type f -mtime +1 -delete

# Backup database (daily)
0 3 * * * /usr/bin/php /path/to/database/backup.php
```

## Support

If you encounter issues:

1. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Run database audit: `php scripts/db_audit.php`
3. Check logs: `tail -f logs/api.log`
4. Review API responses: `curl https://your-domain.com/api/test.php?audit=full`
