# Database Operations Guide

Complete guide for database provisioning, backup automation, and restore operations for 3D Print Pro.

## Table of Contents

- [Database Provisioning](#database-provisioning)
- [Backup Management](#backup-management)
- [Restore Operations](#restore-operations)
- [Maintenance Tasks](#maintenance-tasks)
- [Troubleshooting](#troubleshooting)

---

## Database Provisioning

### Overview

The `provision-database.php` script automates the complete database setup workflow:

1. Creates MySQL database with UTF8MB4 collation
2. Creates restricted application user with proper privileges
3. Imports schema (18 tables)
4. Optionally seeds baseline data
5. Verifies schema integrity

### Prerequisites

- MySQL 5.7+ or MariaDB 10.2+ running
- MySQL admin credentials (root or equivalent)
- Application database credentials configured in `.env`

### Configuration

**Option 1: Environment Variables (Recommended)**

Create or edit `.env` file:

```env
# Application Database
DB_HOST=localhost
DB_DATABASE=ch167436_3dprint
DB_USERNAME=ch167436_3dprint
DB_PASSWORD=your_secure_password

# Admin Credentials (optional, for automation)
DB_ADMIN_USER=root
DB_ADMIN_PASSWORD=admin_password
```

**Option 2: CLI Flags**

Pass credentials directly via command line:

```bash
php scripts/provision-database.php \
  --admin-user=root \
  --admin-password=your_admin_pass
```

### Basic Usage

#### Full Provisioning with Seeding

Recommended for fresh installations:

```bash
php scripts/provision-database.php --seed
```

This will:
- ✅ Create database with UTF8MB4 collation
- ✅ Create application user with restricted privileges
- ✅ Import complete schema (18 tables)
- ✅ Seed baseline data (services, forms, settings)
- ✅ Verify schema integrity

#### Create Database Only

Create database and user without importing schema:

```bash
php scripts/provision-database.php --create-only
```

Use case: Separate database creation from schema management.

#### Import Schema Only

Import schema into existing database:

```bash
php scripts/provision-database.php --import-only
```

Use case: Database and user already exist, need to update schema.

#### Force Recreate

Drop and recreate database (⚠️ **destroys all data**):

```bash
php scripts/provision-database.php --force --seed
```

Use case: Development environment reset.

### Command Reference

```
Usage: php scripts/provision-database.php [options]

Options:
  --admin-user=USER         MySQL admin username (default: root)
  --admin-password=PASS     MySQL admin password (prompts if not provided)
  --admin-host=HOST         MySQL admin host (default: localhost)
  --create-only             Only create database and user
  --import-only             Only import schema (skip db/user creation)
  --seed                    Seed baseline data after schema import
  --force                   Force drop/recreate if exists (⚠️ DESTRUCTIVE)
  --help                    Show help message

Environment Variables:
  DB_ADMIN_USER             MySQL admin username
  DB_ADMIN_PASSWORD         MySQL admin password
  DB_HOST                   Target database host
  DB_DATABASE               Target database name
  DB_USERNAME               Application database user
  DB_PASSWORD               Application user password

Exit Codes:
  0 - Success
  1 - Configuration error
  2 - Connection error
  3 - Schema import error
  4 - Verification error
```

### What Gets Created

#### Database

```sql
CREATE DATABASE ch167436_3dprint
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

- **Collation**: `utf8mb4_unicode_ci` for full Unicode support (emojis, Cyrillic)
- **Character Set**: `utf8mb4` for 4-byte UTF-8 characters

#### Application User

```sql
CREATE USER 'ch167436_3dprint'@'localhost' IDENTIFIED BY '***';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER,
      CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE,
      CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, TRIGGER
ON ch167436_3dprint.* TO 'ch167436_3dprint'@'localhost';
```

**Security Notes**:
- ✅ No `GRANT OPTION` - user cannot create other users
- ✅ No `SUPER` privilege - cannot modify server settings
- ✅ No `FILE` privilege - cannot read/write server files
- ✅ Scoped to single database only

#### Schema (18 Tables)

| Table                      | Purpose                           | Active Column |
|----------------------------|-----------------------------------|---------------|
| `orders`                   | Customer orders & inquiries       | ❌            |
| `order_status_history`     | Status change tracking            | ❌            |
| `order_notes`              | Internal order notes              | ❌            |
| `settings`                 | Application configuration         | ❌            |
| `services`                 | Service offerings                 | ✅            |
| `portfolio`                | Project showcase                  | ✅            |
| `testimonials`             | Customer reviews                  | ✅            |
| `faq`                      | Frequently asked questions        | ✅            |
| `content_blocks`           | Dynamic page content              | ✅            |
| `forms`                    | Dynamic form definitions          | ✅            |
| `form_fields`              | Form field configurations         | ✅            |
| `form_submissions`         | Form submission records           | ❌            |
| `form_submission_values`   | Individual field values           | ❌            |
| `settings_audit`           | Settings change audit             | ❌            |
| `admin_users`              | Admin accounts (RBAC)             | status enum   |
| `admin_sessions`           | Session storage                   | ❌            |
| `admin_login_attempts`     | Login tracking                    | ❌            |
| `admin_action_logs`        | Admin action audit                | ❌            |

### Baseline Data (--seed)

When using `--seed` flag, the following data is populated:

1. **Core Content** (`database/seed-data.php`)
   - 6 default services (FDM printing, SLA printing, 3D modeling, etc.)
   - 4 sample testimonials
   - 8 FAQ items
   - 3 content blocks

2. **Dynamic Forms** (`scripts/seed-forms.php`)
   - Contact form
   - Order form
   - Calculator form
   - Form fields and validation rules

3. **Calculator Settings** (`scripts/seed-calculator-settings.php`)
   - Material definitions
   - Service pricing
   - Quality multipliers
   - Discount tiers
   - Pricing formulas

4. **Global Settings** (`scripts/seed-global-settings.php`)
   - Contact information
   - Social media links
   - SEO metadata
   - SMTP configuration placeholders
   - Telegram integration placeholders

### Verification

After provisioning, verify the setup:

```bash
# Verify schema
php database/verify-schema.php

# Check tables
mysql -u ch167436_3dprint -p ch167436_3dprint -e "SHOW TABLES;"

# Check collation
mysql -u ch167436_3dprint -p ch167436_3dprint -e "
  SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
  FROM information_schema.SCHEMATA
  WHERE SCHEMA_NAME = 'ch167436_3dprint';
"
```

Expected output:
```
+----------------------------+------------------------+
| DEFAULT_CHARACTER_SET_NAME | DEFAULT_COLLATION_NAME |
+----------------------------+------------------------+
| utf8mb4                    | utf8mb4_unicode_ci     |
+----------------------------+------------------------+
```

---

## Backup Management

### Overview

The `database/backup.php` script (v2.0) provides:

- ✅ Timestamped backups with rotation
- ✅ Gzip compression
- ✅ MD5 checksums for integrity verification
- ✅ Schema-only and data-only modes
- ✅ Selective table backups
- ✅ Retention policy enforcement
- ✅ Verification testing

### Storage Location

```
storage/backups/
├── 3dprint_20250119_020000.sql.gz       # Compressed backup
├── 3dprint_20250119_020000.sql.gz.md5   # Checksum
├── 3dprint_20250119_020000.sql          # Uncompressed (optional)
└── backup.log                           # Backup operation log
```

### Manual Backup

#### Full Backup (Schema + Data)

```bash
php database/backup.php
```

Output:
```
3dprint_20250119_123045.sql.gz (12.5 MB)
3dprint_20250119_123045.sql.gz.md5
```

#### Schema Only

Useful for version control:

```bash
php database/backup.php --schema-only
```

Creates structure without data (fast, small).

#### Data Only

Useful for data archival:

```bash
php database/backup.php --data-only
```

Creates data dumps without `CREATE TABLE` statements.

#### Specific Tables

Backup selected tables:

```bash
php database/backup.php --tables=orders,order_notes,order_status_history
```

#### With Verification

Verify backup integrity immediately:

```bash
php database/backup.php --verify
```

Checks:
- ✅ File exists and is readable
- ✅ MD5 checksum matches
- ✅ Gzip integrity (if compressed)
- ✅ SQL syntax is valid

#### Uncompressed Backup

Skip gzip compression:

```bash
php database/backup.php --no-compress
```

Use case: Storage has built-in compression.

### Automated Backups

#### Recommended Cron Schedule

Add to crontab (`crontab -e`):

```bash
# Daily full backup at 2 AM (keep 30 days)
0 2 * * * cd /path/to/project && php database/backup.php --retention=30 >> logs/backup.log 2>&1

# Weekly schema-only backup at 3 AM Sunday (keep 12 weeks)
0 3 * * 0 cd /path/to/project && php database/backup.php --schema-only --retention=12 >> logs/backup.log 2>&1

# Monthly archive at 4 AM on 1st (keep 12 months)
0 4 1 * * cd /path/to/project && php database/backup.php --retention=365 >> logs/backup.log 2>&1
```

**Note**: The provision script outputs ready-to-copy cron snippets with correct paths.

#### Retention Policy

The `--retention=N` flag keeps only the N most recent backups:

```bash
php database/backup.php --retention=30
```

- Deletes backups older than 30 days
- Keeps `.sql`, `.sql.gz`, and `.md5` files in sync
- Logs deletions to `storage/backups/backup.log`

#### Backup Logging

Monitor backup operations:

```bash
# View recent backups
tail -f storage/backups/backup.log

# Check for errors
grep -i error storage/backups/backup.log

# Disk usage
du -sh storage/backups/
```

### Backup Rotation Strategy

Recommended strategy for production:

| Backup Type       | Schedule      | Retention | Purpose                  |
|-------------------|---------------|-----------|--------------------------|
| **Daily Full**    | 2 AM daily    | 30 days   | Short-term recovery      |
| **Weekly Schema** | 3 AM Sunday   | 12 weeks  | Version control          |
| **Monthly Full**  | 4 AM 1st day  | 12 months | Long-term archival       |

**Estimated Storage**:
- Daily full: ~10 MB × 30 = 300 MB
- Weekly schema: ~1 MB × 12 = 12 MB
- Monthly full: ~10 MB × 12 = 120 MB
- **Total**: ~450 MB

---

## Restore Operations

### Before Restoring

⚠️ **CRITICAL WARNINGS**:

1. **Backup Current State**: Always backup current database before restoring
2. **Verify Backup**: Test backup integrity before restore
3. **Downtime**: Restoration requires application downtime
4. **Credentials**: Ensure backup was created with compatible MySQL version

### Test Backup Integrity

Before restoring, verify backup is valid:

```bash
# Verify checksum
md5sum -c storage/backups/3dprint_20250119_020000.sql.gz.md5

# Test gzip integrity
gunzip -t storage/backups/3dprint_20250119_020000.sql.gz

# Dry-run SQL import
zcat storage/backups/3dprint_20250119_020000.sql.gz | mysql --dry-run -u root -p
```

### Full Restore

#### Step 1: Backup Current Database

```bash
# Create safety backup
php database/backup.php
mv storage/backups/3dprint_*.sql.gz storage/backups/pre-restore-backup.sql.gz
```

#### Step 2: Stop Application

```bash
# Put site in maintenance mode (if applicable)
# Or temporarily disable web server
sudo systemctl stop apache2
```

#### Step 3: Restore from Backup

**From Compressed Backup**:

```bash
# Decompress and restore
zcat storage/backups/3dprint_20250119_020000.sql.gz | \
  mysql -u root -p ch167436_3dprint
```

**From Uncompressed Backup**:

```bash
mysql -u root -p ch167436_3dprint < storage/backups/3dprint_20250119_020000.sql
```

#### Step 4: Verify Restoration

```bash
# Check tables exist
mysql -u root -p ch167436_3dprint -e "SHOW TABLES;"

# Verify record counts
mysql -u root -p ch167436_3dprint -e "
  SELECT 
    'orders' AS table_name, COUNT(*) AS records FROM orders
  UNION ALL
  SELECT 'services', COUNT(*) FROM services
  UNION ALL
  SELECT 'admin_users', COUNT(*) FROM admin_users;
"

# Run schema verification
php database/verify-schema.php
```

#### Step 5: Restart Application

```bash
# Restart web server
sudo systemctl start apache2

# Test application
curl https://your-domain.com/api/test.php
```

### Partial Restore

Restore specific tables without affecting others:

```bash
# Extract specific tables from backup
zcat storage/backups/3dprint_20250119_020000.sql.gz | \
  sed -n '/CREATE TABLE `orders`/,/UNLOCK TABLES/p' | \
  mysql -u root -p ch167436_3dprint
```

### Point-in-Time Recovery

Restore to specific timestamp:

```bash
# List available backups
ls -lh storage/backups/*.sql.gz

# Restore from specific backup
zcat storage/backups/3dprint_20250118_140000.sql.gz | \
  mysql -u root -p ch167436_3dprint
```

### Clone Database

Create copy for testing/development:

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE ch167436_3dprint_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restore backup to test database
zcat storage/backups/3dprint_20250119_020000.sql.gz | \
  mysql -u root -p ch167436_3dprint_test

# Update .env.testing
echo "DB_DATABASE=ch167436_3dprint_test" >> .env.testing
```

---

## Maintenance Tasks

### Regular Health Checks

#### Check Table Status

```bash
mysql -u root -p ch167436_3dprint -e "
  SELECT 
    table_name,
    table_rows,
    ROUND(data_length / 1024 / 1024, 2) AS data_mb,
    ROUND(index_length / 1024 / 1024, 2) AS index_mb
  FROM information_schema.tables
  WHERE table_schema = 'ch167436_3dprint'
  ORDER BY data_length DESC;
"
```

#### Check Disk Usage

```bash
# Database size
mysql -u root -p -e "
  SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
  FROM information_schema.tables
  WHERE table_schema = 'ch167436_3dprint';
"

# Backup directory size
du -sh storage/backups/
```

### Optimize Tables

Run monthly to reclaim space and rebuild indexes:

```bash
mysql -u root -p ch167436_3dprint -e "
  OPTIMIZE TABLE 
    orders, services, portfolio, testimonials, faq,
    content_blocks, forms, form_fields, form_submissions,
    admin_users, admin_sessions, admin_action_logs;
"
```

### Clean Old Data

#### Archive Old Orders

```bash
# Export old orders to archive
php scripts/archive-old-orders.php --older-than=365

# Or manually
mysql -u root -p ch167436_3dprint -e "
  UPDATE orders 
  SET archived_at = NOW() 
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    AND archived_at IS NULL;
"
```

#### Cleanup Audit Logs

```bash
# Via API (requires admin auth)
curl -X DELETE "https://your-domain.com/api/admin/audit-logs.php?older_than=90" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Or via MySQL (minimum 30 days)
mysql -u root -p ch167436_3dprint -e "
  DELETE FROM admin_action_logs 
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
"
```

#### Cleanup Old Sessions

```bash
mysql -u root -p ch167436_3dprint -e "
  DELETE FROM admin_sessions 
  WHERE expires_at < NOW();
"
```

### Database Statistics

```bash
# Run verification with stats
php database/verify-schema.php

# Or query directly
mysql -u root -p ch167436_3dprint -e "
  SELECT 
    (SELECT COUNT(*) FROM orders) AS total_orders,
    (SELECT COUNT(*) FROM orders WHERE status = 'new') AS pending_orders,
    (SELECT COUNT(*) FROM services WHERE active = 1) AS active_services,
    (SELECT COUNT(*) FROM admin_users WHERE status = 'active') AS active_admins,
    (SELECT COUNT(*) FROM form_submissions) AS total_submissions;
"
```

---

## Troubleshooting

### Provisioning Issues

#### Error: "Access denied for user"

**Cause**: Incorrect admin credentials or insufficient privileges.

**Solution**:

```bash
# Verify admin credentials
mysql -u root -p -e "SELECT 1;"

# Check user privileges
mysql -u root -p -e "SHOW GRANTS FOR 'root'@'localhost';"

# Grant required privileges
mysql -u root -p -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;"
```

#### Error: "Database already exists"

**Cause**: Database exists from previous installation.

**Solution**:

```bash
# Option 1: Import into existing database
php scripts/provision-database.php --import-only

# Option 2: Force recreate (⚠️ destroys data)
php scripts/provision-database.php --force --seed
```

#### Error: "Can't create database ... errno: 13"

**Cause**: Insufficient permissions on MySQL data directory.

**Solution**:

```bash
# Check MySQL data directory
sudo ls -la /var/lib/mysql/

# Fix permissions
sudo chown -R mysql:mysql /var/lib/mysql/
sudo chmod 750 /var/lib/mysql/

# Restart MySQL
sudo systemctl restart mysql
```

### Backup Issues

#### Error: "mysqldump: command not found"

**Cause**: MySQL client tools not installed.

**Solution**:

```bash
# Ubuntu/Debian
sudo apt-get install mysql-client

# CentOS/RHEL
sudo yum install mysql

# Verify installation
which mysqldump
```

#### Error: "Couldn't execute 'FLUSH TABLES'"

**Cause**: Insufficient privileges for backup user.

**Solution**:

```bash
# Grant required privileges
mysql -u root -p -e "
  GRANT SELECT, LOCK TABLES, SHOW VIEW ON ch167436_3dprint.* 
  TO 'backup_user'@'localhost';
"
```

#### Backups Growing Too Large

**Cause**: Large data accumulation (orders, logs, submissions).

**Solution**:

```bash
# Archive old orders
php scripts/archive-old-orders.php --older-than=365

# Cleanup audit logs older than 90 days
curl -X DELETE "https://your-domain.com/api/admin/audit-logs.php?older_than=90"

# Use data-only backups with exclusions
php database/backup.php --data-only --exclude-tables=admin_action_logs,admin_login_attempts
```

### Restore Issues

#### Error: "Unknown database"

**Cause**: Database doesn't exist before restore.

**Solution**:

```bash
# Create database first
php scripts/provision-database.php --create-only

# Then restore
zcat storage/backups/backup.sql.gz | mysql -u root -p ch167436_3dprint
```

#### Error: "Table already exists"

**Cause**: Restoring into non-empty database.

**Solution**:

```bash
# Option 1: Drop all tables first
mysql -u root -p ch167436_3dprint -e "
  SET FOREIGN_KEY_CHECKS = 0;
  DROP TABLE IF EXISTS orders, services, portfolio, testimonials, faq, 
    content_blocks, forms, form_fields, form_submissions, form_submission_values,
    settings, settings_audit, admin_users, admin_sessions, admin_login_attempts, 
    admin_action_logs, order_status_history, order_notes;
  SET FOREIGN_KEY_CHECKS = 1;
"

# Then restore
zcat storage/backups/backup.sql.gz | mysql -u root -p ch167436_3dprint

# Option 2: Use --force flag (recreates database)
php scripts/provision-database.php --force
```

#### Checksum Mismatch

**Cause**: Backup file corrupted during transfer or storage.

**Solution**:

```bash
# Verify checksum
md5sum storage/backups/backup.sql.gz
cat storage/backups/backup.sql.gz.md5

# If mismatch, restore from earlier backup
ls -lth storage/backups/*.sql.gz | head -n 5

# Or retrieve from remote backup
scp backup-server:/backups/backup.sql.gz storage/backups/
```

---

## Best Practices

### Security

1. **Protect Backups**
   ```bash
   chmod 600 storage/backups/*.sql*
   chown www-data:www-data storage/backups/
   ```

2. **Encrypt Sensitive Backups**
   ```bash
   # Encrypt backup
   gpg --encrypt --recipient admin@example.com storage/backups/backup.sql.gz
   
   # Decrypt for restore
   gpg --decrypt storage/backups/backup.sql.gz.gpg | \
     mysql -u root -p ch167436_3dprint
   ```

3. **Secure Remote Backups**
   ```bash
   # Sync to remote server
   rsync -avz --delete storage/backups/ backup-server:/backups/3dprint/
   
   # Or use S3
   aws s3 sync storage/backups/ s3://my-bucket/3dprint-backups/
   ```

### Performance

1. **Compress Backups**: Always use `--compress` (default) to save disk space
2. **Schedule Off-Peak**: Run backups during low-traffic hours (2-4 AM)
3. **Use Schema-Only**: For frequent version control, use `--schema-only`
4. **Limit Retention**: Don't keep more backups than necessary

### Monitoring

1. **Monitor Backup Success**
   ```bash
   # Check last backup
   ls -lth storage/backups/*.sql.gz | head -n 1
   
   # Alert if no backup in 24 hours
   find storage/backups/ -name "*.sql.gz" -mtime +1
   ```

2. **Monitor Disk Usage**
   ```bash
   # Set threshold (e.g., 80%)
   df -h | awk '$5 > 80 { print $0 }'
   ```

3. **Log Rotation**
   ```bash
   # Rotate backup logs
   logrotate -f /etc/logrotate.d/3dprint-backups
   ```

---

## Additional Resources

- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Full deployment guide
- **[HOSTING_AUDIT.md](HOSTING_AUDIT.md)** - Hosting requirements validation
- **[database/README.md](../database/README.md)** - Schema documentation
- **[database/VERIFICATION_AND_BACKUP.md](../database/VERIFICATION_AND_BACKUP.md)** - Legacy backup docs

---

## Support

For issues with database operations:

1. Check error logs: `logs/api.log`, `storage/backups/backup.log`
2. Verify schema: `php database/verify-schema.php`
3. Test connection: `php -r "new PDO('mysql:host=localhost;dbname=ch167436_3dprint', 'user', 'pass');"`
4. Review [Troubleshooting](#troubleshooting) section above

---

**Last Updated**: January 19, 2025  
**Version**: 1.0  
**Applies To**: 3D Print Pro v5.0+
