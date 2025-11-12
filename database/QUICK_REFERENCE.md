# Database Quick Reference

## Quick Commands

### Schema Management
```bash
# Apply schema
mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql

# Verify schema
php database/verify-schema.php

# Check via HTTP
curl https://ch167436.tw1.ru/database/verify-schema.php
```

### Data Management
```bash
# Seed database
curl https://ch167436.tw1.ru/api/init-database.php

# Hard reset (with token)
curl "https://ch167436.tw1.ru/api/init-database.php?reset=RESET_TOKEN"

# Test API
curl https://ch167436.tw1.ru/api/test.php
```

### Backups
```bash
# Full backup
php database/backup.php

# Schema only
php database/backup.php --schema-only

# Data only
php database/backup.php --data-only

# Specific tables
php database/backup.php --tables=orders,settings

# Via HTTP (with token)
curl "https://ch167436.tw1.ru/database/backup.php?token=BACKUP_TOKEN"
```

### Restore
```bash
# From backup
mysql -u ch167436_3dprint -p ch167436_3dprint < database/backups/BACKUP_FILE.sql

# From compressed backup
gunzip < database/backups/BACKUP_FILE.sql.gz | mysql -u ch167436_3dprint -p ch167436_3dprint
```

## Database Schema Summary

### 7 Tables

| Table | Description | Active Column | Primary Keys/Unique |
|-------|-------------|---------------|---------------------|
| `orders` | Customer orders | ❌ No | order_number (UNIQUE) |
| `settings` | App config | ❌ No | setting_key (UNIQUE) |
| `services` | Services offered | ✅ Yes | slug (UNIQUE) |
| `portfolio` | Project showcase | ✅ Yes | id |
| `testimonials` | Reviews | ✅ Yes | id |
| `faq` | Questions | ✅ Yes | id |
| `content_blocks` | Page content | ✅ Yes | block_name (UNIQUE) |

### Connection Details

**Production:**
- Host: `localhost` (or `ch167436.tw1.ru` remote)
- Database: `ch167436_3dprint`
- User: `ch167436_3dprint`
- Charset: `utf8mb4`

## File Structure

```
database/
├── schema.sql                    # Complete schema (idempotent)
├── seed-data.php                 # Default data (source of truth)
├── verify-schema.php             # Schema verification tool
├── backup.php                    # Backup automation tool
├── backups/                      # Backup storage (git-ignored)
│   └── *.sql, *.sql.gz          # Timestamped backups
├── README.md                     # Complete documentation
├── VERIFICATION_AND_BACKUP.md   # Verification & backup guide
└── QUICK_REFERENCE.md           # This file
```

## API Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/test.php` | GET | Database diagnostics |
| `/api/init-database.php` | GET | Seed database |
| `/api/orders.php` | GET/POST/PUT/DELETE | Orders CRUD |
| `/api/services.php` | GET/POST/PUT/DELETE | Services CRUD |
| `/api/portfolio.php` | GET/POST/PUT/DELETE | Portfolio CRUD |
| `/api/testimonials.php` | GET/POST/PUT/DELETE | Testimonials CRUD |
| `/api/faq.php` | GET/POST/PUT/DELETE | FAQ CRUD |
| `/api/content.php` | GET/POST/PUT/DELETE | Content blocks CRUD |
| `/api/settings.php` | GET/POST/PUT/DELETE | Settings CRUD |

## Common Workflows

### Fresh Installation
```bash
# 1. Configure
cp api/config.example.php api/config.php
nano api/config.php  # Edit credentials

# 2. Create schema
mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql

# 3. Verify
php database/verify-schema.php

# 4. Seed data
curl https://ch167436.tw1.ru/api/init-database.php

# 5. Test
curl https://ch167436.tw1.ru/api/test.php

# 6. Backup
php database/backup.php
```

### Schema Update
```bash
# 1. Backup first!
php database/backup.php

# 2. Apply changes
mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql

# 3. Verify
php database/verify-schema.php

# 4. Test
curl https://ch167436.tw1.ru/api/test.php
```

### Recovery from Backup
```bash
# 1. List backups
ls -lh database/backups/

# 2. Restore
mysql -u ch167436_3dprint -p ch167436_3dprint < database/backups/BACKUP_FILE.sql

# 3. Verify
php database/verify-schema.php
curl https://ch167436.tw1.ru/api/test.php
```

### Hard Reset
```bash
# 1. BACKUP FIRST!
php database/backup.php

# 2. Reset via API
curl "https://ch167436.tw1.ru/api/init-database.php?reset=RESET_TOKEN"

# 3. Verify
php database/verify-schema.php
curl https://ch167436.tw1.ru/api/test.php
```

## Expected Record Counts

After fresh seed:
- **services**: 6
- **portfolio**: 4
- **testimonials**: 4
- **faq**: 8
- **content_blocks**: 3
- **settings**: 12+
- **orders**: 0 (empty initially)

## Security Tokens

Change these in production:

1. **RESET_TOKEN** in `api/init-database.php` line 21
   - Default: `CHANGE_ME_IN_PRODUCTION_123456`
   - Purpose: Hard reset protection

2. **BACKUP_TOKEN** in `database/backup.php` line 36
   - Default: `CHANGE_ME_FOR_PRODUCTION`
   - Purpose: HTTP backup access

## Troubleshooting Quick Checks

```bash
# Database connectivity
mysql -u ch167436_3dprint -p ch167436_3dprint -e "SELECT 1;"

# Tables exist
mysql -u ch167436_3dprint -p ch167436_3dprint -e "SHOW TABLES;"

# Record counts
mysql -u ch167436_3dprint -p ch167436_3dprint -e "
  SELECT 'orders' AS tbl, COUNT(*) AS cnt FROM orders UNION
  SELECT 'services', COUNT(*) FROM services UNION
  SELECT 'portfolio', COUNT(*) FROM portfolio UNION
  SELECT 'testimonials', COUNT(*) FROM testimonials UNION
  SELECT 'faq', COUNT(*) FROM faq UNION
  SELECT 'content_blocks', COUNT(*) FROM content_blocks UNION
  SELECT 'settings', COUNT(*) FROM settings;
"

# API status
curl https://ch167436.tw1.ru/api/test.php

# Schema verification
php database/verify-schema.php | grep -E '(status|production_ready|summary)'
```

## Performance Optimization

```bash
# Analyze tables
mysql -u ch167436_3dprint -p ch167436_3dprint -e "ANALYZE TABLE orders, settings, services, portfolio, testimonials, faq, content_blocks;"

# Check indexes
mysql -u ch167436_3dprint -p ch167436_3dprint -e "SHOW INDEX FROM orders;"
```

## Monitoring

```bash
# Table sizes
mysql -u ch167436_3dprint -p ch167436_3dprint -e "
  SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
  FROM information_schema.TABLES
  WHERE table_schema = 'ch167436_3dprint'
  ORDER BY (data_length + index_length) DESC;
"

# Recent orders
mysql -u ch167436_3dprint -p ch167436_3dprint -e "
  SELECT order_number, name, phone, status, created_at 
  FROM orders 
  ORDER BY created_at DESC 
  LIMIT 10;
"
```

## Support

- 📖 Full docs: `database/README.md`
- 🔍 Verification guide: `database/VERIFICATION_AND_BACKUP.md`
- 🏗️ Architecture: `DATABASE_ARCHITECTURE.md`
- 🚨 Recovery: `DATABASE_FIX_INSTRUCTIONS.md`
