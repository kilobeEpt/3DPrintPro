# Forms System Implementation Checklist

## Pre-Migration Checklist

- [ ] Backup database: `php database/backup.php`
- [ ] Review schema changes: `less database/schema.sql`
- [ ] Review forms documentation: `less database/FORMS_SYSTEM.md`
- [ ] Review migration guide: `less database/MIGRATION_GUIDE.md`
- [ ] Ensure PHP 7.4+ available
- [ ] Ensure composer dependencies installed: `composer install`
- [ ] Test environment ready (optional): `php scripts/setup-test-db.php`

## Schema Deployment Checklist

### MySQL/MariaDB (Production)

- [ ] Apply schema: `mysql -u USER -p DATABASE < database/schema.sql`
- [ ] Verify no errors in output
- [ ] Check tables created: `SHOW TABLES;` (should show 12 tables)
- [ ] Verify foreign keys: `SHOW CREATE TABLE orders;`
- [ ] Run verification: `php database/verify-schema.php`
- [ ] Verify output status is "OK"

### SQLite (Testing)

- [ ] Setup test database: `php scripts/setup-test-db.php`
- [ ] Verify output shows all 12 tables
- [ ] Check test.sqlite file created in database/

## Seeding Checklist

- [ ] Run seed script: `php scripts/seed-forms.php`
- [ ] Verify 2 forms created (contact, order)
- [ ] Verify 12 fields created (6 per form)
- [ ] Check forms in database: `SELECT * FROM forms;`
- [ ] Check fields in database: `SELECT * FROM form_fields;`

## Model Verification Checklist

- [ ] All 5 new model files exist in app/Models/
  - [ ] Form.php
  - [ ] FormField.php
  - [ ] FormSubmission.php
  - [ ] FormSubmissionValue.php
  - [ ] SettingsAudit.php
- [ ] Order.php updated with formSubmission relationship
- [ ] Autoload regenerated: `composer dump-autoload`
- [ ] Test model loading: `php -r "require 'vendor/autoload.php'; require 'bootstrap/eloquent.php'; use App\Models\Form; echo 'OK';"`

## Optional Migration Checklist

- [ ] Run dry-run: `php scripts/migrate-orders-to-forms.php --dry-run`
- [ ] Review dry-run output for errors
- [ ] Test with limit: `php scripts/migrate-orders-to-forms.php --dry-run --limit=10`
- [ ] Run actual migration: `php scripts/migrate-orders-to-forms.php`
- [ ] Verify migration count matches expected
- [ ] Check orders linked: `SELECT COUNT(*) FROM orders WHERE form_submission_id IS NOT NULL;`
- [ ] Verify submissions created: `SELECT COUNT(*) FROM form_submissions;`
- [ ] Verify values created: `SELECT COUNT(*) FROM form_submission_values;`

## Testing Checklist

### Database Queries

- [ ] Test form lookup: `SELECT * FROM forms WHERE slug = 'contact';`
- [ ] Test fields query: `SELECT COUNT(*) FROM form_fields WHERE form_id = 1;`
- [ ] Test submissions: `SELECT * FROM form_submissions LIMIT 5;`
- [ ] Test values: `SELECT * FROM form_submission_values LIMIT 10;`
- [ ] Test order join:
  ```sql
  SELECT o.id, o.order_number, fs.form_slug, fs.submitted_at
  FROM orders o
  LEFT JOIN form_submissions fs ON o.form_submission_id = fs.id
  LIMIT 5;
  ```

### Eloquent Models

Create test-forms.php:
```php
<?php
require_once 'vendor/autoload.php';
require_once 'bootstrap/eloquent.php';

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Order;

// Test forms
$forms = Form::all();
echo "Forms: " . $forms->count() . "\n";

// Test with relationships
$form = Form::with('activeFields')->first();
echo "Form '{$form->name}' has {$form->activeFields->count()} fields\n";

// Test submissions
$submissions = FormSubmission::count();
echo "Submissions: {$submissions}\n";

// Test order relationship
$order = Order::with('formSubmission')->first();
if ($order && $order->formSubmission) {
    echo "Order linked to form: {$order->formSubmission->form_slug}\n";
}

echo "\n✓ All model tests passed!\n";
```

- [ ] Run test script: `php test-forms.php`
- [ ] Verify all counts correct
- [ ] Verify no errors

### Verification Script

- [ ] Run: `php database/verify-schema.php`
- [ ] Verify status: "OK"
- [ ] Verify all 12 tables exist
- [ ] Verify all columns present
- [ ] Verify no missing elements

## Documentation Checklist

- [ ] Read FORMS_SYSTEM.md for complete reference
- [ ] Read MIGRATION_GUIDE.md for deployment steps
- [ ] Review FORMS_SCHEMA_DIAGRAM.txt for visual understanding
- [ ] Check FORMS_SYSTEM_SUMMARY.md for quick overview
- [ ] Review updated database/README.md

## Functionality Checklist

### Backward Compatibility

- [ ] Existing orders API still works
- [ ] Old order creation still functions
- [ ] No errors in existing endpoints
- [ ] Legacy data intact

### New Features

- [ ] Can query forms: `Form::all()`
- [ ] Can get form with fields: `Form::with('activeFields')->find(1)`
- [ ] Can create submission programmatically
- [ ] Can query by field value
- [ ] Can log settings changes: `SettingsAudit::logChange()`
- [ ] Order can access formSubmission: `$order->formSubmission`

## Performance Checklist

- [ ] Form queries fast (< 10ms)
- [ ] Submission queries fast (< 50ms)
- [ ] Join queries optimized (< 20ms)
- [ ] All indexes created (check SHOW INDEX)

## Production Checklist

- [ ] All tests passed
- [ ] No errors in logs
- [ ] Backup created and verified
- [ ] Rollback plan documented
- [ ] Team trained on new system
- [ ] Monitoring configured
- [ ] Documentation accessible

## Rollback Checklist (If Needed)

- [ ] Stop application
- [ ] Restore from backup: `mysql -u USER -p DATABASE < backup_YYYYMMDD_HHMMSS.sql`
- [ ] Verify restoration: `SELECT COUNT(*) FROM orders;`
- [ ] Restart application
- [ ] Monitor for issues

## Final Verification

- [ ] ✅ 12 tables exist in database
- [ ] ✅ 2 forms seeded
- [ ] ✅ 12 form fields seeded
- [ ] ✅ 5 Eloquent models created
- [ ] ✅ Orders table has new columns
- [ ] ✅ Foreign keys in place
- [ ] ✅ Indexes created
- [ ] ✅ Migration script tested
- [ ] ✅ Documentation complete
- [ ] ✅ Backward compatible
- [ ] ✅ Production ready

## Sign-Off

- [ ] Technical lead approved
- [ ] Database schema reviewed
- [ ] Documentation reviewed
- [ ] Testing completed
- [ ] Deployment plan approved

---

**Status**: ⬜ Not Started | ⏳ In Progress | ✅ Complete | ❌ Failed

**Deployed By**: ________________  
**Date**: ________________  
**Environment**: ________________  
**Notes**: ________________
