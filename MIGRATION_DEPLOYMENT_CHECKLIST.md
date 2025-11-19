# Database Migration System - Deployment Checklist

## 📋 Pre-Deployment Verification

### ✅ System Requirements Check

- [ ] PHP 7.4+ installed
- [ ] MySQL 5.7+ or MariaDB 10.2+
- [ ] PDO MySQL extension enabled (`php -m | grep pdo_mysql`)
- [ ] Composer dependencies installed (`./composer install`)
- [ ] Write permissions on `database/` directory

### ✅ File Integrity Check

Run the validation script:

```bash
php scripts/validate-migrations.php
```

**Expected Output:** "✅ All validation checks passed!"

### ✅ Migration Files Present (19 total)

```bash
ls -1 database/migrations/ | wc -l
# Should output: 19
```

**Required migrations:**
- ✅ 2025_01_15_000001_create_users_table.php
- ✅ 2025_01_15_000002_create_customers_table.php
- ✅ 2025_01_15_000003_create_categories_table.php
- ✅ 2025_01_15_000004_create_materials_table.php
- ✅ 2025_01_15_000005_create_order_types_table.php
- ✅ 2025_01_15_000006_create_order_statuses_table.php
- ✅ 2025_01_15_000007_create_services_table.php
- ✅ 2025_01_15_000008_create_service_features_table.php
- ✅ 2025_01_15_000009_create_tags_table.php
- ✅ 2025_01_15_000010_create_portfolio_table.php
- ✅ 2025_01_15_000011_create_portfolio_tags_table.php
- ✅ 2025_01_15_000012_create_orders_table.php
- ✅ 2025_01_15_000013_create_order_status_history_table.php
- ✅ 2025_01_15_000014_create_testimonials_table.php
- ✅ 2025_01_15_000015_create_faq_table.php
- ✅ 2025_01_15_000016_create_content_blocks_table.php
- ✅ 2025_01_15_000017_create_content_revisions_table.php
- ✅ 2025_01_15_000018_create_settings_table.php
- ✅ 2025_01_15_000019_create_audit_log_table.php

### ✅ Seeder Files Present (7 total)

```bash
ls -1 database/seeders/ | wc -l
# Should output: 7
```

**Required seeders:**
- ✅ DatabaseSeeder.php
- ✅ OrderTypesSeeder.php
- ✅ OrderStatusesSeeder.php
- ✅ CategoriesSeeder.php
- ✅ MaterialsSeeder.php
- ✅ DefaultUserSeeder.php
- ✅ SettingsSeeder.php

### ✅ Scripts Executable

```bash
ls -l scripts/migrate scripts/seed
# Both should show -rwxr-xr-x permissions
```

If not executable:
```bash
chmod +x scripts/migrate scripts/seed
```

---

## 🚀 Fresh Installation (New Database)

### Step 1: Configure Database

**Option A: Using .env file (Recommended)**

```bash
cp .env.example .env
nano .env  # or vim, or any editor
```

Update these values:
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

**Option B: Using api/config.php (Legacy)**

```bash
cp api/config.example.php api/config.php
nano api/config.php
```

Update:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');
```

### Step 2: Test Database Connection

```bash
php -r "require 'vendor/autoload.php'; require 'bootstrap/eloquent.php'; echo 'Connection OK';"
```

**Expected Output:** `Connection OK`

If error, check:
- Database credentials
- Database server is running
- User has CREATE/DROP/ALTER privileges

### Step 3: Check Migration Status

```bash
php scripts/migrate status
```

**Expected Output:** All migrations listed as "Pending"

### Step 4: Run Migrations

```bash
php scripts/migrate up
```

**Expected Output:**
```
Running 19 migration(s) in batch 1...

  ✓ Migrated: 2025_01_15_000001_create_users_table (X.XXms)
  ✓ Migrated: 2025_01_15_000002_create_customers_table (X.XXms)
  ...
  ✓ Migrated: 2025_01_15_000019_create_audit_log_table (X.XXms)

✓ All migrations completed successfully!
```

### Step 5: Run Seeders

```bash
php scripts/seed
```

**Expected Output:**
```
╔══════════════════════════════════════════════════════════════╗
║         3D PrintPro Database Seeder v3.0                     ║
╚══════════════════════════════════════════════════════════════╝

📋 Seeding lookup tables...
✓ Seeded: OrderTypesSeeder (X.XXms)
✓ Seeded: OrderStatusesSeeder (X.XXms)
✓ Seeded: CategoriesSeeder (X.XXms)
✓ Seeded: MaterialsSeeder (X.XXms)

📋 Seeding system data...
✓ Seeded: DefaultUserSeeder (X.XXms)
  ⚠ Default admin user created:
     Username: admin
     Password: admin123
     ⚠ CHANGE PASSWORD IMMEDIATELY IN PRODUCTION!
✓ Seeded: SettingsSeeder (X.XXms)

✅ Database seeding completed successfully!

Next steps:
  1. Change default admin password (admin/admin123)
  2. Configure Telegram settings in admin panel
  3. Add services, portfolio, and content via admin panel
```

### Step 6: Verify Installation

```bash
php scripts/migrate status
```

**Expected Output:** All migrations listed as "Ran" with "Batch: 1"

---

## 🔄 Existing Database Upgrade (CAUTION!)

### ⚠️ IMPORTANT: Backup First!

```bash
# MySQL/MariaDB backup
mysqldump -u USERNAME -p DATABASE_NAME > backup_before_migration_$(date +%Y%m%d_%H%M%S).sql

# Or use the built-in backup script
php database/backup.php
```

### Option 1: Fresh Start (Destroys Existing Data)

```bash
# ⚠️ THIS WILL DROP ALL TABLES!
php scripts/migrate fresh

# Then seed
php scripts/seed
```

### Option 2: Selective Migration (Manual)

**Not recommended** - requires manual data mapping from v2.0 schema to v3.0.

1. Export existing data
2. Run `migrate fresh`
3. Transform and import data manually
4. Update application code

---

## 🧪 Testing & Verification

### Test 1: Migration Status

```bash
php scripts/migrate status
```

**Success Criteria:** All 19 migrations show "Ran" status

### Test 2: Verify Schema

```bash
# Count tables
mysql -u USER -p -D DATABASE -e "SHOW TABLES;" | wc -l
# Should output: 20 (19 app tables + 1 migrations table)

# Check specific table
mysql -u USER -p -D DATABASE -e "DESCRIBE users;"
```

**Success Criteria:** 
- 20 tables total
- `users` table has expected structure

### Test 3: Verify Seeded Data

```bash
mysql -u USER -p -D DATABASE << EOF
SELECT COUNT(*) as order_types FROM order_types;
SELECT COUNT(*) as order_statuses FROM order_statuses;
SELECT COUNT(*) as categories FROM categories;
SELECT COUNT(*) as materials FROM materials;
SELECT COUNT(*) as users FROM users;
SELECT COUNT(*) as settings FROM settings;
EOF
```

**Expected Counts:**
- order_types: 4
- order_statuses: 6
- categories: 12
- materials: 6
- users: 1
- settings: 19

### Test 4: Rollback Test (Development Only!)

```bash
# Rollback last batch
php scripts/migrate down

# Check status
php scripts/migrate status

# Re-run migrations
php scripts/migrate up
```

**Success Criteria:** 
- Rollback completes without errors
- Re-migration creates same schema

---

## 🔐 Security Post-Deployment

### Mandatory Security Steps

1. **Change Default Admin Password**
   ```
   Login: http://your-domain.com/admin/
   Username: admin
   Password: admin123
   
   Go to: Settings → Change Password
   ```

2. **Restrict File Permissions**
   ```bash
   chmod 600 .env
   chmod 600 api/config.php
   ```

3. **Verify .gitignore**
   ```bash
   # These files MUST be in .gitignore
   grep -E "\.env$|config\.php" .gitignore
   ```

4. **Configure Telegram (Optional)**
   - Get bot token from @BotFather
   - Update `TELEGRAM_BOT_TOKEN` in .env or settings
   - Get chat ID and update `TELEGRAM_CHAT_ID`

---

## 📊 Production Deployment Checklist

### Before Deployment

- [ ] All tests pass in staging environment
- [ ] Database backup created and verified
- [ ] Database credentials secured in .env or config.php
- [ ] .env and config.php NOT in git repository
- [ ] Rollback plan documented
- [ ] Maintenance window scheduled

### During Deployment

- [ ] Put application in maintenance mode
- [ ] Create final backup
- [ ] Run `php scripts/migrate up`
- [ ] Run `php scripts/seed`
- [ ] Verify migration status
- [ ] Test critical functionality
- [ ] Change default admin password
- [ ] Configure application settings

### After Deployment

- [ ] Remove maintenance mode
- [ ] Monitor error logs
- [ ] Test all major features
- [ ] Verify Telegram notifications (if configured)
- [ ] Document any issues
- [ ] Archive deployment logs

---

## 🆘 Troubleshooting

### Issue: "could not find driver"

**Solution:** Install PDO MySQL extension
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql

# CentOS/RHEL
sudo yum install php-mysql

# Verify
php -m | grep pdo_mysql
```

### Issue: "Access denied for user"

**Solutions:**
1. Check credentials in .env or config.php
2. Verify database user exists and has privileges
3. Test connection manually:
   ```bash
   mysql -u USERNAME -p -h HOST DATABASE
   ```

### Issue: "Table already exists"

**Solutions:**
1. Check migration status: `php scripts/migrate status`
2. If partially migrated, rollback: `php scripts/migrate down`
3. For fresh start: `php scripts/migrate fresh`

### Issue: Foreign key constraint error

**Solutions:**
1. Verify parent tables exist before creating child tables
2. Check data types match (e.g., `increments` → `unsignedInteger`)
3. Ensure referenced records exist before creating relationships

### Issue: Seeder "already exist" message

**Expected Behavior:** Seeders are idempotent - safe to run multiple times
- If data exists, seeders skip creation
- This is normal and not an error

### Issue: Migration stuck or timeout

**Solutions:**
1. Increase PHP timeout: `php -d max_execution_time=300 scripts/migrate up`
2. Run migrations one at a time (manually require and run)
3. Check MySQL connection timeout settings

---

## 📚 Additional Resources

### Documentation Files

- `database/README_MIGRATIONS.md` - Quick start guide
- `database/MIGRATIONS.md` - Complete reference
- `database/INTEGRATION_GUIDE.md` - Integration with existing system
- `MIGRATION_SYSTEM_SUMMARY.md` - Implementation summary

### Helper Scripts

- `scripts/migrate` - Migration runner
- `scripts/seed` - Seeder runner
- `scripts/validate-migrations.php` - Validation script

### CLI Commands

```bash
# Migration commands
php scripts/migrate status     # Show status
php scripts/migrate up         # Run pending
php scripts/migrate down       # Rollback last batch
php scripts/migrate fresh      # Drop all and re-run
php scripts/migrate refresh    # Rollback all and re-run
php scripts/migrate reset      # Rollback all

# Seeder commands
php scripts/seed               # Run all seeders
php scripts/seed ClassName     # Run specific seeder
```

---

## ✅ Acceptance Criteria Verification

### Criterion 1: Migration Runner Produces Exact Schema

**Test:**
```bash
php scripts/migrate fresh
php database/verify-schema.php  # If exists
```

**Success:** 19 tables created with all FKs, indexes, and constraints

### Criterion 2: Seed Process is Idempotent

**Test:**
```bash
php scripts/seed
php scripts/seed  # Run twice
```

**Success:** Second run completes without errors or duplicates

### Criterion 3: Rollback Drops Schema Cleanly

**Test:**
```bash
php scripts/migrate up
php scripts/migrate reset
mysql -u USER -p -D DATABASE -e "SHOW TABLES;"
```

**Success:** Only `migrations` table remains (or zero tables)

---

## 📝 Notes

- **Migration ordering is critical** - DO NOT rename or reorder files
- **Seeders are optional** - migrations work without seeding
- **Rollback is destructive** - always backup before rolling back in production
- **Foreign keys enforce integrity** - ensure parent records exist before children
- **Soft deletes preserve history** - use `deleted_at IS NULL` in queries

---

**Version:** 3.0  
**Last Updated:** January 2025  
**Status:** ✅ Production Ready
