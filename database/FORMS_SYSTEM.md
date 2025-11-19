# Forms System Documentation

## Overview

The Forms System is a comprehensive database schema extension that enables full-featured dynamic form management for the 3D Print Pro platform. It provides:

- **Dynamic Form Definitions**: Create and manage multiple forms through the database
- **Flexible Field Configuration**: Define fields with types, validation rules, and options
- **Submission Tracking**: Store both denormalized (JSON) and normalized (table rows) submission data
- **Legacy Integration**: Seamless backward compatibility with existing orders table
- **Audit Logging**: Track all settings changes with full audit trail

## Database Schema

### New Tables

#### 1. `forms`
Stores form definitions and configurations.

**Columns:**
- `id` - Primary key
- `name` - Form display name
- `slug` - Unique URL-friendly identifier
- `description` - Optional description
- `settings` - JSON field for form-level settings
- `notification_email` - Email to notify on submission
- `success_message` - Message shown after successful submission
- `redirect_url` - Optional URL to redirect after submission
- `sort_order` - Display order
- `active` - Enable/disable form
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `slug` (unique)
- `active`
- `sort_order`

**Relationships:**
- Has many `form_fields`
- Has many `form_submissions`

---

#### 2. `form_fields`
Defines individual fields within forms.

**Columns:**
- `id` - Primary key
- `form_id` - Foreign key to forms
- `name` - Field name/key (unique per form)
- `label` - Display label
- `type` - Field type (text, email, phone, textarea, number, select, checkbox, radio, file, hidden)
- `placeholder` - Placeholder text
- `default_value` - Default value
- `validation_rules` - JSON validation rules
- `options` - JSON options for select/radio/checkbox
- `help_text` - Help text displayed to user
- `sort_order` - Display order within form
- `required` - Whether field is required
- `active` - Enable/disable field
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `form_id`
- `active`
- `sort_order`
- Unique constraint on (`form_id`, `name`)

**Relationships:**
- Belongs to `forms`
- Has many `form_submission_values`

**Foreign Keys:**
- `form_id` REFERENCES `forms(id)` ON DELETE CASCADE

---

#### 3. `form_submissions`
Stores form submission records.

**Columns:**
- `id` - Primary key
- `form_id` - Foreign key to forms
- `form_slug` - Denormalized slug for faster queries
- `submitted_data` - Complete submission as JSON
- `status` - Submission status (pending, processed, archived)
- `ip_address` - Submitter IP address
- `user_agent` - Submitter user agent
- `submitted_at` - Submission timestamp
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `form_id`
- `form_slug`
- `status`
- `submitted_at`
- `created_at`

**Relationships:**
- Belongs to `forms`
- Has many `form_submission_values`
- Has one `orders` (via foreign key in orders table)

**Foreign Keys:**
- `form_id` REFERENCES `forms(id)` ON DELETE CASCADE

---

#### 4. `form_submission_values`
Normalized storage of individual field values.

**Columns:**
- `id` - Primary key
- `form_submission_id` - Foreign key to form_submissions
- `form_field_id` - Foreign key to form_fields (nullable if field was deleted)
- `field_name` - Denormalized field name
- `field_value` - Field value as text
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- `form_submission_id`
- `form_field_id`
- `field_name`

**Relationships:**
- Belongs to `form_submissions`
- Belongs to `form_fields` (nullable)

**Foreign Keys:**
- `form_submission_id` REFERENCES `form_submissions(id)` ON DELETE CASCADE
- `form_field_id` REFERENCES `form_fields(id)` ON DELETE SET NULL

---

#### 5. `settings_audit`
Audit log for settings changes.

**Columns:**
- `id` - Primary key
- `setting_key` - Setting that was changed
- `old_value` - Previous value
- `new_value` - New value
- `changed_by` - Username or 'system'
- `ip_address` - IP address of change
- `user_agent` - User agent string
- `created_at` - Timestamp

**Indexes:**
- `setting_key`
- `changed_by`
- `created_at`

---

### Modified Tables

#### `orders` (Updated)
Added columns for forms integration:
- `form_submission_id` - Foreign key to form_submissions (nullable)
- `form_slug` - Denormalized form slug (nullable)

**New Indexes:**
- `form_slug`
- `form_submission_id`

**New Foreign Keys:**
- `form_submission_id` REFERENCES `form_submissions(id)` ON DELETE SET NULL

---

## Eloquent Models

### Model Files

Located in `/app/Models/`:

1. **Form.php** - Form model with relationships
2. **FormField.php** - Form field model with type constants
3. **FormSubmission.php** - Submission model with status scopes
4. **FormSubmissionValue.php** - Individual value model
5. **SettingsAudit.php** - Audit log model with helper methods
6. **Order.php** - Updated with formSubmission relationship

### Model Relationships

```
Form
├── hasMany: FormField (fields)
├── hasMany: FormField (activeFields) - only active fields, ordered
└── hasMany: FormSubmission (submissions)

FormField
├── belongsTo: Form (form)
└── hasMany: FormSubmissionValue (submissionValues)

FormSubmission
├── belongsTo: Form (form)
├── hasMany: FormSubmissionValue (values)
└── hasOne: Order (order)

FormSubmissionValue
├── belongsTo: FormSubmission (submission)
└── belongsTo: FormField (field) - nullable

Order
└── belongsTo: FormSubmission (formSubmission)
```

### Usage Examples

```php
// Load Eloquent
require_once 'vendor/autoload.php';
require_once 'bootstrap/eloquent.php';

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Order;

// Get a form with its fields
$contactForm = Form::with('activeFields')->where('slug', 'contact')->first();

// Get all submissions for a form
$submissions = FormSubmission::where('form_slug', 'contact')
    ->with('values')
    ->recent(7)
    ->get();

// Get order with form submission
$order = Order::with('formSubmission.form')->find(123);

// Log a settings change
SettingsAudit::logChange('telegram_chat_id', '123456', '789012', 'admin');

// Query by field value
$phoneSubmissions = FormSubmissionValue::byFieldName('phone')
    ->byFieldValue('+7')
    ->with('submission')
    ->get();
```

---

## Installation & Migration

### Step 1: Apply Schema Changes

**For MySQL/MariaDB:**
```bash
mysql -u USER -p DATABASE < database/schema.sql
```

**For SQLite Test Database:**
```bash
php scripts/setup-test-db.php
```

### Step 2: Seed Forms Data

```bash
php scripts/seed-forms.php
```

This creates:
- Contact form with 6 fields (name, phone, email, telegram, subject, message)
- Order form with 6 fields (name, phone, email, telegram, service, message)

### Step 3: Migrate Existing Orders (Optional)

```bash
# Dry run first to see what will be migrated
php scripts/migrate-orders-to-forms.php --dry-run

# Run actual migration
php scripts/migrate-orders-to-forms.php

# Migrate only specific number of orders (testing)
php scripts/migrate-orders-to-forms.php --limit=10
```

The migration script:
- Creates form_submissions for each existing order
- Populates form_submission_values with individual fields
- Links orders to submissions via form_submission_id
- Preserves original timestamps
- Runs in a database transaction (all or nothing)

### Step 4: Verify Schema

```bash
php database/verify-schema.php
```

Or visit in browser:
```
https://your-site.com/database/verify-schema.php
```

---

## Default Forms

### Contact Form (`slug: contact`)
Used for general inquiries and contact requests.

**Fields:**
1. `name` - Text (required)
2. `phone` - Phone (required)
3. `email` - Email (optional)
4. `telegram` - Text (optional)
5. `subject` - Text (optional)
6. `message` - Textarea (required)

### Order Form (`slug: order`)
Used for order submissions with calculator data.

**Fields:**
1. `name` - Text (required)
2. `phone` - Phone (required)
3. `email` - Email (optional)
4. `telegram` - Text (optional)
5. `service` - Text (required)
6. `message` - Textarea (optional)

---

## Data Flow

### New Submission Flow

1. User submits form on website
2. API endpoint validates data
3. Creates `FormSubmission` record with JSON data
4. Creates individual `FormSubmissionValue` records
5. If order-related, creates `Order` record linked to submission
6. Sends notifications (Telegram, email)

### Legacy Order Flow (Backward Compatible)

1. Existing code creates `Order` directly
2. Works as before without form_submission_id
3. Can be migrated later using migration script

---

## Querying Data

### Get All Submissions for a Form

```php
$submissions = FormSubmission::where('form_slug', 'contact')
    ->orderBy('submitted_at', 'desc')
    ->get();
```

### Search by Field Value

```php
// Find submissions with specific phone number
$phone = '+7 900 123 4567';
$submissions = FormSubmissionValue::where('field_name', 'phone')
    ->where('field_value', 'LIKE', '%' . $phone . '%')
    ->with('submission.form')
    ->get()
    ->pluck('submission')
    ->unique();
```

### Get Recent Pending Submissions

```php
$pending = FormSubmission::pending()
    ->recent(7)
    ->with('form', 'values')
    ->get();
```

### Audit Trail for Settings

```php
// Get all changes to a specific setting
$changes = SettingsAudit::where('setting_key', 'telegram_chat_id')
    ->orderBy('created_at', 'desc')
    ->get();

// Get changes by admin
$adminChanges = SettingsAudit::where('changed_by', 'admin')
    ->recent(30)
    ->get();
```

---

## Validation Rules Format

Validation rules are stored as JSON in the `validation_rules` column:

```json
{
    "required": true,
    "minLength": 2,
    "maxLength": 255,
    "pattern": "^[a-zA-Z\\s]+$",
    "email": true
}
```

**Common Rules:**
- `required` - Field must have value
- `minLength` - Minimum string length
- `maxLength` - Maximum string length
- `min` - Minimum numeric value
- `max` - Maximum numeric value
- `pattern` - Regex pattern
- `email` - Valid email format
- `phone` - Valid phone format

---

## Field Types

Supported field types (ENUM in database):

- `text` - Single line text input
- `email` - Email input with validation
- `phone` - Phone number input
- `textarea` - Multi-line text input
- `number` - Numeric input
- `select` - Dropdown selection
- `checkbox` - Multiple checkboxes
- `radio` - Radio button group
- `file` - File upload (future)
- `hidden` - Hidden field

---

## Settings JSON Format

Form-level settings stored in `forms.settings`:

```json
{
    "enable_telegram_notification": true,
    "enable_email_notification": false,
    "rate_limit": "10/hour",
    "require_calculator_data": false
}
```

---

## Troubleshooting

### Migration Issues

**Problem:** Orders already have form_submission_id
```bash
# All orders already migrated
✓ No orders to migrate. All orders are already linked to form submissions.
```

**Problem:** Forms not found
```bash
# Run seed script first
php scripts/seed-forms.php
```

### Schema Verification

If verification fails, check:
1. Schema file was applied: `mysql -u USER -p DATABASE < database/schema.sql`
2. Foreign key constraints are enabled
3. Database user has sufficient privileges

### Model Not Found

Ensure Eloquent is bootstrapped:
```php
require_once 'vendor/autoload.php';
require_once 'bootstrap/eloquent.php';
```

---

## Future Enhancements

Potential additions to the forms system:

1. **Form Builder UI** - Admin interface to create/edit forms
2. **Conditional Fields** - Show/hide fields based on other field values
3. **File Uploads** - Support for file field type
4. **Email Templates** - Customizable notification templates
5. **Webhooks** - Send submission data to external services
6. **Form Analytics** - Track submission rates, conversion, abandonment
7. **A/B Testing** - Test different form variants
8. **Multi-step Forms** - Break long forms into steps
9. **Auto-save Drafts** - Save incomplete submissions
10. **SPAM Protection** - Integrate with reCAPTCHA or similar

---

## Version History

- **v3.0** (January 2025) - Initial forms system implementation
  - Added 5 new tables (forms, form_fields, form_submissions, form_submission_values, settings_audit)
  - Extended orders table with form integration columns
  - Created 5 Eloquent models with relationships
  - Migration and seed scripts
  - Full documentation

---

## Support

For questions or issues:
1. Check this documentation
2. Run verification script: `php database/verify-schema.php`
3. Review migration logs
4. Check database/README.md for general database info
