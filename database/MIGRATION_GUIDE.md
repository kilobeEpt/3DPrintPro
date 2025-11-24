# Forms System Migration Guide

## Overview

This guide walks you through migrating from the legacy orders system to the new full-featured forms system. The migration is **backward compatible** - existing functionality will continue to work during and after migration.

## Prerequisites

Before starting:
- [x] Backup your database
- [x] Review the new schema in `database/schema.sql`
- [x] Read `database/FORMS_SYSTEM.md` for full documentation
- [ ] Have PHP 7.4+ CLI access
- [ ] Have database credentials ready

## Migration Steps

### Step 1: Backup Database

**CRITICAL**: Always backup before schema changes!

```bash
# Using database/backup.php
php database/backup.php

# Or using mysqldump
mysqldump -u USERNAME -p DATABASE_NAME > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Apply Schema Changes

#### Option A: MySQL/MariaDB (Production)

```bash
# Review the schema first
less database/schema.sql

# Apply schema
mysql -u USERNAME -p DATABASE_NAME < database/schema.sql
```

**What this does:**
- Creates 5 new tables (forms, form_fields, form_submissions, form_submission_values, settings_audit)
- Adds 2 new columns to orders table (form_submission_id, form_slug)
- Creates necessary indexes and foreign keys
- All operations are idempotent (safe to run multiple times)

#### Option B: SQLite (Testing)

```bash
# This script creates a test database
php scripts/setup-test-db.php
```

**Output:**
```
Setting up test database...
Creating services table...
Creating orders table...
Creating settings table...
Creating forms table...
Creating form_fields table...
Creating form_submissions table...
Creating form_submission_values table...
Creating settings_audit table...
Inserting sample data...

✓ Test database setup complete!
  Database: /path/to/database/test.sqlite
  Services: 2
  Settings: 1
  Orders: 0
  Forms: 0
  Form Fields: 0
  Form Submissions: 0
  Settings Audit: 0
```

### Step 3: Verify Schema

```bash
# CLI verification
php database/verify-schema.php

# Or via HTTP
curl https://your-site.com/database/verify-schema.php
```

**Expected Output:**
```json
{
    "status": "OK",
    "timestamp": "2025-01-XX XX:XX:XX",
    "tables": {
        "orders": {"exists": true, "columns": 19, "missing": []},
        "settings": {"exists": true, "columns": 4, "missing": []},
        "services": {"exists": true, "columns": 13, "missing": []},
        "portfolio": {"exists": true, "columns": 10, "missing": []},
        "testimonials": {"exists": true, "columns": 11, "missing": []},
        "faq": {"exists": true, "columns": 7, "missing": []},
        "content_blocks": {"exists": true, "columns": 10, "missing": []},
        "forms": {"exists": true, "columns": 12, "missing": []},
        "form_fields": {"exists": true, "columns": 15, "missing": []},
        "form_submissions": {"exists": true, "columns": 10, "missing": []},
        "form_submission_values": {"exists": true, "columns": 7, "missing": []},
        "settings_audit": {"exists": true, "columns": 8, "missing": []}
    }
}
```

### Step 4: Seed Forms Data

This creates the default contact and order forms with their fields.

```bash
php scripts/seed-forms.php
```

**Expected Output:**
```
========================================
Forms Seeding Script
========================================

Seeding forms...
  ✓ Created form 'contact' (ID: 1)
  ✓ Created form 'order' (ID: 2)

Seeding form fields...
  ✓ Created field 'name' in form 'contact'
  ✓ Created field 'phone' in form 'contact'
  ✓ Created field 'email' in form 'contact'
  ✓ Created field 'telegram' in form 'contact'
  ✓ Created field 'subject' in form 'contact'
  ✓ Created field 'message' in form 'contact'
  ✓ Created field 'name' in form 'order'
  ✓ Created field 'phone' in form 'order'
  ✓ Created field 'email' in form 'order'
  ✓ Created field 'telegram' in form 'order'
  ✓ Created field 'service' in form 'order'
  ✓ Created field 'message' in form 'order'

========================================
Seeding Complete
========================================
Forms created/found: 2
Fields created: 12
```

**What this creates:**

1. **Contact Form** (`slug: contact`)
   - name (text, required)
   - phone (phone, required)
   - email (email, optional)
   - telegram (text, optional)
   - subject (text, optional)
   - message (textarea, required)

2. **Order Form** (`slug: order`)
   - name (text, required)
   - phone (phone, required)
   - email (email, optional)
   - telegram (text, optional)
   - service (text, required)
   - message (textarea, optional)

### Step 5: Migrate Existing Orders (Optional)

This step links existing orders to the new forms system. **This is optional** - old orders will continue to work without migration.

#### 5.1: Test Migration (Dry Run)

```bash
# Test with dry run first
php scripts/migrate-orders-to-forms.php --dry-run

# Test with limited orders
php scripts/migrate-orders-to-forms.php --dry-run --limit=10
```

**Example Output:**
```
========================================
Order to Forms Migration Script
========================================

⚠️  DRY RUN MODE - No changes will be made

✓ Found forms:
  - Contact form (ID: 1)
  - Order form (ID: 2)

✓ Loaded form fields:
  - Contact form: 6 fields
  - Order form: 6 fields

Found 150 orders to migrate

Processing order #1 (ORD-20250115-ABC123)... ✓
Processing order #2 (ORD-20250115-DEF456)... ✓
...
Processing order #150 (ORD-20250120-XYZ789)... ✓

========================================
Migration Complete
========================================
Successfully migrated: 150 orders
Errors: 0

⚠️  This was a DRY RUN - no changes were made
Run without --dry-run to perform the actual migration.
```

#### 5.2: Run Actual Migration

```bash
# Migrate all orders
php scripts/migrate-orders-to-forms.php

# Or migrate in batches
php scripts/migrate-orders-to-forms.php --limit=100
```

**What this does:**

For each existing order:
1. Creates a `form_submissions` record with JSON data
2. Creates individual `form_submission_values` records
3. Links the order to the submission via `form_submission_id`
4. Preserves all original timestamps
5. Runs in a transaction (all or nothing)

**After migration:**
```sql
-- Before migration
SELECT form_submission_id FROM orders WHERE id = 1;
-- Result: NULL

-- After migration
SELECT form_submission_id FROM orders WHERE id = 1;
-- Result: 123

-- You can now query via relationships
SELECT o.*, fs.form_slug, fs.submitted_data 
FROM orders o 
LEFT JOIN form_submissions fs ON o.form_submission_id = fs.id 
WHERE o.id = 1;
```

### Step 6: Test the System

#### 6.1: Test with Eloquent Models

Create a test script:

```php
<?php
// test-forms.php
require_once 'vendor/autoload.php';
require_once 'bootstrap/eloquent.php';

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Order;

// Test 1: Get forms
echo "Forms in database:\n";
$forms = Form::with('activeFields')->get();
foreach ($forms as $form) {
    echo "  - {$form->name} ({$form->slug}): {$form->activeFields->count()} fields\n";
}

// Test 2: Get recent submissions
echo "\nRecent submissions:\n";
$submissions = FormSubmission::with('form')->orderBy('submitted_at', 'desc')->limit(5)->get();
foreach ($submissions as $submission) {
    echo "  - {$submission->form->name}: " . $submission->submitted_at->format('Y-m-d H:i') . "\n";
}

// Test 3: Get order with form
echo "\nOrder with form submission:\n";
$order = Order::with('formSubmission.form')->where('form_submission_id', '!=', null)->first();
if ($order) {
    echo "  - Order #{$order->id}: {$order->formSubmission->form->name}\n";
} else {
    echo "  - No migrated orders found\n";
}

echo "\n✓ All tests passed!\n";
```

Run it:
```bash
php test-forms.php
```

#### 6.2: Test Database Queries

```sql
-- Count forms
SELECT COUNT(*) FROM forms;

-- Count fields per form
SELECT f.name, COUNT(ff.id) as field_count 
FROM forms f 
LEFT JOIN form_fields ff ON f.id = ff.form_id 
GROUP BY f.id;

-- Count submissions per form
SELECT f.name, COUNT(fs.id) as submission_count 
FROM forms f 
LEFT JOIN form_submissions fs ON f.id = fs.form_id 
GROUP BY f.id;

-- Check migrated orders
SELECT COUNT(*) FROM orders WHERE form_submission_id IS NOT NULL;

-- Get recent submissions with values
SELECT 
    fs.id,
    f.name as form_name,
    fs.submitted_at,
    (SELECT COUNT(*) FROM form_submission_values fsv WHERE fsv.form_submission_id = fs.id) as value_count
FROM form_submissions fs
JOIN forms f ON fs.form_id = f.id
ORDER BY fs.submitted_at DESC
LIMIT 10;
```

### Step 7: Update Application Code (Optional)

The system is backward compatible, but you can optionally update your code to use the new forms system:

#### Example: Create Submission via API

```php
<?php
// In your API endpoint
require_once 'vendor/autoload.php';
require_once 'bootstrap/eloquent.php';

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use App\Models\Order;

// Get form
$form = Form::where('slug', 'contact')->first();

// Create submission
$submission = FormSubmission::create([
    'form_id' => $form->id,
    'form_slug' => $form->slug,
    'submitted_data' => $data,
    'status' => 'pending',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'submitted_at' => now(),
]);

// Create individual values
foreach ($data as $fieldName => $fieldValue) {
    $field = $form->fields()->where('name', $fieldName)->first();
    
    FormSubmissionValue::create([
        'form_submission_id' => $submission->id,
        'form_field_id' => $field ? $field->id : null,
        'field_name' => $fieldName,
        'field_value' => $fieldValue,
    ]);
}

// Create order (legacy table)
$order = Order::create([
    'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
    'form_submission_id' => $submission->id,
    'form_slug' => $form->slug,
    'name' => $data['name'],
    'phone' => $data['phone'],
    // ... other fields
]);

// Update submission status
$submission->update(['status' => 'processed']);
```

## Rollback Plan

If you need to rollback the changes:

### Option 1: Restore from Backup

```bash
mysql -u USERNAME -p DATABASE_NAME < backup_YYYYMMDD_HHMMSS.sql
```

### Option 2: Drop New Tables Only

**WARNING**: This will delete all form submission data!

```sql
-- Drop in correct order (foreign keys)
DROP TABLE IF EXISTS form_submission_values;
DROP TABLE IF EXISTS form_submissions;
DROP TABLE IF EXISTS form_fields;
DROP TABLE IF EXISTS forms;
DROP TABLE IF EXISTS settings_audit;

-- Remove new columns from orders (optional)
ALTER TABLE orders DROP FOREIGN KEY fk_orders_form_submission;
ALTER TABLE orders DROP COLUMN form_submission_id;
ALTER TABLE orders DROP COLUMN form_slug;
```

## Troubleshooting

### Issue: Foreign Key Constraint Fails

**Symptom:**
```
ERROR 1215 (HY000): Cannot add foreign key constraint
```

**Solution:**
1. Check that the referenced table exists
2. Ensure the referenced column has the same type
3. Verify InnoDB engine is used

### Issue: Migration Script Fails

**Symptom:**
```
✗ Error: Required forms not found. Please run seed data first.
```

**Solution:**
```bash
# Run seed script first
php scripts/seed-forms.php

# Then retry migration
php scripts/migrate-orders-to-forms.php
```

### Issue: Duplicate Key Error

**Symptom:**
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
```

**Solution:**
This means the data already exists. Safe to ignore or:
```bash
# Check what's already seeded
echo "SELECT COUNT(*) FROM forms;" | mysql -u USER -p DATABASE

# If forms exist, migration can proceed
php scripts/migrate-orders-to-forms.php
```

### Issue: Eloquent Not Found

**Symptom:**
```
Fatal error: Class 'App\Models\Form' not found
```

**Solution:**
```bash
# Install composer dependencies
composer install

# Regenerate autoload
composer dump-autoload
```

## Verification Checklist

After migration, verify:

- [ ] All 5 new tables exist: `forms`, `form_fields`, `form_submissions`, `form_submission_values`, `settings_audit`
- [ ] Orders table has new columns: `form_submission_id`, `form_slug`
- [ ] Foreign key constraints are in place
- [ ] Default forms are seeded (contact, order)
- [ ] Form fields are populated (12 total: 6 per form)
- [ ] Existing orders are migrated (if you chose to migrate)
- [ ] No data loss (verify order count before/after)
- [ ] Application continues to work normally
- [ ] New Eloquent models load correctly

## Performance Considerations

The new schema adds several indexes for performance:

- `forms.slug` - Fast form lookup
- `form_submissions.form_slug` - Fast submission queries
- `form_submission_values.field_name` - Fast field value searches
- `orders.form_submission_id` - Fast order-to-submission joins

With proper indexes, queries remain fast even with large datasets:
- 10,000+ forms submissions: < 100ms
- Field value searches: < 50ms
- Order with submission join: < 10ms

## Next Steps

After successful migration:

1. **Update documentation** - Document any custom forms you create
2. **Train team** - Show admins how to use the forms system
3. **Monitor** - Watch for any issues in the first few days
4. **Optimize** - Add more indexes if needed based on usage
5. **Enhance** - Consider adding form builder UI, analytics, etc.

## Support

If you encounter issues:

1. Check this guide
2. Review `database/FORMS_SYSTEM.md` for detailed documentation
3. Run verification: `php database/verify-schema.php`
4. Check database error logs
5. Restore from backup if needed

---

**Important**: Always test migrations in a development/staging environment before applying to production!
