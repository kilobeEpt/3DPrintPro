# Database Schema

Complete database schema reference for 3D Print Pro.

## Overview

The database consists of **7 tables** storing all application data:

1. **orders** - Customer orders and inquiries
2. **settings** - Application configuration
3. **services** - Service offerings
4. **portfolio** - Project showcase
5. **testimonials** - Customer reviews
6. **faq** - Frequently asked questions
7. **content_blocks** - Dynamic page content

**Database Engine:** InnoDB  
**Character Set:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  
**MySQL Version:** 8.0+ (tested)

## Table Schemas

### 1. orders

Stores customer orders and contact form submissions.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | Primary key |
| `order_number` | VARCHAR(50) | NO | - | Unique order identifier (e.g., ORD-20250119-ABC123) |
| `type` | ENUM | NO | 'contact' | Order type: 'order', 'contact' |
| `name` | VARCHAR(255) | NO | - | Customer name |
| `email` | VARCHAR(255) | YES | NULL | Customer email |
| `phone` | VARCHAR(20) | NO | - | Customer phone |
| `telegram` | VARCHAR(100) | YES | NULL | Telegram username |
| `service` | VARCHAR(255) | YES | NULL | Service requested |
| `subject` | VARCHAR(255) | YES | NULL | Subject/title |
| `message` | TEXT | YES | NULL | Order details/message |
| `amount` | DECIMAL(10,2) | YES | 0.00 | Order amount in rubles |
| `calculator_data` | JSON | YES | NULL | Calculator parameters |
| `status` | ENUM | NO | 'new' | Order status: 'new', 'processing', 'completed', 'cancelled' |
| `telegram_sent` | BOOLEAN | NO | FALSE | Telegram notification sent flag |
| `telegram_error` | TEXT | YES | NULL | Telegram error message (if any) |
| `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update timestamp |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`order_number`)
- INDEX (`order_number`)
- INDEX (`phone`)
- INDEX (`email`)
- INDEX (`status`)
- INDEX (`created_at`)
- INDEX (`type`)

**Notes:**
- NO 'active' column - all orders kept for history
- `calculator_data` stores JSON with calculator parameters
- `order_number` format: ORD-YYYYMMDD-RANDOM6
- `telegram_sent` tracks if notification was sent
- `telegram_error` stores error message if notification failed

**Example calculator_data:**
```json
{
  "technology": "fdm",
  "material": "PLA",
  "weight": 100,
  "quantity": 2,
  "fill": 20,
  "quality": "normal",
  "timeline": "2-3 дня",
  "support": true
}
```

---

### 2. settings

Application configuration and settings.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | Primary key |
| `setting_key` | VARCHAR(100) | NO | - | Unique setting key |
| `setting_value` | TEXT | YES | NULL | Setting value |
| `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update timestamp |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`setting_key`)
- INDEX (`setting_key`)

**Notes:**
- NO 'active' column - all settings always active
- Key-value store for configuration
- Values stored as TEXT (can be JSON)

**Common Settings:**

| Key | Type | Description |
|-----|------|-------------|
| `site_name` | string | Site name |
| `site_description` | string | Site description |
| `site_keywords` | string | SEO keywords |
| `company_name` | string | Company name |
| `company_address` | string | Company address |
| `company_phone` | string | Company phone |
| `company_email` | string | Company email |
| `company_hours` | string | Business hours |
| `telegram_bot_token` | string | Telegram bot token |
| `telegram_chat_id` | string | Telegram chat ID |
| `telegram_contact_url` | string | Public bot link |
| `telegram_notify_new_order` | string | '1' or '0' - Enable new order notifications |
| `telegram_notify_status_change` | string | '1' or '0' - Enable status change notifications |
| `admin_login` | string | Admin username |
| `admin_password_hash` | string | Admin password hash (bcrypt) |

---

### 3. services

Service offerings and pricing.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(255) | NO | - | Service name |
| `slug` | VARCHAR(255) | NO | - | URL-friendly name |
| `icon` | VARCHAR(255) | YES | NULL | Font Awesome icon class |
| `description` | TEXT | YES | NULL | Service description |
| `features` | JSON | YES | NULL | Array of features |
| `price` | VARCHAR(100) | YES | NULL | Price text |
| `category` | VARCHAR(100) | YES | NULL | Service category |
| `sort_order` | INT | NO | 0 | Display order |
| `active` | BOOLEAN | NO | TRUE | Visible on website |
| `featured` | BOOLEAN | NO | FALSE | Highlighted on homepage |
| `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update timestamp |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`slug`)
- INDEX (`active`)
- INDEX (`featured`)
- INDEX (`sort_order`)
- INDEX (`slug`)
- INDEX (`category`)

**Notes:**
- HAS 'active' column for visibility control
- `features` stored as JSON array
- `slug` must be unique for SEO

**Example features:**
```json
["Быстрая печать", "Высокое качество", "Доступные цены"]
```

---

### 4. portfolio

Project showcase and case studies.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | Primary key |
| `title` | VARCHAR(255) | NO | - | Project title |
| `description` | TEXT | YES | NULL | Project description |
| `image_url` | VARCHAR(500) | YES | NULL | Image path |
| `category` | VARCHAR(100) | YES | NULL | Project category |
| `tags` | JSON | YES | NULL | Array of tags |
| `sort_order` | INT | NO | 0 | Display order |
| `active` | BOOLEAN | NO | TRUE | Visible on website |
| `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update timestamp |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`active`)
- INDEX (`category`)
- INDEX (`sort_order`)

**Notes:**
- HAS 'active' column for visibility control
- `tags` stored as JSON array
- Images stored in `/images/portfolio/`

**Example tags:**
```json
["архитектура", "прототип", "детальная печать"]
```

---

### 5. testimonials

Customer reviews and ratings.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | Primary key |
| `name` | VARCHAR(255) | NO | - | Customer name |
| `position` | VARCHAR(255) | YES | NULL | Job title/company |
| `avatar` | VARCHAR(500) | YES | NULL | Avatar image path |
| `text` | TEXT | NO | - | Review text |
| `rating` | INT | NO | 5 | Rating 1-5 stars |
| `sort_order` | INT | NO | 0 | Display order |
| `approved` | BOOLEAN | NO | TRUE | Moderation approval |
| `active` | BOOLEAN | NO | TRUE | Visible on website |
| `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update timestamp |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`active`)
- INDEX (`approved`)
- INDEX (`rating`)
- INDEX (`sort_order`)

**Constraints:**
- CHECK (`rating` >= 1 AND `rating` <= 5)

**Notes:**
- HAS 'active' and 'approved' columns for moderation
- `rating` must be between 1 and 5
- `approved` = FALSE hides review without deleting

---

### 6. faq

Frequently asked questions.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | Primary key |
| `question` | VARCHAR(500) | NO | - | Question text |
| `answer` | TEXT | NO | - | Answer text |
| `sort_order` | INT | NO | 0 | Display order |
| `active` | BOOLEAN | NO | TRUE | Visible on website |
| `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update timestamp |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`active`)
- INDEX (`sort_order`)

**Notes:**
- HAS 'active' column for visibility control
- Use `sort_order` to organize questions by topic

---

### 7. content_blocks

Dynamic content blocks for pages.

**Columns:**

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | INT | NO | AUTO_INCREMENT | Primary key |
| `block_name` | VARCHAR(255) | NO | - | Unique block identifier |
| `title` | VARCHAR(500) | YES | NULL | Block title |
| `content` | LONGTEXT | YES | NULL | Block content (HTML) |
| `data` | JSON | YES | NULL | Additional data |
| `page` | VARCHAR(100) | YES | NULL | Page identifier |
| `sort_order` | INT | NO | 0 | Display order |
| `active` | BOOLEAN | NO | TRUE | Visible on website |
| `created_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Last update timestamp |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`block_name`)
- INDEX (`block_name`)
- INDEX (`page`)
- INDEX (`active`)

**Notes:**
- HAS 'active' column for visibility control
- `block_name` must be unique
- `content` can contain HTML
- `data` stores additional JSON data

**Common Blocks:**

| block_name | Page | Description |
|------------|------|-------------|
| `home_hero` | home | Homepage hero section |
| `home_features` | home | Features section |
| `about_intro` | about | About page intro |

---

## Relationships

### No Foreign Keys

Currently, the schema uses no foreign key constraints for flexibility:
- Orders reference services by name (VARCHAR), not ID
- Settings are independent key-value pairs
- All tables are loosely coupled

### Potential Relationships (Future)

If implementing foreign keys:
- `orders.service_id` → `services.id`
- `orders.user_id` → `users.id` (if user system added)

---

## Initialization

### Create Schema

```bash
mysql -u username -p database_name < database/schema.sql
```

This creates all 7 tables with indexes and constraints.

### Seed Data

After schema creation, run:
```
https://your-domain.com/api/init-database.php
```

This populates tables with initial data:
- 6 default services
- 4 sample testimonials
- 8 FAQ items
- 3 content blocks
- 15+ settings

### Verify

```bash
# Check tables exist
mysql -u user -p database -e "SHOW TABLES;"

# Check record counts
mysql -u user -p database -e "
  SELECT 'orders' AS table_name, COUNT(*) AS count FROM orders
  UNION ALL
  SELECT 'services', COUNT(*) FROM services
  UNION ALL
  SELECT 'portfolio', COUNT(*) FROM portfolio
  UNION ALL
  SELECT 'testimonials', COUNT(*) FROM testimonials
  UNION ALL
  SELECT 'faq', COUNT(*) FROM faq
  UNION ALL
  SELECT 'content_blocks', COUNT(*) FROM content_blocks
  UNION ALL
  SELECT 'settings', COUNT(*) FROM settings;
"
```

Or use the audit script:
```bash
php scripts/db_audit.php
```

---

## Backup & Restore

### Backup

**Via PHP Script:**
```
https://your-domain.com/database/backup.php?token=YOUR_BACKUP_TOKEN
```

**Via MySQL:**
```bash
mysqldump -u user -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore

```bash
mysql -u user -p database_name < backup.sql
```

---

## Maintenance

### Optimize Tables

```sql
OPTIMIZE TABLE orders, services, portfolio, testimonials, faq, content_blocks, settings;
```

### Analyze Tables

```sql
ANALYZE TABLE orders, services, portfolio, testimonials, faq, content_blocks, settings;
```

### Check Table Integrity

```sql
CHECK TABLE orders, services, portfolio, testimonials, faq, content_blocks, settings;
```

### Repair Tables (if needed)

```sql
REPAIR TABLE table_name;
```

---

## Performance

### Indexes

All tables have appropriate indexes:
- Primary keys for unique identification
- Unique keys for slug/block_name
- Indexes on frequently queried columns (active, status, sort_order)
- Indexes on foreign-key-like columns (phone, email)

### Query Optimization

**Good:**
```sql
-- Uses index on status
SELECT * FROM orders WHERE status = 'new' LIMIT 50;

-- Uses index on active
SELECT * FROM services WHERE active = 1 ORDER BY sort_order;
```

**Bad:**
```sql
-- Full table scan
SELECT * FROM orders WHERE message LIKE '%keyword%';

-- No index on YEAR()
SELECT * FROM orders WHERE YEAR(created_at) = 2025;
```

### Table Sizes

Estimated sizes with sample data:

| Table | Rows | Approximate Size |
|-------|------|-----------------|
| orders | 1,000 | ~500 KB |
| services | 10 | ~10 KB |
| portfolio | 20 | ~20 KB |
| testimonials | 50 | ~50 KB |
| faq | 20 | ~20 KB |
| content_blocks | 10 | ~50 KB |
| settings | 20 | ~10 KB |

---

## Schema Validation

Use the verification script to check schema integrity:

```bash
php database/verify-schema.php
```

This checks:
- All 7 tables exist
- All columns present with correct types
- All indexes created
- No schema drift

---

## Migration Notes

### From localStorage

Old data structure:
- `printOrders` - Array of orders
- `services` - Array of services
- `portfolio` - Array of projects
- `testimonials` - Array of reviews
- `faqData` - Array of FAQ items

Migration:
1. Export from localStorage to JSON
2. Transform to match schema
3. Import via API or direct SQL INSERT

### Schema Changes

If modifying schema:
1. Backup database first
2. Test on staging environment
3. Use ALTER TABLE for changes
4. Update seed-data.php if needed
5. Update verify-schema.php
6. Document in CHANGELOG

---

## Security

### Protection

- ✅ PDO prepared statements (SQL injection prevention)
- ✅ htmlspecialchars on output (XSS prevention)
- ✅ Password hashing (bcrypt via password_hash())
- ✅ Input validation (type checking, length limits)
- ✅ Unique constraints (prevent duplicates)
- ✅ CHECK constraints (validate ratings)

### Best Practices

1. Never use root account for application
2. Grant only necessary privileges
3. Use strong database passwords
4. Backup regularly
5. Encrypt backups
6. Monitor slow queries
7. Review logs for suspicious activity

---

## Support

For schema issues:
- Run audit: `php scripts/db_audit.php`
- Check logs: `logs/api.log`
- Verify schema: `php database/verify-schema.php`
- See [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
