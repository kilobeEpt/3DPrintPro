# Unified REST API Documentation

## Overview

The 3D Print API follows RESTful conventions with standardized response structures, comprehensive error handling, rate limiting, and security headers.

## Base URL

```
https://ch167436.tw1.ru/api
```

## Authentication

Currently, the API is open for read operations. Write operations (POST/PUT/DELETE) are protected by rate limiting.

## Rate Limiting

All write operations (POST, PUT, DELETE) are rate limited to prevent abuse.

**Default Limits:**
- 60 requests per minute per IP address
- Configurable per endpoint (e.g., `orders_create`, `services_update`)

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1699999999
```

**When Rate Limited (429):**
```
Retry-After: 45
```

## Security Headers

All responses include security headers:
- `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
- `X-Frame-Options: DENY` - Prevents clickjacking
- `Referrer-Policy: strict-origin-when-cross-origin` - Controls referrer info
- `X-XSS-Protection: 1; mode=block` - XSS protection

## Response Structure

### Success Response (200/201)
```json
{
  "success": true,
  "data": {
    "order": { ... }
  },
  "meta": {
    "total": 100,
    "limit": 10,
    "offset": 0
  }
}
```

### Error Response (400/404/422/429/500)
```json
{
  "success": false,
  "error": "Error message",
  "errors": {
    "field_name": "Validation error"
  }
}
```

## HTTP Status Codes

- `200 OK` - Successful GET/PUT/DELETE
- `201 Created` - Successful POST
- `400 Bad Request` - Invalid input
- `404 Not Found` - Resource not found
- `405 Method Not Allowed` - Invalid HTTP method
- `422 Unprocessable Entity` - Validation failed
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

## Endpoints

### Orders

#### GET /api/orders.php
Retrieve orders with optional filtering and pagination.

**Query Parameters:**
- `id` (optional) - Get specific order by ID
- `status` (optional) - Filter by status: `new`, `processing`, `completed`, `cancelled`
- `type` (optional) - Filter by type: `order`, `contact`
- `limit` (optional, default: 100) - Results per page
- `offset` (optional, default: 0) - Pagination offset

**Response:**
```json
{
  "success": true,
  "data": {
    "orders": [...]
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
    "order_number": "ORD-20240101-ABC123",
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

**Rate Limited:** Yes (60/min)

**Request Body:**
```json
{
  "id": 123,
  "status": "processing",
  "message": "Updated details"
}
```

#### DELETE /api/orders.php
Delete an order.

**Rate Limited:** Yes (60/min)

**Query Parameters:**
- `id` (required) - Order ID to delete

---

### Services

#### GET /api/services.php
Retrieve services with optional filtering.

**Query Parameters:**
- `id` (optional) - Get specific service
- `active` (optional) - Filter by active status: `true`/`false`
- `featured` (optional) - Filter featured services: `true`/`false`
- `category` (optional) - Filter by category
- `limit` (optional, default: 100)
- `offset` (optional, default: 0)

#### POST /api/services.php
Create a new service.

**Rate Limited:** Yes (60/min)

**Request Body:**
```json
{
  "name": "3D Printing FDM",
  "slug": "3d-printing-fdm",
  "icon": "fa-print",
  "description": "High-quality FDM printing",
  "features": ["Fast", "Affordable", "Quality"],
  "price": 100,
  "category": "printing",
  "sort_order": 1,
  "active": 1,
  "featured": 1
}
```

#### PUT /api/services.php
Update a service.

**Rate Limited:** Yes (60/min)

#### DELETE /api/services.php
Delete a service.

**Rate Limited:** Yes (60/min)

---

### Portfolio

#### GET /api/portfolio.php
Retrieve portfolio items.

**Query Parameters:**
- `id` (optional) - Get specific item
- `active` (optional) - Filter by active status
- `category` (optional) - Filter by category
- `limit` (optional, default: 100)
- `offset` (optional, default: 0)

#### POST /api/portfolio.php
Create a portfolio item.

**Rate Limited:** Yes (60/min)

#### PUT /api/portfolio.php
Update a portfolio item.

**Rate Limited:** Yes (60/min)

#### DELETE /api/portfolio.php
Delete a portfolio item.

**Rate Limited:** Yes (60/min)

---

### Testimonials

#### GET /api/testimonials.php
Retrieve testimonials.

**Query Parameters:**
- `id` (optional) - Get specific testimonial
- `active` (optional) - Filter by active status
- `approved` (optional) - Filter by approval status
- `limit` (optional, default: 100)
- `offset` (optional, default: 0)

#### POST /api/testimonials.php
Create a testimonial.

**Rate Limited:** Yes (60/min)

#### PUT /api/testimonials.php
Update a testimonial.

**Rate Limited:** Yes (60/min)

#### DELETE /api/testimonials.php
Delete a testimonial.

**Rate Limited:** Yes (60/min)

---

### FAQ

#### GET /api/faq.php
Retrieve FAQ items.

**Query Parameters:**
- `id` (optional) - Get specific FAQ
- `active` (optional) - Filter by active status
- `limit` (optional, default: 100)
- `offset` (optional, default: 0)

#### POST /api/faq.php
Create a FAQ item.

**Rate Limited:** Yes (60/min)

#### PUT /api/faq.php
Update a FAQ item.

**Rate Limited:** Yes (60/min)

#### DELETE /api/faq.php
Delete a FAQ item.

**Rate Limited:** Yes (60/min)

---

### Content Blocks

#### GET /api/content.php
Retrieve content blocks.

**Query Parameters:**
- `id` (optional) - Get specific block
- `block_name` (optional) - Get by unique block name
- `page` (optional) - Filter by page
- `active` (optional) - Filter by active status
- `limit` (optional, default: 100)
- `offset` (optional, default: 0)

#### POST /api/content.php
Create a content block.

**Rate Limited:** Yes (60/min)

#### PUT /api/content.php
Update a content block.

**Rate Limited:** Yes (60/min)

#### DELETE /api/content.php
Delete a content block.

**Rate Limited:** Yes (60/min)

---

### Settings

#### GET /api/settings.php
Retrieve settings.

**Query Parameters:**
- `key` (optional) - Get specific setting by key

**Response (all settings):**
```json
{
  "success": true,
  "data": {
    "settings": {
      "site_name": "3D Print Pro",
      "telegram_chat_id": "-1001234567890"
    }
  }
}
```

#### POST /api/settings.php (or PUT)
Save settings (single or multiple).

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

**Rate Limited:** Yes (60/min)

**Request Body:**
```json
{
  "key": "setting_name"
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

## Configuration

Rate limiting can be configured in `api/config.php`:

```php
define('RATE_LIMIT_MAX_REQUESTS', 60); // Requests per window
define('RATE_LIMIT_TIME_WINDOW', 60);  // Window in seconds
```

---

## Migration from Deprecated Endpoints

### Old: submit-form.php
**Before:**
```
POST /api/submit-form.php
```

**After:**
```
POST /api/orders.php
```

### Old: get-orders.php
**Before:**
```
GET /api/get-orders.php?limit=10&offset=0
```

**After:**
```
GET /api/orders.php?limit=10&offset=0
```

---

## Testing

### Smoke Test
Run the API smoke test to verify all endpoints:

```bash
php scripts/api_smoke_test.php
```

### Manual Testing
```bash
# Get orders
curl https://ch167436.tw1.ru/api/orders.php

# Create order (with rate limit headers)
curl -X POST https://ch167436.tw1.ru/api/orders.php \
  -H "Content-Type: application/json" \
  -d '{"name":"John","phone":"+79991234567"}' \
  -i

# Check rate limit headers
curl -I https://ch167436.tw1.ru/api/orders.php
```

---

## Security Best Practices

1. **HTTPS Only**: Always use HTTPS in production
2. **Input Validation**: All inputs are validated and sanitized
3. **SQL Injection Protection**: PDO prepared statements throughout
4. **XSS Protection**: `htmlspecialchars()` on all output
5. **Rate Limiting**: Prevents abuse on write operations
6. **Security Headers**: Standard security headers on all responses
7. **Config Protection**: `api/config.php` protected by `.htaccess`

---

## Support

For issues or questions, contact the development team or create an issue in the project repository.
