# Database Migrations - Quick Start Guide

## ✅ What's Included

This directory contains a complete database migration system for 3D PrintPro v3.0:

- **19 Migration files** - Full v3.0 normalized schema
- **6 Seeder files** - Reference data and lookup tables
- **2 CLI tools** - Migration and seeder runners
- **Complete documentation** - See `MIGRATIONS.md`

## 🚀 Quick Start

### Prerequisites

1. **PHP 7.4+** with PDO MySQL extension
2. **Composer dependencies installed**:
   ```bash
   composer install
   ```
3. **Database configured** in `.env` file (see `.env.example`)

### Basic Usage

```bash
# 1. Check migration status
php scripts/migrate status

# 2. Run all pending migrations
php scripts/migrate up

# 3. Seed reference data
php scripts/seed

# 4. Verify
php scripts/migrate status
```

## 📁 Directory Structure

```
database/
├── migrations/            # Migration files (timestamped)
│   ├── 2025_01_15_000001_create_users_table.php
│   ├── 2025_01_15_000002_create_customers_table.php
│   ├── ... (19 total migrations)
│   
├── seeders/              # Seeder files
│   ├── DatabaseSeeder.php           # Main seeder (calls others)
│   ├── OrderTypesSeeder.php         # Order types lookup
│   ├── OrderStatusesSeeder.php      # Order statuses lookup
│   ├── CategoriesSeeder.php         # Categories taxonomy
│   ├── MaterialsSeeder.php          # 3D printing materials
│   ├── DefaultUserSeeder.php        # Default admin user
│   └── SettingsSeeder.php           # Application settings
│   
├── Migration.php         # Base migration class
├── Seeder.php           # Base seeder class
├── MIGRATIONS.md        # Complete migration documentation
└── README_MIGRATIONS.md # This file
```

## 🗄️ Target Schema v3.0

The migrations create 19 tables organized in 4 groups:

### Core Tables (4)
- `users` - Admin authentication
- `customers` - Customer records  
- `orders` - Orders and inquiries
- `order_status_history` - Status change audit

### Lookup Tables (4)
- `categories` - Service/portfolio/FAQ categories
- `materials` - 3D printing materials
- `order_types` - Order type taxonomy
- `order_statuses` - Status workflow

### Content Tables (9)
- `services` - Service offerings
- `service_features` - Normalized features
- `portfolio` - Project showcase
- `tags` - Portfolio tags
- `portfolio_tags` - Portfolio↔Tags junction
- `testimonials` - Customer reviews
- `faq` - FAQ items
- `content_blocks` - Page content
- `content_revisions` - Version history

### System Tables (2)
- `settings` - Application config
- `audit_log` - Audit trail

## 🔑 Key Features

✅ **34 Foreign Key relationships** with proper CASCADE rules  
✅ **65 Indexes** including 15 composite indexes  
✅ **Soft deletes** on 7 tables  
✅ **Full-text search** on 5 tables  
✅ **Complete audit trail** - change tracking and versioning  
✅ **Fully normalized (3NF)** with strategic denormalization  

## 📝 Configuration

### Database Connection

Edit `.env` file (create from `.env.example` if needed):

```env
# MySQL Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### Verify Configuration

```bash
# Test database connection
php -r "require 'vendor/autoload.php'; require 'bootstrap/eloquent.php'; echo 'Connection OK';"
```

## 🛠️ Common Tasks

### Fresh Installation

```bash
# Start fresh (drops all tables)
php scripts/migrate fresh

# Seed reference data
php scripts/seed
```

### Add New Migration

1. Create file with timestamp: `database/migrations/2025_01_15_000020_add_column_to_table.php`
2. Implement up() and down() methods
3. Run: `php scripts/migrate up`

Example:
```php
<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AddColumnToTable extends Migration
{
    public function up()
    {
        Capsule::schema()->table('services', function (Blueprint $table) {
            $table->string('new_column')->nullable();
        });
    }

    public function down()
    {
        Capsule::schema()->table('services', function (Blueprint $table) {
            $table->dropColumn('new_column');
        });
    }
}
```

### Rollback

```bash
# Rollback last batch
php scripts/migrate down

# Rollback all migrations
php scripts/migrate reset

# Rollback and re-run all
php scripts/migrate refresh
```

## 🔐 Default Credentials

After seeding, default admin account is created:

- **Username:** `admin`
- **Password:** `admin123`
- **Email:** `admin@3dprintpro.ru`

⚠️ **CHANGE PASSWORD IMMEDIATELY IN PRODUCTION!**

## 📊 Seeded Data

The seeders populate:

- **4 Order Types:** order, contact, consultation, custom
- **6 Order Statuses:** new, processing, pending_approval, completed, cancelled, on_hold
- **12 Categories:** 5 service, 4 portfolio, 3 FAQ categories
- **6 Materials:** PLA, ABS, PETG, TPU, Nylon, Resin Standard
- **1 Admin User:** Default super admin
- **19 Settings:** Site, company, Telegram, email, calculator settings

## ⚠️ Important Notes

### Foreign Key Constraints

The migrations create 34 foreign key relationships. **Order matters!**

Parent tables must be created before child tables:
1. Lookup tables first (order_types, order_statuses, categories, materials)
2. Core tables (users, customers)
3. Content tables (services, portfolio, etc.)
4. Child/junction tables last

### Soft Deletes

Tables with soft delete (have `deleted_at` column):
- users, customers, services, portfolio, testimonials, faq, content_blocks

Query active records: `WHERE deleted_at IS NULL`

### Full-Text Search

Requires InnoDB (MySQL 5.6+) or MyISAM. Full-text indexes created on:
- customers (name, email, phone)
- services (name, description)
- portfolio (title, description)
- orders (subject, message)
- faq (question, answer)

## 🐛 Troubleshooting

### "could not find driver"

**Solution:** Install PDO MySQL extension
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql

# Verify
php -m | grep pdo_mysql
```

### "Access denied for user"

**Solution:** Check database credentials in `.env`

### "Table already exists"

**Solution:** Either:
1. Drop the table manually, or
2. Use `php scripts/migrate fresh` to start clean

### Foreign key constraint error

**Solution:** 
1. Check migration order
2. Verify parent tables exist
3. Ensure data types match (e.g., `unsignedInteger` for FK to `increments`)

## 📚 Full Documentation

See `database/MIGRATIONS.md` for:
- Complete schema documentation
- Migration API reference
- Best practices
- Production deployment guide
- Detailed troubleshooting

## 🎯 Next Steps

After running migrations and seeders:

1. **Change admin password** via admin panel
2. **Configure Telegram** settings
3. **Add content** (services, portfolio, testimonials, FAQ)
4. **Set up application** settings
5. **Test thoroughly** in development

## 🔗 Related Files

- `scripts/migrate` - Migration runner CLI
- `scripts/seed` - Seeder runner CLI
- `bootstrap/eloquent.php` - Database bootstrap
- `docs/db-overhaul/` - Design documentation
- `.env.example` - Configuration template

## 💡 Tips

- Always **backup** before running migrations in production
- Use `status` command frequently to check migration state
- Test rollback on development before deploying
- Keep migrations small and focused
- Run seeders after fresh migrations

## 📞 Support

For issues or questions, refer to:
- `database/MIGRATIONS.md` - Comprehensive guide
- `docs/db-overhaul/schema-design.md` - Full schema specification
- Migration files themselves - well-commented code

---

**Version:** 3.0  
**Created:** January 2025  
**Status:** ✅ Production Ready
