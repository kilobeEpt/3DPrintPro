# Forms API Documentation

## Overview

The Forms API provides endpoints for managing dynamic forms and processing submissions. It supports both public form submission and admin CRUD operations.

## Endpoints

### `/api/forms.php`

Manages form definitions and configurations.

#### GET - Retrieve Form(s)

**Public: Get form by slug**
```http
GET /api/forms.php?slug=contact
```

Response:
```json
{
  "success": true,
  "data": {
    "form": {
      "id": 1,
      "name": "Contact Form",
      "slug": "contact",
      "description": "General contact form",
      "settings": {},
      "notification_email": "admin@example.com",
      "success_message": "Thank you for your message!",
      "redirect_url": null,
      "fields": [
        {
          "id": 1,
          "name": "name",
          "label": "Full Name",
          "type": "text",
          "placeholder": "Enter your name",
          "default_value": null,
          "validation_rules": {
            "minLength": 2,
            "maxLength": 100
          },
          "options": null,
          "help_text": null,
          "sort_order": 1,
          "required": true
        }
      ]
    }
  }
}
```

**Admin: List all forms**
```http
GET /api/forms.php?limit=20&offset=0&active=1
Authorization: Bearer <admin-session-token>
```

Query Parameters:
- `limit` (optional): Number of records (default: 20)
- `offset` (optional): Pagination offset (default: 0)
- `active` (optional): Filter by active status (1 or 0)
- `search` (optional): Search in name, slug, description

Response:
```json
{
  "success": true,
  "data": {
    "forms": [
      {
        "id": 1,
        "name": "Contact Form",
        "slug": "contact",
        "description": "General contact form",
        "settings": {},
        "notification_email": "admin@example.com",
        "success_message": "Thank you!",
        "redirect_url": null,
        "sort_order": 1,
        "active": true,
        "created_at": "2024-01-01T00:00:00+00:00",
        "updated_at": "2024-01-01T00:00:00+00:00",
        "fields_count": 6
      }
    ]
  },
  "meta": {
    "total": 2,
    "limit": 20,
    "offset": 0,
    "has_more": false
  }
}
```

**Admin: Get single form by ID**
```http
GET /api/forms.php?id=1
Authorization: Bearer <admin-session-token>
```

#### POST - Create Form

**Admin only**

```http
POST /api/forms.php
Authorization: Bearer <admin-session-token>
X-CSRF-Token: <csrf-token>
Content-Type: application/json

{
  "name": "Survey Form",
  "slug": "survey",
  "description": "Customer satisfaction survey",
  "settings": {
    "allow_multiple_submissions": false
  },
  "notification_email": "surveys@example.com",
  "success_message": "Thank you for your feedback!",
  "redirect_url": "/thank-you",
  "sort_order": 10,
  "active": true
}
```

Response:
```json
{
  "success": true,
  "data": {
    "form_id": 3,
    "slug": "survey",
    "message": "Form created successfully"
  }
}
```

#### PUT - Update Form

**Admin only**

```http
PUT /api/forms.php
Authorization: Bearer <admin-session-token>
X-CSRF-Token: <csrf-token>
Content-Type: application/json

{
  "id": 3,
  "name": "Customer Survey",
  "active": true
}
```

#### DELETE - Delete Form

**Admin only**

```http
DELETE /api/forms.php?id=3&force=1
Authorization: Bearer <admin-session-token>
X-CSRF-Token: <csrf-token>
```

Query Parameters:
- `id` (required): Form ID
- `force` (optional): Set to 1 to delete form with submissions

---

### `/api/form-submissions.php`

Handles form submission processing and review.

#### POST - Submit Form

**Public endpoint** (rate-limited)

```http
POST /api/form-submissions.php
Content-Type: application/json

{
  "form_slug": "contact",
  "data": {
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "service": "Consultation",
    "message": "I need help with..."
  }
}
```

Success Response (201):
```json
{
  "success": true,
  "data": {
    "submission_id": 123,
    "order_id": 456,
    "order_number": "ORD-20240101-ABC123",
    "message": "Form submitted successfully"
  },
  "meta": {
    "redirect_url": "/thank-you"
  }
}
```

Validation Error Response (422):
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "name": "Name is required",
    "email": "Email must be a valid email address",
    "phone": "Phone must be a valid phone number"
  }
}
```

#### GET - List Submissions

**Admin only**

```http
GET /api/form-submissions.php?form_slug=contact&status=pending&limit=50&offset=0
Authorization: Bearer <admin-session-token>
```

Query Parameters:
- `form_id` (optional): Filter by form ID
- `form_slug` (optional): Filter by form slug
- `status` (optional): Filter by status (pending, processed, archived)
- `from_date` (optional): Filter from date (YYYY-MM-DD)
- `to_date` (optional): Filter to date (YYYY-MM-DD)
- `limit` (optional): Number of records (default: 50)
- `offset` (optional): Pagination offset (default: 0)

Response:
```json
{
  "success": true,
  "data": {
    "submissions": [
      {
        "id": 123,
        "form_id": 1,
        "form_slug": "contact",
        "form_name": "Contact Form",
        "status": "pending",
        "submitted_at": "2024-01-01T12:00:00+00:00",
        "order_id": 456,
        "order_number": "ORD-20240101-ABC123",
        "summary": "John Doe • john@example.com • +1234567890"
      }
    ]
  },
  "meta": {
    "total": 150,
    "limit": 50,
    "offset": 0,
    "has_more": true
  }
}
```

**Admin: Get single submission**
```http
GET /api/form-submissions.php?id=123
Authorization: Bearer <admin-session-token>
```

Response:
```json
{
  "success": true,
  "data": {
    "submission": {
      "id": 123,
      "form_id": 1,
      "form_slug": "contact",
      "form_name": "Contact Form",
      "submitted_data": {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+1234567890",
        "message": "..."
      },
      "status": "pending",
      "ip_address": "192.168.1.1",
      "user_agent": "Mozilla/5.0...",
      "submitted_at": "2024-01-01T12:00:00+00:00",
      "created_at": "2024-01-01T12:00:00+00:00",
      "updated_at": "2024-01-01T12:00:00+00:00",
      "values": [
        {
          "id": 1,
          "field_name": "name",
          "field_label": "Full Name",
          "field_value": "John Doe"
        }
      ],
      "order": {
        "id": 456,
        "order_number": "ORD-20240101-ABC123",
        "status": "new"
      }
    }
  }
}
```

#### PUT - Update Submission Status

**Admin only**

```http
PUT /api/form-submissions.php
Authorization: Bearer <admin-session-token>
X-CSRF-Token: <csrf-token>
Content-Type: application/json

{
  "id": 123,
  "status": "processed"
}
```

Valid statuses: `pending`, `processed`, `archived`

#### DELETE - Delete Submission

**Admin only**

```http
DELETE /api/form-submissions.php?id=123
Authorization: Bearer <admin-session-token>
X-CSRF-Token: <csrf-token>
```

---

## Field Types

Supported field types with automatic validation:

- `text` - Plain text input
- `email` - Email address with validation
- `phone` - Phone number with validation
- `textarea` - Multi-line text
- `number` - Numeric input
- `select` - Dropdown selection
- `checkbox` - Checkbox input
- `radio` - Radio button
- `file` - File upload (future)
- `hidden` - Hidden field
- `url` - URL with validation

## Validation Rules

Custom validation rules can be defined per field:

```json
{
  "validation_rules": {
    "min": 10,
    "max": 100,
    "minLength": 5,
    "maxLength": 200,
    "pattern": "^[A-Z][a-z]+$"
  }
}
```

## Rate Limiting

Public endpoints are rate-limited:
- Form submissions: 10 per hour per IP
- Form configuration retrieval: 60 per hour per IP

## Caching

Public form configurations include cache headers:
- `Cache-Control: public, max-age=300` (5 minutes)

## Integration with Orders

Forms with slug `order` or `contact` automatically create Order records:
- Generates unique order number
- Sends Telegram notifications
- Links submission to order via `form_submission_id`

## Error Handling

All endpoints return structured errors:

**400 Bad Request** - Invalid input
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "field_name": "Error message"
  }
}
```

**401 Unauthorized** - Authentication required
```json
{
  "success": false,
  "error": "Unauthorized access"
}
```

**404 Not Found** - Resource not found
```json
{
  "success": false,
  "error": "Form not found"
}
```

**422 Unprocessable Entity** - Validation failed
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "name": "Name is required",
    "email": "Email must be a valid email address"
  }
}
```

**429 Too Many Requests** - Rate limit exceeded
```json
{
  "success": false,
  "error": "Rate limit exceeded",
  "meta": {
    "retry_after": 3600
  }
}
```

**500 Internal Server Error** - Server error
```json
{
  "success": false,
  "error": "Internal server error"
}
```

## Testing

Run the end-to-end test script:

```bash
php scripts/test-forms-api.php
```

This validates:
- Form loading
- Validation logic
- Submission processing
- Database persistence
- Model relationships
