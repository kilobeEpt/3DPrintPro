# Database Verification and Backup Guide

## Overview

This directory contains tools for verifying database schema correctness and creating automated backups.

## Files

### 1. `verify-schema.php`
**Purpose:** Validates that all required tables and columns exist in the database

**Features:**
- ✅ Verifies all 7 tables exist
- ✅ Checks each table has expected columns
- ✅ Validates 'active' column presence (or absence)
- ✅ Counts and reports on indexes
- ✅ Detects missing or extra columns
- ✅ Works from CLI or HTTP
- ✅ Returns detailed JSON report

**CLI Usage:**
```bash
php database/verify-schema.php
```

**HTTP Usage:**
```
https://your-site.com/database/verify-schema.php
```

**Expected Output (Success):**
```json
{
  "status": "OK",
  "timestamp": "2025-01-12 10:30:00",
  "database": "ch167436_3dprint",
  "host": "localhost",
  "tables_expected": 7,
  "tables_found": 7,
  "tables_verified": 7,
  "production_ready": true,
  "summary": "7 of 7 tables verified successfully",
  "verification": {
    "orders": {
      "status": "OK",
      "exists": true,
      "columns_expected": 17,
      "columns_found": 17,
      "has_active_column": false
    },
    ...
  },
  "errors": [],
  "warnings": []
}
```

**Exit Codes (CLI):**
- `0` - All tables verified successfully
- `1` - Verification failed or connection error

### 2. `backup.php`
**Purpose:** Creates timestamped backups of schema and/or data

**Features:**
- ✅ Full database backup (schema + data)
- ✅ Schema-only backup option
- ✅ Data-only backup option
- ✅ Specific tables backup
- ✅ Automatic compression (.gz)
- ✅ Timestamped filenames
- ✅ Works from CLI or HTTP (with token)
- ✅ Returns detailed backup report

**CLI Usage:**
```bash
# Full backup (schema + data)
php database/backup.php

# Schema only
php database/backup.php --schema-only

# Data only
php database/backup.php --data-only

# Specific tables
php database/backup.php --tables=orders,settings
```

**HTTP Usage:**
```
https://your-site.com/database/backup.php?token=YOUR_TOKEN
https://your-site.com/database/backup.php?token=YOUR_TOKEN&schema_only=1
https://your-site.com/database/backup.php?token=YOUR_TOKEN&tables=orders,settings
```

**Security:**
- Change `BACKUP_TOKEN` in `backup.php` line 36 for production
- Token required for HTTP access
- CLI access doesn't require token

**Output Files:**
Backups are saved to `database/backups/` with format:
- `ch167436_3dprint_backup_YYYY-MM-DD_HH-MM-SS.sql` - SQL dump
- `ch167436_3dprint_backup_YYYY-MM-DD_HH-MM-SS.sql.gz` - Compressed version

**Expected Output:**
```json
{
  "status": "OK",
  "timestamp": "2025-01-12 10:30:00",
  "database": "ch167436_3dprint",
  "host": "localhost",
  "files_created": [
    {
      "filename": "ch167436_3dprint_backup_2025-01-12_10-30-00.sql",
      "size": 245760,
      "size_formatted": "240.00 KB",
      "type": "full"
    },
    {
      "filename": "ch167436_3dprint_backup_2025-01-12_10-30-00.sql.gz",
      "size": 45820,
      "size_formatted": "44.75 KB",
      "compression_ratio": "81.3%"
    }
  ],
  "summary": "Backup completed successfully. 2 file(s) created."
}
```

## Production Deployment Workflow

### Initial Setup

1. **Configure database credentials:**
   ```bash
   cp api/config.example.php api/config.php
   nano api/config.php  # Edit with actual credentials
   chmod 600 api/config.php  # Secure permissions
   ```

2. **Apply database schema:**
   ```bash
   mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql
   ```

3. **Verify schema was applied:**
   ```bash
   php database/verify-schema.php
   ```
   
   Or via HTTP:
   ```bash
   curl https://ch167436.tw1.ru/database/verify-schema.php
   ```

4. **Seed initial data:**
   ```bash
   curl https://ch167436.tw1.ru/api/init-database.php
   ```

5. **Create initial backup:**
   ```bash
   php database/backup.php
   ```

### Regular Verification

Run verification after any schema changes:
```bash
php database/verify-schema.php
```

**Check for:**
- `"status": "OK"`
- `"production_ready": true`
- `"tables_verified": 7`
- No errors in `"errors": []`

### Backup Schedule

**Recommended backup strategy:**

1. **Daily automated backups** (via cron):
   ```bash
   # Add to crontab: crontab -e
   0 2 * * * cd /path/to/project && php database/backup.php >> logs/backup.log 2>&1
   ```

2. **Before any schema changes:**
   ```bash
   php database/backup.php
   ```

3. **Before production deployments:**
   ```bash
   php database/backup.php --data-only
   ```

4. **Weekly schema snapshots:**
   ```bash
   php database/backup.php --schema-only
   ```

### Backup Management

**Retention policy:**
```bash
# Keep last 7 days of daily backups
find database/backups/ -name "*.sql.gz" -mtime +7 -delete

# Keep monthly backups forever (manual archiving)
# Move first-of-month backups to separate directory
```

**Restore from backup:**
```bash
# Decompress if needed
gunzip database/backups/ch167436_3dprint_backup_2025-01-12_10-30-00.sql.gz

# Restore
mysql -u ch167436_3dprint -p ch167436_3dprint < database/backups/ch167436_3dprint_backup_2025-01-12_10-30-00.sql
```

## Troubleshooting

### Verification Fails

**Problem:** `verify-schema.php` returns errors

**Solutions:**
1. Check database credentials:
   ```bash
   mysql -u ch167436_3dprint -p ch167436_3dprint -e "SELECT 1;"
   ```

2. Verify schema was applied:
   ```bash
   mysql -u ch167436_3dprint -p ch167436_3dprint -e "SHOW TABLES;"
   ```

3. Re-apply schema if needed:
   ```bash
   mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql
   ```

4. Check specific error messages in JSON output

### Backup Fails

**Problem:** `backup.php` returns errors

**Solutions:**
1. Check `mysqldump` is installed:
   ```bash
   which mysqldump
   mysqldump --version
   ```

2. Verify database access:
   ```bash
   mysql -u ch167436_3dprint -p ch167436_3dprint -e "SHOW TABLES;"
   ```

3. Check write permissions:
   ```bash
   ls -la database/backups/
   chmod 755 database/backups/
   ```

4. Test manually:
   ```bash
   mysqldump -u ch167436_3dprint -p ch167436_3dprint > test.sql
   ```

### Missing Tables

**Problem:** Verification reports missing tables

**Solution:** Apply schema:
```bash
mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql
php database/verify-schema.php  # Verify again
```

### Wrong Schema

**Problem:** Tables have wrong columns or structure

**Solution:** Hard reset and rebuild:
```bash
# 1. Backup current data
php database/backup.php

# 2. Uncomment DROP TABLE lines in schema.sql
nano database/schema.sql  # Uncomment lines 29-35

# 3. Re-apply schema
mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql

# 4. Re-comment DROP TABLE lines
nano database/schema.sql  # Re-comment lines 29-35

# 5. Re-seed data
curl https://ch167436.tw1.ru/api/init-database.php

# 6. Verify
php database/verify-schema.php
```

## Integration with CI/CD

### Pre-deployment Checks

Add to deployment script:
```bash
#!/bin/bash
set -e

echo "Creating backup..."
php database/backup.php

echo "Verifying schema..."
php database/verify-schema.php || {
    echo "Schema verification failed!"
    exit 1
}

echo "Testing API..."
curl -f https://ch167436.tw1.ru/api/test.php || {
    echo "API test failed!"
    exit 1
}

echo "Deployment checks passed ✅"
```

### Post-deployment Verification

```bash
#!/bin/bash
set -e

echo "Verifying deployed schema..."
curl -f https://ch167436.tw1.ru/database/verify-schema.php | jq -r '.status'

echo "Creating post-deployment backup..."
curl -f "https://ch167436.tw1.ru/database/backup.php?token=YOUR_TOKEN"

echo "Post-deployment verification complete ✅"
```

## Security Considerations

1. **Protect backup files:**
   - Backups contain sensitive data
   - Keep `database/backups/` in `.gitignore` ✅
   - Set restrictive permissions: `chmod 600 database/backups/*.sql`

2. **Secure HTTP access:**
   - Change `BACKUP_TOKEN` in production
   - Use HTTPS only for HTTP access
   - Consider IP whitelisting in `.htaccess`

3. **Protect config files:**
   - `api/config.php` should never be in git ✅
   - Set file permissions: `chmod 600 api/config.php`
   - Use environment variables for extra security

4. **Audit access:**
   - Log backup and verification attempts
   - Monitor for unauthorized access
   - Regular security reviews

## Related Documentation

- `README.md` - Database schema and seeding system
- `schema.sql` - Complete database schema
- `seed-data.php` - Default seed data
- `../DATABASE_ARCHITECTURE.md` - Full system architecture
- `../DATABASE_FIX_INSTRUCTIONS.md` - Recovery procedures
- `../api/init-database.php` - Database initialization script
- `../api/test.php` - API diagnostic endpoint

## Support

For issues:
1. Check this documentation
2. Review error messages in JSON output
3. Test database connectivity
4. Verify file permissions
5. Check PHP/MySQL versions
6. Review system logs

## Version History

### v1.0 (January 2025) - Current
- ✅ Schema verification script
- ✅ Automated backup script
- ✅ CLI and HTTP support
- ✅ Comprehensive documentation
- ✅ Production-ready tooling
