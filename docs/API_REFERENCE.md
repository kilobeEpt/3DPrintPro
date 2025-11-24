# API Reference

## Overview

The 3D Print Pro API provides RESTful endpoints for managing content, orders, and other resources. All endpoints return standardized JSON responses and support CORS for cross-origin requests.

### Base URL
```
/api/
```

### Authentication

Most read operations are public. Write operations (POST, PUT, DELETE) require admin authentication via session cookies and CSRF tokens.

### Response Format

All API responses follow a standardized format:

#### Success Response
```json
{
  "success": true,
  "data": {
    // Response data
  },
  "meta": {
    // Optional metadata (pagination, counts, etc.)
  }
}
```

#### Error Response
```json
{
  "success": false,
  "error": "Human-readable error message",
  "errors": {
    // Optional field-specific validation errors
    "field_name": "Validation error message"
  }
}
```

### HTTP Status Codes

- `200 OK` - Successful GET, PUT, DELETE request
- `201 Created` - Successful POST request
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

### Pagination

Endpoints that return multiple records support pagination via query parameters:

- `limit` - Maximum number of records to return (default: varies by endpoint, max: 100)
- `offset` - Number of records to skip (default: 0)
- `page` - Page number (alternative to offset, 1-based)

Paginated responses include metadata:

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "total": 100,
    "limit": 20,
    "offset": 0,
    "page": 1,
    "pages": 5
  }
}
```

### Filtering

Most GET endpoints support filtering via query parameters:

- `active` - Filter by active status (`true` or `false`)
- `category` - Filter by category
- `status` - Filter by status

---

## Admin-Only Endpoints

Some endpoints require elevated permissions and are located under `/api/admin/`:

### Admin Users API

**Endpoint:** `/api/admin/users.php`

**Access:** Super Administrator only (except initial onboarding)

Manages admin user accounts including creation, modification, deletion, and role assignment.

**Features:**
- RBAC enforcement (super_admin required)
- Password complexity validation
- Email uniqueness checks
- Audit trail integration
- Session management
- First-time onboarding support

**See:** [Admin Guide - User Management](ADMIN_GUIDE.md#user-management) for complete documentation

**Documentation:** See `/api/admin/README.md` for detailed API usage

---

## Services API

**Endpoint:** `/api/services.php`

### GET - List Services

Retrieve all services or a single service.

**Query Parameters:**
- `id` (optional) - Get specific service by ID
- `active` (optional) - Filter by active status (`true`/`false`)
- `featured` (optional) - Filter by featured status (`true`/`false`)
- `category` (optional) - Filter by category
- `limit` (optional) - Pagination limit
- `offset` (optional) - Pagination offset

**Example Request:**
```
GET /api/services.php?active=true&limit=10
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "services": [
      {
        "id": 1,
        "name": "3D Printing",
        "slug": "3d-printing",
        "icon": "print",
        "description": "High-quality 3D printing services",
        "features": ["Fast turnaround", "Multiple materials"],
        "price": "From $50",
        "category": "printing",
        "sort_order": 1,
        "active": true,
        "featured": true,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    ]
  },
  "meta": {
    "total": 1
  }
}
```

### POST - Create Service

Create a new service. Requires admin authentication and CSRF token.

**Request Body:**
```json
{
  "name": "Service Name",
  "slug": "service-name",
  "icon": "icon-name",
  "description": "Service description",
  "features": ["Feature 1", "Feature 2"],
  "price": "From $100",
  "category": "category-name",
  "sort_order": 10,
  "active": true,
  "featured": false
}
```

**Required Fields:** `name`

### PUT - Update Service

Update an existing service. Requires admin authentication and CSRF token.

**Request Body:**
```json
{
  "id": 1,
  "name": "Updated Name",
  "active": false
}
```

**Required Fields:** `id`

### DELETE - Delete Service

Delete a service. Requires admin authentication and CSRF token.

**Query Parameters:**
- `id` (required) - Service ID to delete

---

## Portfolio API

**Endpoint:** `/api/portfolio.php`

### GET - List Portfolio Items

Retrieve all portfolio items or a single item.

**Query Parameters:**
- `id` (optional) - Get specific item by ID
- `active` (optional) - Filter by active status
- `category` (optional) - Filter by category
- `limit` (optional) - Pagination limit
- `offset` (optional) - Pagination offset

**Example Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "title": "Project Title",
        "description": "Project description",
        "image": "/uploads/project.jpg",
        "category": "commercial",
        "tags": ["tag1", "tag2"],
        "sort_order": 1,
        "active": true,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    ]
  },
  "meta": {
    "total": 1
  }
}
```

### POST - Create Portfolio Item

Create a new portfolio item. Requires admin authentication and CSRF token.

**Required Fields:** `title`

### PUT - Update Portfolio Item

Update an existing portfolio item. Requires admin authentication and CSRF token.

**Required Fields:** `id`

### DELETE - Delete Portfolio Item

Delete a portfolio item. Requires admin authentication and CSRF token.

---

## FAQ API

**Endpoint:** `/api/faq.php`

### GET - List FAQ Items

Retrieve all FAQ items or a single item.

**Query Parameters:**
- `id` (optional) - Get specific item by ID
- `active` (optional) - Filter by active status
- `limit` (optional) - Pagination limit
- `offset` (optional) - Pagination offset

**Example Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "question": "What is 3D printing?",
        "answer": "3D printing is...",
        "sort_order": 1,
        "active": true,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    ]
  },
  "meta": {
    "total": 1
  }
}
```

### POST - Create FAQ Item

Create a new FAQ item. Requires admin authentication and CSRF token.

**Required Fields:** `question`, `answer`

### PUT - Update FAQ Item

Update an existing FAQ item. Requires admin authentication and CSRF token.

**Required Fields:** `id`

### DELETE - Delete FAQ Item

Delete an FAQ item. Requires admin authentication and CSRF token.

---

## Testimonials API

**Endpoint:** `/api/testimonials.php`

### GET - List Testimonials

Retrieve all testimonials or a single testimonial.

**Query Parameters:**
- `id` (optional) - Get specific testimonial by ID
- `active` (optional) - Filter by active status
- `approved` (optional) - Filter by approved status
- `limit` (optional) - Pagination limit
- `offset` (optional) - Pagination offset

**Example Response:**
```json
{
  "success": true,
  "data": {
    "testimonials": [
      {
        "id": 1,
        "client_name": "John Doe",
        "company": "ACME Corp",
        "content": "Great service!",
        "rating": 5,
        "sort_order": 1,
        "active": true,
        "approved": true,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    ]
  },
  "meta": {
    "total": 1
  }
}
```

### POST - Create Testimonial

Create a new testimonial. Requires admin authentication and CSRF token.

**Required Fields:** `client_name`, `content`

### PUT - Update Testimonial

Update an existing testimonial. Requires admin authentication and CSRF token.

**Required Fields:** `id`

### DELETE - Delete Testimonial

Delete a testimonial. Requires admin authentication and CSRF token.

---

## Content Blocks API

**Endpoint:** `/api/content.php`

### GET - List Content Blocks

Retrieve all content blocks or a single block.

**Query Parameters:**
- `id` (optional) - Get specific block by ID
- `name` (optional) - Get block by name
- `active` (optional) - Filter by active status
- `page` (optional) - Filter by page
- `limit` (optional) - Pagination limit
- `offset` (optional) - Pagination offset

**Example Response:**
```json
{
  "success": true,
  "data": {
    "blocks": [
      {
        "id": 1,
        "block_name": "hero_title",
        "content": "Welcome to 3D Print Pro",
        "page": "home",
        "sort_order": 1,
        "active": true,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    ]
  },
  "meta": {
    "total": 1
  }
}
```

### POST - Create Content Block

Create a new content block. Requires admin authentication and CSRF token.

**Required Fields:** `block_name`, `content`

### PUT - Update Content Block

Update an existing content block. Requires admin authentication and CSRF token.

**Required Fields:** `id`

### DELETE - Delete Content Block

Delete a content block. Requires admin authentication and CSRF token.

---

## Orders API

**Endpoint:** `/api/orders.php`

### GET - List Orders

Retrieve all orders or a single order. Requires admin authentication.

**Query Parameters:**
- `id` (optional) - Get specific order by ID
- `status` (optional) - Filter by status
- `type` (optional) - Filter by type
- `limit` (optional) - Pagination limit (default: 100)
- `offset` (optional) - Pagination offset

**Example Response:**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "order_number": "ORD-20240101-001",
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "customer_phone": "+1234567890",
        "type": "order",
        "status": "pending",
        "calculator_data": {},
        "telegram_sent": true,
        "telegram_error": null,
        "form_submission_id": 1,
        "form_slug": "order",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
      }
    ]
  },
  "meta": {
    "total": 1,
    "limit": 100,
    "offset": 0,
    "has_more": false
  }
}
```

### POST - Create Order

Create a new order via form submission.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "message": "Order details",
  "calculatorData": {
    // Optional calculator data for quote
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order_id": 1,
    "order_number": "ORD-20240101-001",
    "submission_id": 1,
    "message": "Order submitted successfully"
  },
  "meta": {
    "telegram_sent": true,
    "telegram_error": null
  }
}
```

### PUT - Update Order

Update an existing order. Requires admin authentication and CSRF token.

**Required Fields:** `id`

**Note:** Updating the `status` field will trigger a Telegram notification if configured.

### DELETE - Delete Order

Delete an order. Requires admin authentication and CSRF token.

---

## Rate Limiting

API endpoints implement rate limiting to prevent abuse:

- **Read operations (GET):** 60 requests per minute
- **Write operations (POST/PUT/DELETE):** 10 requests per minute

Rate limit headers are included in responses:
- `X-RateLimit-Limit` - Maximum requests per window
- `X-RateLimit-Remaining` - Remaining requests in current window
- `X-RateLimit-Reset` - Time when the rate limit resets

---

## Error Handling

All errors are returned with appropriate HTTP status codes and a standardized JSON format.

### Validation Errors (422)
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "name": "Name is required and must be a string",
    "email": "Email must be a valid email address"
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "error": "Resource not found"
}
```

### Server Error (500)
```json
{
  "success": false,
  "error": "An unexpected error occurred. Please try again later."
}
```

---

## Architecture

The API is built using:

- **Eloquent ORM** - Laravel's database ORM for data access
- **Controller Pattern** - Each resource has a dedicated controller
- **Base Controller** - Shared functionality via `BaseApiController`
- **Traits** - Reusable components (pagination, validation)
- **Standardized Responses** - Consistent JSON structure via `ApiResponse`
- **Security Headers** - CORS, CSP, XSS protection
- **Rate Limiting** - Per-endpoint request throttling
- **Logging** - Comprehensive request/error logging

### Bootstrap Process

All API endpoints load `/api/bootstrap.php` which:
1. Loads Composer autoloader
2. Initializes Eloquent ORM
3. Loads common helpers
4. Applies security headers
5. Sets up global exception handling

### Controller Architecture

Controllers extend `BaseApiController` which provides:
- Request parsing (JSON input, query parameters)
- Response formatting (success, error, validation)
- Authentication hooks
- Rate limiting
- Pagination
- Validation helpers

---

## Backwards Compatibility

The legacy `Database` class (`/api/db.php`) is deprecated but maintained for backwards compatibility. New code should use Eloquent models and controllers.

**Migration Path:**
1. All standard CRUD endpoints now use Eloquent
2. FormService integration maintained for order processing
3. Telegram notifications still use Database for compatibility
4. Legacy scripts can continue using Database class

---

## Example Usage

### JavaScript (Fetch API)

```javascript
// Get all active services
fetch('/api/services.php?active=true')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Services:', data.data.services);
    }
  });

// Create a new FAQ (admin only)
fetch('/api/faq.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': getCsrfToken()
  },
  body: JSON.stringify({
    question: 'How much does it cost?',
    answer: 'Prices start at $50.',
    active: true
  })
})
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      console.log('Created:', data.data);
    }
  });
```

### cURL

```bash
# Get services
curl -X GET 'https://yoursite.com/api/services.php?active=true'

# Create portfolio item (admin)
curl -X POST 'https://yoursite.com/api/portfolio.php' \
  -H 'Content-Type: application/json' \
  -H 'Cookie: session_id=...' \
  -H 'X-CSRF-Token: ...' \
  -d '{
    "title": "New Project",
    "category": "commercial",
    "active": true
  }'
```

---

## Testing

### Smoke Tests

Run API smoke tests:
```bash
php scripts/api_smoke.php
```

### Unit Tests

Run PHPUnit tests:
```bash
composer test
```

### Integration Tests

See `/tests/Integration/` for full test coverage of API controllers and endpoints.

---

## Support

For issues or questions, please refer to:
- Main documentation: `/README.md`
- Testing guide: `/docs/TESTING.md`
- Settings service: `/docs/SETTINGS_SERVICE.md`
- Forms system: `/database/FORMS_SYSTEM.md`
