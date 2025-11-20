# Forms System Guide

**Version:** 4.0  
**Last Updated:** January 2025  
**Feature Status:** Production Ready ✅

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Form Builder](#form-builder)
4. [Field Types](#field-types)
5. [Validation](#validation)
6. [Conditional Logic](#conditional-logic)
7. [Submissions Management](#submissions-management)
8. [Notifications](#notifications)
9. [Calculator Integration](#calculator-integration)
10. [API Reference](#api-reference)
11. [Frontend Integration](#frontend-integration)
12. [Best Practices](#best-practices)

---

## Overview

The Forms System provides a powerful, drag-and-drop form builder for creating custom forms with advanced features:

- **Visual Form Builder** - Drag-and-drop field ordering
- **10 Field Types** - Text, email, phone, number, textarea, select, radio, checkbox, file, hidden
- **Dynamic Validation** - Per-field validation rules with real-time feedback
- **Conditional Logic** - Show/hide fields based on other field values
- **Notification System** - Telegram and email notifications per form
- **Calculator Integration** - Map calculator outputs to form fields
- **Submission Management** - Filter, search, and bulk actions
- **Orders Integration** - Automatic order creation from submissions

---

## Architecture

### Database Tables

```
forms (form definitions)
├── form_fields (field configurations)
├── form_submissions (submission records)
│   └── form_submission_values (individual field values)
└── orders (linked order records)
```

### Models

- **Form** - `app/Models/Form.php`
- **FormField** - `app/Models/FormField.php`
- **FormSubmission** - `app/Models/FormSubmission.php`
- **FormSubmissionValue** - `app/Models/FormSubmissionValue.php`

### Controllers

- **FormController** - `/api/forms.php` (CRUD operations)
- **FormFieldController** - `/api/form-fields.php` (field management)
- **FormSubmissionController** - `/api/form-submissions.php` (submission handling)

### Services

- **FormService** - `api/helpers/form_service.php` (form loading, validation, submission processing)

---

## Form Builder

### Creating a Form

**Admin UI: `/admin/forms.php`**

1. Click "Create Form" button
2. Enter form details:
   - **Name:** Display name (e.g., "Contact Form")
   - **Slug:** URL-friendly identifier (e.g., "contact")
   - **Description:** Internal description
   - **Success Message:** Shown after successful submission
   - **Redirect URL:** (Optional) Redirect after submission

3. Click "Save Form"

### Adding Fields

1. Open form in builder
2. Click "Add Field" or drag from field palette
3. Configure field properties:
   - **Name:** Field identifier (e.g., "email")
   - **Label:** User-visible label (e.g., "Email Address")
   - **Type:** Select from 10 types
   - **Placeholder:** (Optional) Placeholder text
   - **Help Text:** (Optional) Hint text below field
   - **Required:** Toggle required status
   - **Validation Rules:** Click to configure

4. Click "Save Field"

### Field Ordering

**Drag-and-Drop:**
1. Hover over field in list
2. Click and hold drag handle (⋮⋮)
3. Drag to new position
4. Release to drop
5. Order saves automatically

**Manual Ordering:**
1. Click edit on field
2. Change "Sort Order" number
3. Save field
4. Fields reorder based on sort_order

### Form Settings

**Notification Settings:**
```json
{
  "telegram_enabled": true,
  "telegram_chat_id": "-123456789",
  "email_enabled": true,
  "email_recipients": "admin@example.com,manager@example.com",
  "email_template": "order"
}
```

**Calculator Mapping:**
```json
{
  "calculator_mapping": {
    "amount": "calculator.totalCost",
    "material": "calculator.material",
    "weight": "calculator.weight"
  }
}
```

**Conditional Logic:**
```json
{
  "conditional_logic": {
    "conditions": [
      {
        "field": "service_type",
        "operator": "equals",
        "value": "3d_printing"
      }
    ],
    "action": "show"
  }
}
```

---

## Field Types

### 1. Text

**Type:** `text`  
**Use Case:** Short text input (name, city, etc.)

```json
{
  "name": "full_name",
  "label": "Full Name",
  "type": "text",
  "placeholder": "Enter your full name",
  "validation_rules": {
    "required": true,
    "minLength": 2,
    "maxLength": 100
  }
}
```

### 2. Email

**Type:** `email`  
**Use Case:** Email addresses with automatic validation

```json
{
  "name": "email",
  "label": "Email Address",
  "type": "email",
  "placeholder": "you@example.com",
  "validation_rules": {
    "required": true,
    "pattern": "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$"
  }
}
```

### 3. Phone

**Type:** `phone`  
**Use Case:** Phone numbers

```json
{
  "name": "phone",
  "label": "Phone Number",
  "type": "phone",
  "placeholder": "+7 (900) 123-45-67",
  "validation_rules": {
    "required": true,
    "pattern": "^\\+?[0-9\\s\\-\\(\\)]+$"
  }
}
```

### 4. Number

**Type:** `number`  
**Use Case:** Numeric input (quantity, age, etc.)

```json
{
  "name": "quantity",
  "label": "Quantity",
  "type": "number",
  "validation_rules": {
    "required": true,
    "min": 1,
    "max": 100
  }
}
```

### 5. Textarea

**Type:** `textarea`  
**Use Case:** Multi-line text (messages, descriptions)

```json
{
  "name": "message",
  "label": "Message",
  "type": "textarea",
  "placeholder": "Enter your message here...",
  "validation_rules": {
    "required": true,
    "minLength": 10,
    "maxLength": 5000
  }
}
```

### 6. Select

**Type:** `select`  
**Use Case:** Dropdown selection from predefined options

```json
{
  "name": "service",
  "label": "Select Service",
  "type": "select",
  "options": [
    {"value": "3d_printing", "label": "3D Printing"},
    {"value": "design", "label": "Design Services"},
    {"value": "consultation", "label": "Consultation"}
  ],
  "validation_rules": {
    "required": true
  }
}
```

### 7. Radio

**Type:** `radio`  
**Use Case:** Single selection from visible options

```json
{
  "name": "priority",
  "label": "Priority",
  "type": "radio",
  "options": [
    {"value": "standard", "label": "Standard"},
    {"value": "urgent", "label": "Urgent"}
  ],
  "default_value": "standard"
}
```

### 8. Checkbox

**Type:** `checkbox`  
**Use Case:** Boolean yes/no, or multiple selections

```json
{
  "name": "agree_terms",
  "label": "I agree to the terms and conditions",
  "type": "checkbox",
  "validation_rules": {
    "required": true
  }
}
```

### 9. File

**Type:** `file`  
**Use Case:** File uploads

```json
{
  "name": "attachment",
  "label": "Upload File",
  "type": "file",
  "validation_rules": {
    "maxSize": 5242880,
    "allowedTypes": ["image/jpeg", "image/png", "application/pdf"]
  }
}
```

### 10. Hidden

**Type:** `hidden`  
**Use Case:** Hidden fields for tracking, calculator data, etc.

```json
{
  "name": "utm_source",
  "type": "hidden",
  "default_value": "website"
}
```

---

## Validation

### Built-In Validation Rules

| Rule | Description | Example |
|------|-------------|---------|
| `required` | Field must not be empty | `{"required": true}` |
| `minLength` | Minimum string length | `{"minLength": 5}` |
| `maxLength` | Maximum string length | `{"maxLength": 100}` |
| `min` | Minimum numeric value | `{"min": 1}` |
| `max` | Maximum numeric value | `{"max": 100}` |
| `pattern` | Regex pattern match | `{"pattern": "^[A-Z]"}` |
| `email` | Valid email format | Automatic for type=email |
| `phone` | Valid phone format | Automatic for type=phone |
| `url` | Valid URL format | Automatic for type=url |

### Custom Validation

**Complex Rules:**
```json
{
  "validation_rules": {
    "required": true,
    "minLength": 8,
    "maxLength": 20,
    "pattern": "^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d).+$",
    "custom_message": "Password must contain uppercase, lowercase, and number"
  }
}
```

### Validation Messages

**Default Messages:**
- `required`: "This field is required"
- `minLength`: "Must be at least X characters"
- `maxLength`: "Must not exceed X characters"
- `min`: "Must be at least X"
- `max`: "Must not exceed X"
- `pattern`: "Invalid format"
- `email`: "Must be a valid email address"
- `phone`: "Must be a valid phone number"

**Custom Messages:**
```json
{
  "validation_rules": {
    "required": true,
    "custom_message": "Please provide your full name"
  }
}
```

---

## Conditional Logic

### Operators

1. **equals** - Field value equals specific value
2. **not_equals** - Field value does not equal specific value
3. **contains** - Field value contains substring
4. **not_contains** - Field value does not contain substring
5. **empty** - Field is empty
6. **not_empty** - Field is not empty

### Single Condition

**Show field if service_type is "3d_printing":**

```json
{
  "field_id": 5,
  "conditional_logic": {
    "conditions": [
      {
        "field": "service_type",
        "operator": "equals",
        "value": "3d_printing"
      }
    ],
    "action": "show"
  }
}
```

### Multiple Conditions (AND)

**Show field if service is "3d_printing" AND priority is "urgent":**

```json
{
  "conditional_logic": {
    "conditions": [
      {
        "field": "service_type",
        "operator": "equals",
        "value": "3d_printing"
      },
      {
        "field": "priority",
        "operator": "equals",
        "value": "urgent"
      }
    ],
    "logic": "AND",
    "action": "show"
  }
}
```

### Multiple Conditions (OR)

```json
{
  "conditional_logic": {
    "conditions": [
      {
        "field": "country",
        "operator": "equals",
        "value": "Russia"
      },
      {
        "field": "country",
        "operator": "equals",
        "value": "Belarus"
      }
    ],
    "logic": "OR",
    "action": "show"
  }
}
```

---

## Submissions Management

### Viewing Submissions

**Admin UI: `/admin/submissions.php`**

**Features:**
- List view with pagination (20/50/100 per page)
- Status badges (pending, processed, archived)
- Linked order information
- Quick actions (view, change status, delete)

### Filtering

**Available Filters:**
- **Form:** Filter by specific form
- **Status:** pending, processed, archived
- **Date Range:** From/To dates
- **Search:** Name, email, phone, order number

**Example Filter:**
```
Form: Contact Form
Status: Pending
Date From: 2024-01-01
Date To: 2024-01-31
Search: john@example.com
```

### Bulk Actions

**Select Multiple Submissions:**
1. Check checkboxes for desired submissions
2. Click "Bulk Actions" dropdown
3. Select action:
   - Mark as Processed
   - Mark as Archived
   - Delete Selected
4. Confirm action
5. Verify success message

### Submission Details

**View Full Submission:**
1. Click submission in list
2. View modal shows:
   - All field values
   - Submission metadata (IP, User Agent, timestamp)
   - Linked order (if any)
   - Status history

---

## Notifications

### Telegram Notifications

**Per-Form Configuration:**

```json
{
  "telegram_enabled": true,
  "telegram_chat_id": "-123456789"
}
```

**Message Format:**
```
🔔 New Form Submission: Contact Form

📝 Order #: ORD-20240101-ABC123
👤 Name: Ivan Petrov
📧 Email: ivan@example.com
📞 Phone: +79001234567
💬 Message: I need 3D printing services...

🕐 Submitted: 2024-01-01 12:30:45
```

**Setup Steps:**
1. Create Telegram bot with @BotFather
2. Get bot token
3. Add bot to chat/channel
4. Get chat ID
5. Configure in form settings or global settings

### Email Notifications

**Per-Form Configuration:**

```json
{
  "email_enabled": true,
  "email_recipients": "admin@example.com,manager@example.com",
  "email_template": "order"
}
```

**Available Templates:**
- **default** - Basic submission notification
- **order** - Order-specific template with calculator data
- **contact** - Contact form template
- **custom** - Custom HTML template

**Email Headers:**
```
From: 3D Print Pro <noreply@3dprint-omsk.ru>
To: admin@example.com
Subject: New Form Submission: Contact Form
```

**Setup Steps:**
1. Configure SMTP in Global Settings
2. Test email with "Send Test Email" button
3. Enable per-form in form settings
4. Add recipient email addresses (comma-separated)

---

## Calculator Integration

### Mapping Calculator Outputs

**Configuration:**

```json
{
  "calculator_mapping": {
    "amount": "calculator.totalCost",
    "material": "calculator.material",
    "weight": "calculator.weight",
    "quality": "calculator.quality",
    "delivery_days": "calculator.deliveryDays"
  }
}
```

**How It Works:**

1. User completes calculator on public site
2. Calculator data stored in JavaScript object
3. User submits form
4. Form submission includes calculator data
5. Mapped fields automatically populated
6. Order created with calculator_data JSON

**Example Calculator Data:**

```json
{
  "material": "PLA",
  "weight": 50,
  "quality": "high",
  "services": ["design", "print"],
  "quantity": 3,
  "totalCost": 3750,
  "deliveryDays": 5
}
```

**Accessing Nested Values:**

Use dot notation for nested paths:
- `calculator.totalCost` → `3750`
- `calculator.material` → `"PLA"`
- `calculator.services[0]` → `"design"`

---

## API Reference

### Public Endpoints

#### Get Form by Slug

```http
GET /api/forms.php?slug=contact
```

**Response:**
```json
{
  "success": true,
  "data": {
    "form": {
      "id": 1,
      "name": "Contact Form",
      "slug": "contact",
      "success_message": "Thank you!",
      "fields": [...]
    }
  }
}
```

#### Submit Form

```http
POST /api/form-submissions.php
Content-Type: application/json

{
  "form_slug": "contact",
  "data": {
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "message": "Hello..."
  }
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "submission_id": 123,
    "order_id": 456,
    "order_number": "ORD-20240101-ABC123",
    "message": "Form submitted successfully"
  }
}
```

### Admin Endpoints

#### List Forms

```http
GET /api/forms.php?limit=20&offset=0
Authorization: Bearer <session-token>
```

#### Create Form

```http
POST /api/forms.php
Authorization: Bearer <session-token>
X-CSRF-Token: <csrf-token>

{
  "name": "New Form",
  "slug": "new-form",
  "description": "Description",
  "active": true
}
```

#### List Submissions

```http
GET /api/form-submissions.php?form_slug=contact&status=pending
Authorization: Bearer <session-token>
```

See [API_FORMS.md](API_FORMS.md) for complete API documentation.

---

## Frontend Integration

### Load Form

```javascript
// Load form by slug
fetch('/api/forms.php?slug=contact')
  .then(response => response.json())
  .then(data => {
    const form = data.data.form;
    renderForm(form);
  });
```

### Render Fields

```javascript
function renderForm(form) {
  const container = document.getElementById('form-container');
  
  form.fields.forEach(field => {
    const fieldHtml = createFieldHtml(field);
    container.innerHTML += fieldHtml;
  });
}

function createFieldHtml(field) {
  switch (field.type) {
    case 'text':
      return `
        <div class="form-group">
          <label for="${field.name}">${field.label}${field.required ? '*' : ''}</label>
          <input 
            type="text" 
            id="${field.name}" 
            name="${field.name}"
            placeholder="${field.placeholder || ''}"
            ${field.required ? 'required' : ''}
          />
        </div>
      `;
    case 'email':
      return `...`;
    // ... other types
  }
}
```

### Submit Form

```javascript
async function submitForm(formData) {
  const response = await fetch('/api/form-submissions.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      form_slug: 'contact',
      data: formData
    })
  });
  
  const result = await response.json();
  
  if (result.success) {
    showSuccessMessage(result.data.message);
    if (result.meta?.redirect_url) {
      window.location.href = result.meta.redirect_url;
    }
  } else {
    showErrors(result.errors);
  }
}
```

### Client-Side Validation

```javascript
function validateField(field, value) {
  const rules = JSON.parse(field.validation_rules || '{}');
  
  if (rules.required && !value) {
    return 'This field is required';
  }
  
  if (rules.minLength && value.length < rules.minLength) {
    return `Must be at least ${rules.minLength} characters`;
  }
  
  if (rules.pattern) {
    const regex = new RegExp(rules.pattern);
    if (!regex.test(value)) {
      return rules.custom_message || 'Invalid format';
    }
  }
  
  return null; // Valid
}
```

---

## Best Practices

### Form Design

1. **Keep forms short** - Only ask for essential information
2. **Group related fields** - Use logical sections
3. **Provide clear labels** - Descriptive, concise labels
4. **Use appropriate field types** - Email for emails, phone for phones
5. **Add help text** - Clarify requirements for complex fields
6. **Show progress** - For multi-step forms, show current step

### Validation

1. **Validate early** - Client-side validation for immediate feedback
2. **Validate thoroughly** - Server-side validation for security
3. **Provide clear messages** - Specific error messages
4. **Highlight errors** - Visual indicators on invalid fields
5. **Preserve data** - Don't clear valid fields on error

### Notifications

1. **Test notifications** - Use test buttons before going live
2. **Include key info** - Name, email, phone, order number
3. **Format clearly** - Use emojis and line breaks in Telegram
4. **Set appropriate recipients** - Route to correct teams
5. **Monitor delivery** - Check logs for failed notifications

### Performance

1. **Cache form configurations** - 5-minute cache on public API
2. **Lazy load forms** - Load forms on demand, not on page load
3. **Optimize submissions** - Async processing for slow operations
4. **Index database** - Index foreign keys and frequently queried fields

### Security

1. **Validate all inputs** - Server-side validation required
2. **Sanitize output** - Use `htmlspecialchars()` when displaying data
3. **Rate limit submissions** - Prevent spam (10 submissions/hour/IP)
4. **CSRF protection** - Required for all state-changing operations
5. **Secure file uploads** - Validate file types and sizes

---

## Troubleshooting

### Form Not Loading

**Symptoms:** Form doesn't appear on public page

**Solutions:**
1. Check form `active` status in admin
2. Verify form slug correct in API call
3. Check browser console for errors
4. Verify API endpoint accessible

### Validation Errors

**Symptoms:** Form submission rejected with validation errors

**Solutions:**
1. Check required fields filled
2. Verify field formats (email, phone)
3. Check minLength/maxLength constraints
4. Review validation rules in admin

### Notifications Not Sending

**Symptoms:** No Telegram/email received

**Telegram:**
1. Verify bot token correct
2. Check chat ID correct (include `-` for groups)
3. Ensure bot added to chat
4. Test with "Test Telegram" button

**Email:**
1. Check SMTP settings in Global Settings
2. Verify recipient email correct
3. Test with "Test Email" button
4. Check spam folder

### Submissions Not Creating Orders

**Symptoms:** Submission created but no order

**Solutions:**
1. Check form slug is "order" or "contact"
2. Verify `form_submission_id` populated
3. Check order creation logic in FormService
4. Review error logs for exceptions

---

## Migration from Legacy Forms

### Backup Existing Data

```bash
php database/backup.php
```

### Run Migration Script

```bash
php scripts/migrate-orders-to-forms.php --dry-run
```

Review output, then run without `--dry-run`:

```bash
php scripts/migrate-orders-to-forms.php
```

### Update Frontend

Replace legacy form submission code with new Forms API:

**Before:**
```javascript
fetch('/api/orders.php', {
  method: 'POST',
  body: formData
});
```

**After:**
```javascript
fetch('/api/form-submissions.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    form_slug: 'contact',
    data: formData
  })
});
```

---

## Support

**Documentation:**
- [API_FORMS.md](API_FORMS.md) - Complete API reference
- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) - Admin panel usage

**Testing:**
- Run smoke tests: `php scripts/form-api-smoke.php`
- Run unit tests: `vendor/bin/phpunit tests/Integration/FormBuilderTest.php`

---

**Document Version:** 1.0  
**Last Updated:** January 2025  
**Status:** Production Ready ✅
