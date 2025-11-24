# 🔍 Database Audit Tool Documentation

## Overview

The Database Audit Tool is a comprehensive diagnostic utility designed to validate MySQL database connectivity, schema integrity, and configuration for the 3D Print Pro application.

**Created:** January 2025  
**Purpose:** Diagnose database outages and schema drift  
**Location:** `scripts/db_audit.php`

---

## Features

### ✅ Connection Validation
- Tests PDO connection to MySQL server
- Identifies common connection errors with actionable messages
- Validates database credentials
- Reports MySQL version

### ✅ Privilege Checking
- Verifies user has required privileges (SELECT, INSERT, UPDATE, DELETE)
- Checks for optional CREATE privilege
- Reports granted and missing privileges

### ✅ Table Enumeration
- Validates all 7 expected tables exist
- Reports missing tables
- Identifies unexpected/extra tables
- Shows record counts per table

### ✅ Schema Validation
- Compares actual table structure to `database/schema.sql`
- Validates column names and count
- Validates indexes and keys
- Detects schema drift
- Reports specific mismatches

### ✅ Security
- Sanitizes credentials in HTTP output
- No sensitive data exposed via web interface
- CLI mode shows full details for administrators

### ✅ Multiple Output Formats
- Human-readable text output
- Structured JSON output
- Exit codes for automation

---

## Usage

### Command Line (Recommended for Production Issues)

```bash
# Standard output (human-readable)
php scripts/db_audit.php

# JSON output (for parsing/logging)
php scripts/db_audit.php --json

# Save to file
php scripts/db_audit.php > /var/log/db-audit-$(date +%Y%m%d-%H%M%S).log

# Check exit code
php scripts/db_audit.php && echo "Success" || echo "Failed"
```

### HTTP/Browser (Quick Check)

```
# Full audit via test.php
https://your-domain.com/api/test.php?audit=full

# Direct access (text format)
https://your-domain.com/scripts/db_audit.php

# JSON format
https://your-domain.com/scripts/db_audit.php?format=json
```

---

## Output Examples

### Success Output

```
========================================
DATABASE AUDIT REPORT
========================================
Timestamp: 2025-01-15 14:30:00

CONNECTION:
  Status: ✅ Connected
  Host: localhost
  Database: ch167436_3dprint
  User: ch167436_3dprint
  MySQL Version: 8.0.32

PRIVILEGES:
  Status: ✅ OK
  Granted: SELECT, INSERT, UPDATE, DELETE

TABLES:
  Expected: 7
  Found: 7
  Status: ✅ OK

SCHEMA VALIDATION:
  Status: ✅ OK

  Table Details:
    ✅ orders: 17 columns, 7 indexes, 42 records
    ✅ settings: 4 columns, 3 indexes, 5 records
    ✅ services: 13 columns, 6 indexes, 6 records
    ✅ portfolio: 10 columns, 4 indexes, 4 records
    ✅ testimonials: 11 columns, 5 indexes, 4 records
    ✅ faq: 7 columns, 3 indexes, 6 records
    ✅ content_blocks: 10 columns, 5 indexes, 3 records

========================================
SUMMARY: ✅ All checks passed successfully. Database is fully operational.
========================================
```

### Failure Output (Example)

```
========================================
DATABASE AUDIT REPORT
========================================
Timestamp: 2025-01-15 14:32:00

CONNECTION:
  Status: ❌ Failed
  Error: Access denied for user 'ch167436_3dprint'@'localhost'

ERRORS:
  ❌ Database connection failed: Access denied for user 'ch167436_3dprint'@'localhost' - Check DB_USER and DB_PASS in config.php

========================================
SUMMARY: ❌ Database audit failed with 1 error(s) and 0 warning(s).
========================================
```

---

## Expected Tables Schema

The audit tool validates against these 7 tables:

| Table | Purpose | Key Columns | Has 'active' |
|-------|---------|-------------|--------------|
| **orders** | Customer orders/requests | order_number, name, phone, email | ❌ No |
| **settings** | Application settings | setting_key, setting_value | ❌ No |
| **services** | Service offerings | name, slug, description, price | ✅ Yes |
| **portfolio** | Portfolio items | title, image_url, category | ✅ Yes |
| **testimonials** | Customer reviews | name, text, rating, approved | ✅ Yes |
| **faq** | FAQ entries | question, answer | ✅ Yes |
| **content_blocks** | Content management | block_name, title, content | ✅ Yes |

**Total Expected:** 7 tables, 75 columns, 37 indexes

---

## Common Issues & Troubleshooting

### Issue 1: Access Denied

**Error:** `Access denied for user 'username'@'localhost'`

**Cause:** Wrong database credentials

**Solution:**
1. Check `api/config.php`:
   ```php
   define('DB_USER', 'correct_username');
   define('DB_PASS', 'correct_password');
   ```
2. Verify credentials work:
   ```bash
   mysql -u username -p
   ```
3. Re-run audit: `php scripts/db_audit.php`

---

### Issue 2: Unknown Database

**Error:** `Unknown database 'ch167436_3dprint'`

**Cause:** Database doesn't exist

**Solution:**
1. Create database:
   ```sql
   CREATE DATABASE ch167436_3dprint 
   CHARACTER SET utf8mb4 
   COLLATE utf8mb4_unicode_ci;
   ```
2. Import schema:
   ```bash
   mysql -u username -p ch167436_3dprint < database/schema.sql
   ```
3. Re-run audit

---

### Issue 3: Missing Tables

**Error:** `Missing tables: orders, services, portfolio`

**Cause:** Schema not imported

**Solution:**
1. Via MySQL CLI:
   ```bash
   mysql -u username -p database_name < database/schema.sql
   ```
2. Via PHPMyAdmin:
   - Select database
   - Click "Import"
   - Upload `database/schema.sql`
3. Re-run audit

---

### Issue 4: Schema Drift

**Error:** `Missing columns: features, updated_at`

**Cause:** Schema outdated or manually modified

**Solution:**
1. **BACKUP DATA FIRST!**
   ```bash
   mysqldump -u username -p database > backup.sql
   ```
2. Compare schema:
   ```bash
   mysql -u username -p -e "DESCRIBE table_name"
   ```
3. Apply missing columns:
   ```sql
   ALTER TABLE services ADD COLUMN features JSON AFTER description;
   ```
4. Or re-import schema (⚠️ may lose data)

---

### Issue 5: Connection Refused

**Error:** `Connection refused`

**Cause:** MySQL server not running or wrong host

**Solution:**
1. Check MySQL status:
   ```bash
   systemctl status mysql
   # or
   service mysql status
   ```
2. Start MySQL:
   ```bash
   systemctl start mysql
   ```
3. Verify DB_HOST in `config.php`:
   ```php
   define('DB_HOST', 'localhost'); // or 127.0.0.1
   ```

---

## Integration & Automation

### Cron Job (Daily Health Check)

```bash
# Add to crontab
0 2 * * * cd /var/www/html && php scripts/db_audit.php --json >> /var/log/db-audit.log 2>&1
```

### Shell Script Integration

```bash
#!/bin/bash
# Pre-deployment health check

cd /var/www/html
if php scripts/db_audit.php --json > /tmp/audit.json; then
    echo "✅ Database health check passed"
    # Continue deployment
else
    echo "❌ Database health check failed"
    cat /tmp/audit.json
    exit 1
fi
```

### Monitoring (Nagios, Zabbix, etc.)

```bash
#!/bin/bash
# Nagios plugin

cd /var/www/html
OUTPUT=$(php scripts/db_audit.php --json)
EXIT_CODE=$?

if [ $EXIT_CODE -eq 0 ]; then
    echo "OK: Database is healthy"
    exit 0
else
    echo "CRITICAL: Database issues detected - $OUTPUT"
    exit 2
fi
```

---

## Exit Codes

| Code | Meaning | Action |
|------|---------|--------|
| `0` | Success - All checks passed | ✅ No action needed |
| `1` | Failure - One or more checks failed | ❌ Investigate errors |

---

## Security Notes

### CLI Mode
- Shows full connection details
- Displays actual host, database, user
- Only accessible to server administrators

### HTTP Mode
- Sanitizes credentials automatically
- Hides DB_USER, DB_HOST, DB_NAME
- Shows `***` instead of sensitive values
- Safe to run from web interface

**Example HTTP Output:**
```json
{
  "connection": {
    "status": "connected",
    "host": "***",
    "database": "***"
  }
}
```

---

## Files Modified

This audit tool required changes to:

1. **NEW:** `scripts/db_audit.php` - Main audit script (506 lines)
2. **UPDATED:** `api/test.php` - Added `?audit=full` parameter
3. **UPDATED:** `README.md` - Added comprehensive diagnostics section
4. **UPDATED:** `START_HERE.md` - Updated troubleshooting guide
5. **NEW:** `scripts/test_audit.sh` - Test script for validation

---

## Related Documentation

- [README.md](./README.md) - Main documentation with audit usage
- [START_HERE.md](./START_HERE.md) - Quick start with troubleshooting
- [DATABASE_ARCHITECTURE.md](./DATABASE_ARCHITECTURE.md) - Database schema details
- [DATABASE_FIX_INSTRUCTIONS.md](./DATABASE_FIX_INSTRUCTIONS.md) - Database setup guide

---

## Support

If the audit tool reports issues you can't resolve:

1. **Check the output carefully** - errors include actionable solutions
2. **Review documentation** - See README.md and START_HERE.md
3. **Run in verbose mode** - Use `--json` for detailed output
4. **Check MySQL logs** - `/var/log/mysql/error.log`
5. **Verify network** - Can server reach MySQL?

---

## Version History

### v1.0 - January 2025
- Initial release
- Connection validation
- Privilege checking
- Table enumeration
- Schema validation
- CLI and HTTP modes
- JSON and human-readable output
- Security (credential sanitization)
- Integration with api/test.php
- Comprehensive documentation

---

**Questions?** See the main [README.md](./README.md) or run `php scripts/db_audit.php` to diagnose issues.
