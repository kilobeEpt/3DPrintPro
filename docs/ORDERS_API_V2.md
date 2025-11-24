# Orders API v2.0

Complete documentation for the enhanced Orders API with status history, notes, filtering, and exports.

## Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Endpoints](#endpoints)
  - [List Orders](#list-orders)
  - [Get Order](#get-order)
  - [Create Order](#create-order)
  - [Update Order](#update-order)
  - [Update Status](#update-status)
  - [Archive/Unarchive](#archiveunarchive)
  - [Manage Notes](#manage-notes)
  - [Export Orders](#export-orders)
  - [Delete Order](#delete-order)
- [Models](#models)
- [Examples](#examples)

## Overview

The Orders API v2.0 provides comprehensive order management with:

- **Advanced Filtering**: Filter by status, type, date range, search terms, form slug, and archive status
- **Status History**: Automatic tracking of all status changes with admin attribution
- **Internal Notes**: Add, update, and delete notes on orders
- **Exports**: Generate CSV and PDF exports with signed URLs
- **Archiving**: Archive completed orders without deletion
- **Full RBAC**: Role-based access control with audit logging

## Authentication

Most endpoints require admin authentication:

```http
GET /api/orders.php?id=123
X-CSRF-Token: <token>
Cookie: PHPSESSID=<session>
```

Public order creation does not require authentication.

## Endpoints

### List Orders

Retrieve orders with advanced filtering and pagination.

**Request:**
```http
GET /api/orders.php?status=new&type=order&search=John&date_from=2025-01-01&archived=false&limit=50&offset=0&sort_by=created_at&sort_order=desc&with_relations=true
```

**Query Parameters:**
- `status` (optional): Filter by status (`new`, `processing`, `completed`, `cancelled`)
- `type` (optional): Filter by type (`order`, `contact`)
- `form_slug` (optional): Filter by form slug
- `search` (optional): Search in name, email, phone, order_number
- `date_from` (optional): Start date (YYYY-MM-DD HH:MM:SS)
- `date_to` (optional): End date (YYYY-MM-DD HH:MM:SS)
- `archived` (optional): Filter archived (`true`, `false`, omit for all)
- `limit` (optional): Results per page (default: 100)
- `offset` (optional): Pagination offset
- `sort_by` (optional): Sort field (`created_at`, `updated_at`, `amount`, `status`, `order_number`)
- `sort_order` (optional): Sort direction (`asc`, `desc`)
- `with_relations` (optional): Include relations (`true`, `false`)

**Response:**
```json
{
  "data": {
    "orders": [
      {
        "id": 123,
        "order_number": "ORD-2025-001",
        "type": "order",
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+79991234567",
        "status": "new",
        "amount": "1500.00",
        "archived_at": null,
        "created_at": "2025-01-15T10:30:00",
        "updated_at": "2025-01-15T10:30:00",
        "status_history": [...],
        "notes": [...]
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

### Get Order

Retrieve a single order with full details.

**Request:**
```http
GET /api/orders.php?id=123
```

**Response:**
```json
{
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-2025-001",
      "type": "order",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+79991234567",
      "telegram": "@johndoe",
      "service": "3D Printing",
      "subject": "Custom part",
      "message": "Need a custom part printed",
      "amount": "1500.00",
      "calculator_data": {...},
      "form_submission_id": 456,
      "form_slug": "order",
      "status": "processing",
      "archived_at": null,
      "telegram_sent": true,
      "telegram_error": null,
      "created_at": "2025-01-15T10:30:00",
      "updated_at": "2025-01-15T11:00:00",
      "status_history": [
        {
          "id": 1,
          "order_id": 123,
          "old_status": null,
          "new_status": "new",
          "changed_by": null,
          "comment": "Order created",
          "created_at": "2025-01-15T10:30:00",
          "changed_by": null
        },
        {
          "id": 2,
          "order_id": 123,
          "old_status": "new",
          "new_status": "processing",
          "changed_by": 1,
          "comment": "Started processing",
          "created_at": "2025-01-15T11:00:00",
          "changed_by": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
          }
        }
      ],
      "notes": [
        {
          "id": 1,
          "order_id": 123,
          "note": "Customer called to confirm details",
          "created_by": 1,
          "created_at": "2025-01-15T10:45:00",
          "updated_at": "2025-01-15T10:45:00",
          "created_by": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
          }
        }
      ],
      "form_submission": {...}
    }
  }
}
```

### Create Order

Create a new order (public endpoint).

**Request:**
```http
POST /api/orders.php
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+79991234567",
  "telegram": "@johndoe",
  "service": "3D Printing",
  "subject": "Custom part",
  "message": "Need a custom part printed",
  "calculatorData": {...}
}
```

**Response:**
```json
{
  "data": {
    "order_id": 123,
    "order_number": "ORD-2025-001",
    "submission_id": 456,
    "message": "Order submitted successfully"
  },
  "meta": {
    "telegram_sent": true,
    "telegram_error": null
  }
}
```

### Update Order

Update order fields (admin only).

**Request:**
```http
PUT /api/orders.php
Content-Type: application/json
X-CSRF-Token: <token>

{
  "id": 123,
  "status": "processing",
  "status_comment": "Started working on the order",
  "amount": 1600.00
}
```

**Response:**
```json
{
  "data": {
    "message": "Order updated successfully",
    "order_id": 123,
    "status_changed": true
  }
}
```

### Update Status

Update order status with history tracking (admin only).

**Request:**
```http
PATCH /api/orders.php?action=status&id=123
Content-Type: application/json
X-CSRF-Token: <token>

{
  "status": "completed",
  "comment": "Order fulfilled and shipped"
}
```

**Response:**
```json
{
  "data": {
    "message": "Order status updated successfully",
    "order_id": 123,
    "old_status": "processing",
    "new_status": "completed"
  }
}
```

### Archive/Unarchive

Archive or unarchive an order (admin only).

**Archive:**
```http
PATCH /api/orders.php?action=archive&id=123
X-CSRF-Token: <token>
```

**Unarchive:**
```http
PATCH /api/orders.php?action=unarchive&id=123
X-CSRF-Token: <token>
```

**Response:**
```json
{
  "data": {
    "message": "Order archived successfully",
    "order_id": 123
  }
}
```

### Manage Notes

#### Add Note

```http
PATCH /api/orders.php?action=add_note&id=123
Content-Type: application/json
X-CSRF-Token: <token>

{
  "note": "Customer called to confirm shipping address"
}
```

**Response:**
```json
{
  "data": {
    "message": "Note added successfully",
    "note": {
      "id": 5,
      "order_id": 123,
      "note": "Customer called to confirm shipping address",
      "created_by": 1,
      "created_at": "2025-01-15T12:00:00"
    }
  }
}
```

#### Update Note

```http
PATCH /api/orders.php?action=update_note&id=123
Content-Type: application/json
X-CSRF-Token: <token>

{
  "note_id": 5,
  "note": "Updated note content"
}
```

#### Delete Note

```http
PATCH /api/orders.php?action=delete_note&id=123&note_id=5
X-CSRF-Token: <token>
```

### Export Orders

Generate and download order exports with signed URLs.

#### Generate Export URL

```http
POST /api/orders/export.php
Content-Type: application/json
X-CSRF-Token: <token>

{
  "type": "csv",
  "filters": {
    "status": "completed",
    "date_from": "2025-01-01",
    "date_to": "2025-01-31"
  },
  "fields": ["id", "order_number", "name", "email", "amount", "status"],
  "expires_in": 60
}
```

**Response:**
```json
{
  "data": {
    "url": "/api/orders/export.php?token=...&sig=...",
    "expires_at": "2025-01-15T13:00:00",
    "type": "csv"
  }
}
```

#### Download Export

```http
GET /api/orders/export.php?token=...&sig=...
```

**Response:** File download (CSV or PDF)

### Delete Order

Permanently delete an order (admin only, use with caution).

**Request:**
```http
DELETE /api/orders.php?id=123
X-CSRF-Token: <token>
```

**Response:**
```json
{
  "data": {
    "message": "Order deleted successfully",
    "order_id": 123
  }
}
```

## Models

### Order

**Fields:**
- `id`: Integer, primary key
- `order_number`: String, unique identifier
- `type`: Enum (`order`, `contact`)
- `name`: String, customer name
- `email`: String, customer email
- `phone`: String, customer phone
- `telegram`: String, Telegram username
- `service`: String, selected service
- `subject`: String, order subject
- `message`: Text, order message
- `amount`: Decimal, order amount
- `calculator_data`: JSON, calculator inputs
- `form_submission_id`: Integer, related form submission
- `form_slug`: String, form identifier
- `status`: Enum (`new`, `processing`, `completed`, `cancelled`)
- `archived_at`: Timestamp, archive date
- `telegram_sent`: Boolean, notification sent
- `telegram_error`: Text, notification error
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Relationships:**
- `formSubmission`: BelongsTo FormSubmission
- `statusHistory`: HasMany OrderStatusHistory
- `notes`: HasMany OrderNote

### OrderStatusHistory

**Fields:**
- `id`: Integer, primary key
- `order_id`: Integer, foreign key
- `old_status`: String, previous status
- `new_status`: String, current status
- `changed_by`: Integer, admin user ID
- `comment`: Text, change comment
- `ip_address`: String
- `user_agent`: Text
- `created_at`: Timestamp

**Relationships:**
- `order`: BelongsTo Order
- `changedBy`: BelongsTo AdminUser

### OrderNote

**Fields:**
- `id`: Integer, primary key
- `order_id`: Integer, foreign key
- `note`: Text, note content
- `created_by`: Integer, admin user ID
- `ip_address`: String
- `user_agent`: Text
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Relationships:**
- `order`: BelongsTo Order
- `createdBy`: BelongsTo AdminUser

## Examples

### Advanced Filtering

```php
// Get all processing orders from January with customer search
$response = $client->get('/api/orders.php', [
    'status' => 'processing',
    'date_from' => '2025-01-01',
    'date_to' => '2025-01-31',
    'search' => 'john',
    'limit' => 50
]);
```

### Status Change with Notification

```php
// Update status and trigger notifications
$response = $client->patch('/api/orders.php?action=status&id=123', [
    'status' => 'completed',
    'comment' => 'Order completed and shipped'
]);

// Notifications sent via Telegram/Email based on settings:
// - notifications_telegram_status_change
// - notifications_email_status_change
// - notifications_email_address
```

### Export with Filters

```php
// Generate CSV export for completed orders
$response = $client->post('/api/orders/export.php', [
    'type' => 'csv',
    'filters' => [
        'status' => 'completed',
        'date_from' => '2025-01-01'
    ],
    'fields' => ['order_number', 'name', 'amount', 'created_at']
]);

// Download the file
$exportUrl = $response['data']['url'];
file_put_contents('orders.csv', file_get_contents($baseUrl . $exportUrl));
```

### Working with Notes

```php
// Add a note
$note = $client->patch('/api/orders.php?action=add_note&id=123', [
    'note' => 'Customer requested rush delivery'
]);

// Update the note
$client->patch('/api/orders.php?action=update_note&id=123', [
    'note_id' => $note['data']['note']['id'],
    'note' => 'Customer requested rush delivery - confirmed +$50 fee'
]);
```

## Settings

Configure order notifications via settings:

- `notifications_telegram_status_change`: `true`/`false` - Enable Telegram notifications
- `notifications_email_status_change`: `true`/`false` - Enable email notifications
- `notifications_email_address`: Email address for notifications

## Audit Logging

All order operations are logged to `admin_action_logs`:

- Order view
- Order update
- Status change
- Note add/update/delete
- Archive/unarchive
- Export generation
- Order deletion

## Error Handling

**Validation Errors (400):**
```json
{
  "error": "Validation failed",
  "details": {
    "status": ["Invalid status value"]
  }
}
```

**Not Found (404):**
```json
{
  "error": "Order not found"
}
```

**Unauthorized (401):**
```json
{
  "error": "Authentication required. Please log in."
}
```

**Forbidden (403):**
```json
{
  "error": "Invalid CSRF token. Please refresh the page and try again."
}
```
