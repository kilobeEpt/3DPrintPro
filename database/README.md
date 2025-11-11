# Database Schema and Seeding System

## Overview

This directory contains the source-of-truth schema and seed data for the 3D Print Pro database. The system is designed for **deterministic, idempotent restoration** of database environments.

## Files

### 1. `schema.sql`
**Purpose:** Creates the complete database structure (7 tables)

**Features:**
- ✅ Idempotent - safe to run multiple times (CREATE TABLE IF NOT EXISTS)
- ✅ Complete indexes for performance
- ✅ Proper constraints and data types
- ✅ Detailed comments explaining each table
- ✅ Optional DROP TABLE statements (commented out) for hard reset

**Tables Created:**
1. **orders** - Customer orders and inquiries (NO 'active' column)
2. **settings** - Application configuration (NO 'active' column)
3. **services** - Service offerings (HAS 'active' column)
4. **portfolio** - Project showcase (HAS 'active' column)
5. **testimonials** - Customer reviews (HAS 'active' column)
6. **faq** - Frequently asked questions (HAS 'active' column)
7. **content_blocks** - Dynamic page content (HAS 'active' column)

**Usage:**
```bash
# MySQL CLI
mysql -u username -p database_name < schema.sql

# Or via PHPMyAdmin
# SQL tab → paste schema.sql → Execute
```

### 2. `seed-data.php`
**Purpose:** Centralized default data for all tables

**Features:**
- ✅ All seed data in one place (single source of truth)
- ✅ Easy to edit and customize
- ✅ PHP array format for flexibility
- ✅ Comprehensive default content

**Data Included:**
- **6 services** - FDM, SLA, modeling, prototyping, post-processing, consultation
- **4 portfolio items** - Architecture, prototype, figurine, industrial part
- **4 testimonials** - Customer reviews with 5-star ratings
- **8 FAQ entries** - Common questions and answers
- **3 content blocks** - Hero, features, about sections
- **12 settings keys** - Site configuration and calculator defaults

**Structure:**
```php
return [
    'services' => [...],
    'portfolio' => [...],
    'testimonials' => [...],
    'faq' => [...],
    'content_blocks' => [...],
    'settings' => [...]
];
```

## Restoration Process

### Quick Start (30 seconds)
```bash
# 1. Create schema
mysql -u user -p dbname < database/schema.sql

# 2. Seed data
curl https://your-site.com/api/init-database.php

# 3. Verify
curl https://your-site.com/api/test.php

# Done! ✅
```

### Step-by-Step Guide

#### Step 1: Create Database Schema
Apply the schema to create all tables:
```bash
mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql
```

**What happens:**
- Creates 7 tables with proper structure
- Adds indexes for performance
- Sets up constraints and defaults
- Inserts initial telegram_chat_id setting
- **Safe to rerun** - won't drop existing data

#### Step 2: Seed Default Data
Visit the initialization endpoint:
```
https://your-site.com/api/init-database.php
```

**What happens:**
- Loads seed data from `seed-data.php`
- Checks for existing records using unique fields
- Inserts missing records
- Updates changed records
- **Idempotent** - safe to rerun, no duplicates

**First run response:**
```json
{
  "status": "OK",
  "mode": "normal",
  "actions": [
    "✅ Services: 6 added, 0 updated",
    "✅ Portfolio: 4 added, 0 updated",
    "✅ Testimonials: 4 added, 0 updated",
    "✅ FAQ: 8 added, 0 updated",
    "✅ Content blocks: 3 added, 0 updated",
    "✅ Settings: 12 new keys added"
  ],
  "production_ready": true
}
```

**Subsequent runs:**
```json
{
  "status": "OK",
  "mode": "normal",
  "actions": [
    "✓ Services already up to date",
    "✓ Portfolio already up to date",
    ...
  ],
  "production_ready": true
}
```

## Hard Reset Mode

**WARNING:** This will delete all content data!

For emergencies when you need to completely rebuild:

```
https://your-site.com/api/init-database.php?reset=RESET_TOKEN
```

**What it does:**
- Deletes ALL data from: services, portfolio, testimonials, faq, content_blocks
- Resets AUTO_INCREMENT counters
- Re-seeds with fresh data from seed-data.php
- **Does NOT touch:** orders (customer data) and settings (keeps existing values)

**Security:**
- Token is defined in `api/init-database.php` (line 21)
- Default: `CHANGE_ME_IN_PRODUCTION_123456`
- **Must be changed** for production use!

## Customizing Seed Data

To customize the default content:

1. **Edit `seed-data.php`:**
   ```php
   'services' => [
       [
           'name' => 'Your Service',
           'slug' => 'your-service',
           'description' => '...',
           // ... more fields
       ]
   ]
   ```

2. **Run the seed script:**
   ```
   https://your-site.com/api/init-database.php
   ```

3. **Changes will be applied:**
   - New services will be added
   - Existing services (matched by slug) will be updated
   - No duplicates created

## Idempotency Guarantees

### Schema (schema.sql)
- ✅ Uses `CREATE TABLE IF NOT EXISTS`
- ✅ Uses `INSERT ... ON DUPLICATE KEY UPDATE` for settings
- ✅ Safe to run multiple times
- ✅ Won't drop or truncate existing data

### Seed Script (api/init-database.php)
- ✅ Checks for existing records before insert
- ✅ Uses unique fields for identification:
  - services: `slug`
  - portfolio: `title`
  - testimonials: `name` + first 50 chars of `text`
  - faq: `question`
  - content_blocks: `block_name`
  - settings: `setting_key`
- ✅ Updates existing records if data changed
- ✅ Never creates duplicates
- ✅ Safe to run multiple times

## Verification

After restoration, verify the database state:

```bash
# Check tables exist
mysql -u user -p dbname -e "SHOW TABLES;"

# Check record counts
curl https://your-site.com/api/test.php

# Check API endpoints
curl https://your-site.com/api/services.php
curl https://your-site.com/api/portfolio.php
curl https://your-site.com/api/testimonials.php
curl https://your-site.com/api/faq.php
curl https://your-site.com/api/content.php
```

**Expected counts after fresh seed:**
- services: 6
- portfolio: 4
- testimonials: 4
- faq: 8
- content_blocks: 3
- settings: 12+

## Troubleshooting

### Schema fails to apply
```bash
# Check MySQL connection
mysql -u user -p dbname -e "SELECT 1;"

# Check database exists
mysql -u user -p -e "SHOW DATABASES LIKE 'dbname';"

# Check user privileges
mysql -u user -p dbname -e "SHOW GRANTS;"
```

### Seed script returns error
```bash
# Check database connection
curl https://your-site.com/api/test.php

# Check schema is applied
mysql -u user -p dbname -e "SHOW TABLES;"

# Check PHP errors
tail -f /path/to/php/error.log
```

### Duplicate records
**This should never happen!** The system is idempotent.

If you encounter duplicates:
1. Check that unique fields are actually unique in seed-data.php
2. Verify api/init-database.php hasn't been modified
3. Consider using hard reset mode to clean up

## Best Practices

1. **Always backup before hard reset:**
   ```bash
   mysqldump -u user -p dbname > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Test schema changes in development first:**
   - Apply schema.sql to dev database
   - Run seed script
   - Verify all endpoints work
   - Then deploy to production

3. **Keep seed-data.php in version control:**
   - All changes tracked
   - Easy to rollback
   - Documentation of default state

4. **Change RESET_TOKEN in production:**
   ```php
   // In api/init-database.php line 21:
   define('RESET_TOKEN', 'your-secure-random-string-here');
   ```

5. **Monitor seed script results:**
   - Check `production_ready` flag
   - Verify record counts match expectations
   - Test API endpoints after seeding

## Version History

### v2.0 (January 2025) - Current
- ✅ Idempotent schema and seed scripts
- ✅ Centralized seed-data.php
- ✅ Hard reset mode with token protection
- ✅ Detailed reporting and verification
- ✅ Comprehensive documentation

### v1.0 (Initial)
- Basic schema.sql
- Manual data insertion
- No idempotency guarantees

## Related Documentation

- `../DATABASE_ARCHITECTURE.md` - Complete database and API documentation
- `../DATABASE_FIX_INSTRUCTIONS.md` - Step-by-step restoration guide
- `../api/init-database.php` - Seed script implementation
- `../api/db.php` - Database class with CRUD methods

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review DATABASE_FIX_INSTRUCTIONS.md
3. Examine PHP error logs
4. Verify MySQL privileges
5. Test with api/test.php diagnostic endpoint
