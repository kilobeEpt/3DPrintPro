# Deprecated API Endpoints

This directory contains deprecated API endpoints that have been replaced by unified REST endpoints.

## Deprecated Endpoints

### submit-form.php (Deprecated)
**Replaced by:** `orders.php` (POST method)

This endpoint was used for form submissions but has been replaced by the unified orders endpoint.

**Migration:**
- Old: `POST /api/submit-form.php`
- New: `POST /api/orders.php`

The new endpoint provides:
- Consistent response structure with `ApiResponse`
- Proper error handling and logging with `ApiLogger`
- Rate limiting protection
- Security headers
- Shared Telegram helper

### get-orders.php (Deprecated)
**Replaced by:** `orders.php` (GET method)

This endpoint was used to fetch orders but has been replaced by the unified orders endpoint.

**Migration:**
- Old: `GET /api/get-orders.php?limit=10&offset=0`
- New: `GET /api/orders.php?limit=10&offset=0`

The new endpoint provides:
- Consistent response structure with `ApiResponse`
- Better pagination metadata
- Filtering by status and type
- Single order retrieval via `?id=123`

## Why These Were Deprecated

1. **Inconsistent Response Structure**: Old endpoints had different response formats
2. **No Rate Limiting**: Old endpoints were vulnerable to abuse
3. **No Security Headers**: Missing important security headers
4. **Code Duplication**: Telegram sending logic was duplicated
5. **Limited Error Handling**: Basic error handling without proper logging

## Current Unified API Structure

All modern endpoints now follow these conventions:

### Success Response (200/201)
```json
{
  "success": true,
  "data": { ... },
  "meta": { ... }
}
```

### Error Response (400/404/422/429/500)
```json
{
  "success": false,
  "error": "Error message",
  "errors": { ... }
}
```

### Rate Limit Headers
All endpoints include:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Requests remaining in window
- `X-RateLimit-Reset`: Unix timestamp when limit resets
- `Retry-After`: Seconds to wait (when rate limited)

### Security Headers
All endpoints include:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `X-XSS-Protection: 1; mode=block`

## Do Not Use These Files

These files are kept for reference only. They should not be used in production and may be removed in future versions.
