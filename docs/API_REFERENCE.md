# API Reference

Complete reference for the 3D Print Pro REST API.

## Overview

The API follows RESTful conventions with standardized response structures, comprehensive error handling, rate limiting, and security headers.

**Base URL:** `/api/`  
**Format:** JSON  
**Authentication:** PHP sessions for admin operations  
**Rate Limit:** 60 requests/minute per IP (configurable)

## HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Successful GET/PUT/DELETE request |
| 201 | Created | Successful POST request |
| 400 | Bad Request | Invalid input or malformed request |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | CSRF token invalid or permission denied |
| 404 | Not Found | Resource not found |
| 405 | Method Not Allowed | Invalid HTTP method |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

## Response Structure

### Success Response

```json
{
  "success": true,
  "data": {
    "order": {...}
  },
  "meta": {
    "total": 100,
    "limit": 10,
    "offset": 0,
    "has_more": true
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": "Error message",
  "errors": {
    "field_name": "Validation error details"
  }
}
```

## Rate Limiting

All write operations (POST/PUT/DELETE) are rate limited.

**Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1699999999
```

**When Exceeded (429):**
```
Retry-After: 45
```

## Security Headers

All responses include:
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-Frame-Options: DENY` - Prevents clickjacking
- `Referrer-Policy: strict-origin-when-cross-origin` - Controls referrer info
- `X-XSS-Protection: 1; mode=block` - XSS protection

## API Endpoints

### Orders

#### GET /api/orders.php

Retrieve orders with optional filtering and pagination.

**Authentication:** Required (admin session)

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `id` | int | - | Get specific order by ID |
| `status` | string | - | Filter by: `new`, `processing`, `completed`, `cancelled` |
| `type` | string | - | Filter by: `order`, `contact` |
| `limit` | int | 100 | Results per page |
| `offset` | int | 0 | Pagination offset |

**Example:**
```bash
curl https://your-domain.com/api/orders.php?status=new&limit=50
```

**Response:**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "order_number": "ORD-20250119-ABC123",
        "type": "order",
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+7 (999) 123-45-67",
        "telegram": "@johndoe",
        "service": "FDM печать",
        "message": "Order details",
        "amount": "1500.00",
        "calculator_data": {...},
        "status": "new",
        "telegram_sent": 1,
        "created_at": "2025-01-19 14:30:00",
        "updated_at": "2025-01-19 14:30:00"
      }
    ]
  },
  "meta": {
    "total": 150,
    "limit": 100,
    "offset": 0,
    "has_more": true
  }
}
```

#### POST /api/orders.php

Create a new order or contact form submission.

**Authentication:** Public (no auth required)  
**Rate Limited:** Yes (60/min)

**Request Body:**
```json
{
  "name": "John Doe",
  "phone": "+7 (999) 123-45-67",
  "email": "john@example.com",
  "telegram": "@johndoe",
  "service": "3D Printing",
  "message": "Order details",
  "amount": 1500,
  "calculatorData": {
    "technology": "fdm",
    "material": "PLA",
    "weight": 50,
    "quantity": 2
  }
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "order_id": 123,
    "order_number": "ORD-20250119-ABC123",
    "message": "Order submitted successfully"
  },
  "meta": {
    "telegram_sent": true,
    "telegram_error": null
  }
}
```

#### PUT /api/orders.php

Update an existing order.

**Authentication:** Required + CSRF token  
**Rate Limited:** Yes (60/min)

**Request Body:**
```json
{
  "id": 123,
  "status": "processing",
  "message": "Updated details"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Order updated successfully",
    "status_changed": true,
    "telegram_notification_sent": true
  }
}
```

#### DELETE /api/orders.php

Delete an order.

**Authentication:** Required + CSRF token  
**Rate Limited:** Yes (60/min)

**Query Parameters:**
- `id` (required) - Order ID to delete

**Example:**
```bash
curl -X DELETE https://your-domain.com/api/orders.php?id=123 \
  -H "Cookie: PHPSESSID=..." \
  -H "X-CSRF-Token: ..."
```

---

### Services

#### GET /api/services.php

Retrieve services with optional filtering.

**Authentication:** Public  
**Rate Limited:** No

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `id` | int | - | Get specific service |
| `active` | bool | - | Filter by active status |
| `featured` | bool | - | Filter featured services |
| `category` | string | - | Filter by category |
| `limit` | int | 100 | Results per page |
| `offset` | int | 0 | Pagination offset |

**Response:**
```json
{
  "success": true,
  "data": {
    "services": [
      {
        "id": 1,
        "name": "FDM печать",
        "slug": "fdm-printing",
        "icon": "fa-print",
        "description": "High-quality FDM 3D printing",
        "features": ["Fast", "Affordable", "Quality"],
        "price": "от 50₽/г",
        "category": "printing",
        "sort_order": 1,
        "active": 1,
        "featured": 1,
        "created_at": "2025-01-01 00:00:00"
      }
    ]
  }
}
```

#### POST /api/services.php

Create a new service.

**Authentication:** Required + CSRF token  
**Rate Limited:** Yes (60/min)

**Request Body:**
```json
{
  "name": "SLA печать",
  "slug": "sla-printing",
  "icon": "fa-cube",
  "description": "Precision SLA printing",
  "features": ["High detail", "Smooth surface"],
  "price": "от 100₽/г",
  "category": "printing",
  "sort_order": 2,
  "active": true,
  "featured": false
}
```

#### PUT /api/services.php

Update a service.

**Authentication:** Required + CSRF token  
**Rate Limited:** Yes (60/min)

**Request Body:**
```json
{
  "id": 1,
  "price": "от 60₽/г",
  "featured": true
}
```

#### DELETE /api/services.php

Delete a service.

**Authentication:** Required + CSRF token  
**Rate Limited:** Yes (60/min)

**Query Parameters:**
- `id` (required) - Service ID

---

### Portfolio

#### GET /api/portfolio.php

Retrieve portfolio items.

**Authentication:** Public  
**Rate Limited:** No

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `id` | int | - | Get specific item |
| `active` | bool | - | Filter by active status |
| `category` | string | - | Filter by category |

**Response:**
```json
{
  "success": true,
  "data": {
    "portfolio": [
      {
        "id": 1,
        "title": "Architectural Model",
        "description": "Detailed architectural prototype",
        "image_url": "/images/portfolio1.jpg",
        "category": "architecture",
        "tags": ["prototype", "architecture", "detailed"],
        "sort_order": 1,
        "active": 1
      }
    ]
  }
}
```

#### POST /api/portfolio.php

Create portfolio item.

**Authentication:** Required + CSRF token

#### PUT /api/portfolio.php

Update portfolio item.

**Authentication:** Required + CSRF token

#### DELETE /api/portfolio.php

Delete portfolio item.

**Authentication:** Required + CSRF token

---

### Testimonials

#### GET /api/testimonials.php

Retrieve testimonials.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `id` | int | - | Get specific testimonial |
| `active` | bool | - | Filter by active status |
| `approved` | bool | - | Filter by approval status |

**Response:**
```json
{
  "success": true,
  "data": {
    "testimonials": [
      {
        "id": 1,
        "name": "Иван Петров",
        "position": "Директор, ООО \"Компания\"",
        "avatar": "/images/avatar1.jpg",
        "text": "Отличное качество печати!",
        "rating": 5,
        "sort_order": 1,
        "approved": 1,
        "active": 1
      }
    ]
  }
}
```

#### POST /api/testimonials.php

Create testimonial (admin only).

#### PUT /api/testimonials.php

Update testimonial (admin only).

#### DELETE /api/testimonials.php

Delete testimonial (admin only).

---

### FAQ

#### GET /api/faq.php

Retrieve FAQ items.

**Response:**
```json
{
  "success": true,
  "data": {
    "faq": [
      {
        "id": 1,
        "question": "Сколько стоит 3D печать?",
        "answer": "Стоимость зависит от материала и веса...",
        "sort_order": 1,
        "active": 1
      }
    ]
  }
}
```

#### POST /api/faq.php

Create FAQ item (admin only).

#### PUT /api/faq.php

Update FAQ item (admin only).

#### DELETE /api/faq.php

Delete FAQ item (admin only).

---

### Content Blocks

#### GET /api/content.php

Retrieve content blocks.

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `id` | int | - | Get specific block |
| `block_name` | string | - | Get by unique name |
| `page` | string | - | Filter by page |

**Response:**
```json
{
  "success": true,
  "data": {
    "content_blocks": [
      {
        "id": 1,
        "block_name": "home_hero",
        "title": "3D Печать в Омске",
        "content": "Профессиональная 3D печать...",
        "data": {...},
        "page": "home",
        "sort_order": 1,
        "active": 1
      }
    ]
  }
}
```

#### POST /api/content.php

Create content block (admin only).

#### PUT /api/content.php

Update content block (admin only).

#### DELETE /api/content.php

Delete content block (admin only).

---

### Settings

#### GET /api/settings.php

Retrieve settings.

**Authentication:** Required (admin only)

**Query Parameters:**
- `key` (optional) - Get specific setting

**Response (all settings):**
```json
{
  "success": true,
  "data": {
    "settings": {
      "site_name": "3D Print Pro",
      "telegram_chat_id": "-1001234567890",
      "telegram_bot_token": "123456:ABC-DEF...",
      "telegram_notify_new_order": "1",
      "telegram_notify_status_change": "1"
    }
  }
}
```

#### POST /api/settings.php (or PUT)

Save settings.

**Authentication:** Required + CSRF token  
**Rate Limited:** Yes (60/min)

**Single Setting:**
```json
{
  "key": "site_name",
  "value": "3D Print Pro"
}
```

**Multiple Settings:**
```json
{
  "site_name": "3D Print Pro",
  "telegram_chat_id": "-1001234567890"
}
```

#### DELETE /api/settings.php

Delete a setting.

**Authentication:** Required + CSRF token

---

### Telegram Test

#### POST /api/telegram-test.php

Send test Telegram message.

**Authentication:** Required  
**Rate Limited:** Yes (60/min)

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "message": "Test message sent successfully"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Bot token not configured"
}
```

---

### Diagnostics

#### GET /api/test.php

Quick API health check.

**Query Parameters:**
- `audit=full` - Run comprehensive database audit

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-01-19 14:30:00",
  "database_status": "Connected",
  "mysql_version": "8.0.32",
  "php_version": "8.1.0",
  "tables": {
    "orders": 42,
    "services": 6,
    "portfolio": 4,
    "testimonials": 4,
    "faq": 8,
    "content_blocks": 3,
    "settings": 15
  }
}
```

---

## Error Handling

### Validation Errors (400/422)

```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "name": "Name is required and must be a string",
    "phone": "Phone is required and must be a string"
  }
}
```

### Not Found (404)

```json
{
  "success": false,
  "error": "Order not found"
}
```

### Unauthorized (401)

```json
{
  "success": false,
  "error": "Authentication required"
}
```

### Forbidden (403)

```json
{
  "success": false,
  "error": "Invalid CSRF token"
}
```

### Rate Limit Exceeded (429)

```json
{
  "success": false,
  "error": "Rate limit exceeded. Please try again later.",
  "meta": {
    "retry_after": 45,
    "reset": 1699999999,
    "limit": 60,
    "window": 60
  }
}
```

### Server Error (500)

```json
{
  "success": false,
  "error": "Internal server error. Please try again later."
}
```

---

## JavaScript Client

### APIClient Class

```javascript
const apiClient = new APIClient('/api');

// Settings
const settings = await apiClient.getAllSettings();
const chatId = await apiClient.getSetting('telegram_chat_id');
await apiClient.saveSettings({ site_name: "New Name" });

// Orders
const orders = await apiClient.getOrders({ status: 'new', limit: 50 });
const order = await apiClient.createOrder({ name: "John", phone: "+7..." });
await apiClient.updateOrder(id, { status: 'processing' });
await apiClient.deleteOrder(id);

// Services
const services = await apiClient.getServices({ active: true });
await apiClient.createService({ name: "New Service", ...});
await apiClient.updateService(id, { price: "100₽" });
await apiClient.deleteService(id);

// Portfolio, Testimonials, FAQ, Content - similar CRUD methods
```

---

## Configuration

Rate limiting configured in `api/config.php`:

```php
define('RATE_LIMIT_MAX_REQUESTS', 60); // Requests per window
define('RATE_LIMIT_TIME_WINDOW', 60);  // Window in seconds
```

---

## Testing

### Smoke Test

```bash
php scripts/api_smoke_test.php
```

### Manual Testing

```bash
# Get orders (requires auth)
curl -b cookies.txt https://your-domain.com/api/orders.php

# Create order
curl -X POST https://your-domain.com/api/orders.php \
  -H "Content-Type: application/json" \
  -d '{"name":"John","phone":"+79991234567","message":"Test"}' \
  -i

# Check rate limit headers
curl -I https://your-domain.com/api/orders.php

# Full audit
curl https://your-domain.com/api/test.php?audit=full
```

---

## Migration from Deprecated Endpoints

### Old: submit-form.php
**Before:** `POST /api/submit-form.php`  
**After:** `POST /api/orders.php`

### Old: get-orders.php
**Before:** `GET /api/get-orders.php?limit=10`  
**After:** `GET /api/orders.php?limit=10`

---

## Support

For issues or questions:
- Check logs: `logs/api.log`
- Run diagnostics: `/api/test.php?audit=full`
- Database audit: `php scripts/db_audit.php`
- See [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
