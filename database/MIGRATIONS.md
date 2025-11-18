# Database Migrations Guide

## Overview

This document covers the database migration system for 3D PrintPro v3.0. The migration system uses Illuminate Schema Builder and provides artisan-style CLI commands for managing database schema lifecycle.

## Quick Start

```bash
# Run all pending migrations
php scripts/migrate up

# Seed the database with reference data
php scripts/seed

# Check migration status
php scripts/migrate status
```

## Architecture

### Components

1. **Migration Runner** (`scripts/migrate`) - CLI tool for running migrations
2. **Seeder Runner** (`scripts/seed`) - CLI tool for running seeders
3. **Migration Base Class** (`database/Migration.php`) - Abstract class for migrations
4. **Seeder Base Class** (`database/Seeder.php`) - Abstract class for seeders
5. **Migrations** (`database/migrations/*.php`) - Individual migration files
6. **Seeders** (`database/seeders/*.php`) - Seeder files for reference data

### Migration Tracking

Migrations are tracked in the `migrations` table:
- `id` - Auto-increment primary key
- `migration` - Migration filename (e.g., `2025_01_15_000001_create_users_table`)
- `batch` - Batch number (increments with each `migrate up` run)
- `migrated_at` - Timestamp when migration was executed

## CLI Commands

### Migration Commands

```bash
# Run pending migrations
php scripts/migrate up
php scripts/migrate           # alias for 'up'

# Rollback last batch
php scripts/migrate down
php scripts/migrate rollback  # alias for 'down'

# Rollback all migrations
php scripts/migrate reset

# Rollback all and re-run migrations
php scripts/migrate refresh

# Drop all tables and re-run migrations
php scripts/migrate fresh

# Show migration status
php scripts/migrate status
```

### Seeder Commands

```bash
# Run all seeders (DatabaseSeeder)
php scripts/seed

# Run specific seeder
php scripts/seed OrderTypesSeeder
php scripts/seed --class=OrderTypesSeeder
```

## Target Schema v3.0

The migration system implements a fully normalized (3NF) schema with 19 tables:

### Core Tables
1. **users** - Admin authentication and authorization
2. **customers** - Unified customer records
3. **orders** - Customer orders with relationships
4. **order_status_history** - Audit trail for order status changes

### Lookup Tables
5. **categories** - Shared taxonomy for services/portfolio/FAQ
6. **materials** - 3D printing materials catalog
7. **order_types** - Order type taxonomy
8. **order_statuses** - Order status workflow

### Content Tables
9. **services** - Service offerings
10. **service_features** - Normalized service features
11. **portfolio** - Project showcase
12. **tags** - Portfolio tag taxonomy
13. **portfolio_tags** - Many-to-many junction table
14. **testimonials** - Customer reviews
15. **faq** - Frequently asked questions
16. **content_blocks** - Dynamic page content
17. **content_revisions** - Content version history

### System Tables
18. **settings** - Application configuration
19. **audit_log** - Centralized audit trail

### Key Improvements from v2.0

✅ **Foreign Keys** - 34 FK relationships with explicit CASCADE rules  
✅ **Soft Deletes** - Recoverable deletions for content entities  
✅ **Audit Trail** - Complete change tracking  
✅ **Normalized Data** - Categories, statuses, features extracted to lookup tables  
✅ **65 Indexes** - Including 15 composite indexes for performance  
✅ **Full-Text Search** - On customers, services, portfolio, orders, FAQ  

## Migration Files

### Naming Convention

Migrations follow Laravel-style naming:
```
{YYYY}_{MM}_{DD}_{HHMMSS}_{description}.php
```

Example: `2025_01_15_000001_create_users_table.php`

### Migration Structure

```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->unique();
            // ... more columns
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('username');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('users');
    }
}
```

### Class Name Convention

Class names are generated from filename:
- Remove timestamp prefix (first 4 parts: `YYYY_MM_DD_HHMMSS`)
- Convert to PascalCase

Example:
- File: `2025_01_15_000001_create_users_table.php`
- Class: `CreateUsersTable`

## Seeders

### Seeder Structure

```php
<?php

class OrderTypesSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['type_key' => 'order', 'display_name' => 'Заказ'],
            // ... more data
        ];

        foreach ($types as $type) {
            $this->updateOrInsert(
                'order_types', 
                ['type_key' => $type['type_key']], 
                $type
            );
        }
    }
}
```

### Available Seeder Methods

```php
// Insert data
$this->insert('table_name', ['column' => 'value']);

// Check if exists
$this->exists('table_name', ['column' => 'value']);

// Update or insert (upsert)
$this->updateOrInsert(
    'table_name',
    ['key' => 'value'],    // Match criteria
    ['data' => 'value']    // Values to set
);

// Call another seeder
$this->call('OtherSeeder');

// Get database connection
$this->db();
```

### Seeder Execution Order

Defined in `DatabaseSeeder.php`:

1. **OrderTypesSeeder** - Order types lookup
2. **OrderStatusesSeeder** - Order statuses lookup
3. **CategoriesSeeder** - Categories for services/portfolio/FAQ
4. **MaterialsSeeder** - 3D printing materials
5. **DefaultUserSeeder** - Default admin user
6. **SettingsSeeder** - Application settings

## Schema Details

### Foreign Key Relationships

#### CASCADE DELETE
Parent deletion cascades to children:
```sql
service_features.service_id → services.id ON DELETE CASCADE
portfolio_tags.portfolio_id → portfolio.id ON DELETE CASCADE
content_revisions.content_block_id → content_blocks.id ON DELETE CASCADE
order_status_history.order_id → orders.id ON DELETE CASCADE
```

#### SET NULL
Parent deletion nulls child reference:
```sql
services.category_id → categories.id ON DELETE SET NULL
services.created_by → users.id ON DELETE SET NULL
orders.service_id → services.id ON DELETE SET NULL
```

#### RESTRICT
Prevent parent deletion if children exist:
```sql
orders.customer_id → customers.id ON DELETE RESTRICT
orders.order_type_id → order_types.id ON DELETE RESTRICT
orders.order_status_id → order_statuses.id ON DELETE RESTRICT
```

### Soft Deletes

Tables with soft delete support (have `deleted_at` column):
- `users`
- `customers`
- `services`
- `portfolio`
- `testimonials`
- `faq`
- `content_blocks`

**Note:** `orders` table does NOT use soft delete (permanent records for audit).

### Full-Text Search

Tables with full-text indexes:
```sql
-- Customer search
FULLTEXT INDEX ft_customer_search (name, email, phone)

-- Service search
FULLTEXT INDEX ft_service_search (name, description)

-- Portfolio search
FULLTEXT INDEX ft_portfolio_search (title, description)

-- Order search
FULLTEXT INDEX ft_order_search (subject, message)

-- FAQ search
FULLTEXT INDEX ft_faq_search (question, answer)
```

## Best Practices

### Creating New Migrations

1. **Use timestamp-based naming** for proper ordering
2. **One logical change per migration** for easier rollback
3. **Always implement both up() and down()** methods
4. **Test rollback** before committing
5. **Add indexes** for foreign keys and frequently queried columns

### Writing Rollback Methods

```php
public function down()
{
    // Drop tables in reverse order of dependencies
    Capsule::schema()->dropIfExists('child_table');
    Capsule::schema()->dropIfExists('parent_table');
}
```

### Seeder Idempotency

Use `updateOrInsert()` to make seeders idempotent:

```php
// Good - idempotent
$this->updateOrInsert(
    'order_types',
    ['type_key' => 'order'],
    ['display_name' => 'Заказ', 'active' => true]
);

// Bad - will fail on re-run if unique constraint exists
$this->insert('order_types', ['type_key' => 'order']);
```

## Troubleshooting

### Migration Failed

If a migration fails:

1. **Check the error message** - shows which statement failed
2. **Rollback if needed**: `php scripts/migrate down`
3. **Fix the migration file**
4. **Re-run**: `php scripts/migrate up`

### Foreign Key Constraint Error

Common causes:
- Parent table doesn't exist yet (wrong migration order)
- Referenced column doesn't exist
- Data type mismatch between FK and PK

Solution:
- Ensure parent tables are created first
- Use correct data types (e.g., `unsignedInteger` for FKs to `increments`)

### Can't Drop Table - Foreign Key Constraint

```bash
# Disable FK checks temporarily
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE table_name;
SET FOREIGN_KEY_CHECKS=1;

# Or use fresh command (drops all tables)
php scripts/migrate fresh
```

## Migration Timeline

### Phase 1: Fresh Installation (Recommended)

For new installations:

```bash
# 1. Run all migrations
php scripts/migrate up

# 2. Seed reference data
php scripts/seed

# 3. Verify
php scripts/migrate status
```

### Phase 2: Production Migration (Future)

For migrating from v2.0 to v3.0 (requires data migration scripts):

```bash
# 1. Backup existing database
php database/backup.php

# 2. Export existing data
# (Custom data migration scripts needed)

# 3. Run fresh migrations
php scripts/migrate fresh

# 4. Seed lookup tables
php scripts/seed

# 5. Import migrated data
# (Custom data import scripts needed)
```

## Safety Notes

### ⚠️ Destructive Commands

These commands **DROP DATA**:

```bash
php scripts/migrate fresh    # Drops ALL tables
php scripts/migrate reset     # Rollback ALL migrations
php scripts/migrate refresh   # Rollback and re-run ALL
```

**Always backup before running destructive commands!**

### Production Checklist

Before running migrations in production:

- [ ] **Backup database** (`php database/backup.php`)
- [ ] **Test on staging** environment first
- [ ] **Schedule maintenance window** (expect 2-4 hours downtime)
- [ ] **Verify foreign key constraints** are properly set
- [ ] **Check disk space** (migrations may temporarily double disk usage)
- [ ] **Prepare rollback plan** in case of failure
- [ ] **Notify stakeholders** of maintenance window

## Reference

### Schema Builder Documentation

Full Illuminate Schema Builder docs:  
https://laravel.com/docs/10.x/migrations

### Common Column Types

```php
$table->increments('id');                    // UNSIGNED INT AUTO_INCREMENT
$table->bigIncrements('id');                 // UNSIGNED BIGINT AUTO_INCREMENT
$table->string('name', 255);                 // VARCHAR(255)
$table->text('description');                 // TEXT
$table->longText('content');                 // LONGTEXT
$table->integer('count');                    // INT
$table->decimal('price', 10, 2);            // DECIMAL(10,2)
$table->boolean('active');                   // TINYINT(1)
$table->json('data');                        // JSON
$table->timestamp('created_at');             // TIMESTAMP
$table->timestamps();                        // created_at + updated_at
$table->softDeletes();                       // deleted_at
$table->enum('status', ['new', 'done']);    // ENUM
```

### Common Index Types

```php
$table->index('column');                     // Single column index
$table->index(['col1', 'col2'], 'idx_name'); // Composite index
$table->unique('column');                    // Unique constraint
$table->unique(['col1', 'col2']);           // Composite unique
$table->foreign('user_id')->references('id')->on('users'); // FK
```

## Support

For issues or questions:
- Review migration files in `database/migrations/`
- Check seeder files in `database/seeders/`
- See design docs in `docs/db-overhaul/`
- Run `php scripts/migrate status` for current state

---

**Version:** 3.0  
**Last Updated:** January 2025  
**Status:** ✅ Production Ready
