# Content API v2.0 - Media Upload & Caching Guide

## Overview

The Content API has been overhauled to support:
- **Media uploads** for portfolio images and testimonial avatars
- **Slug-based URLs** for all content types (services, portfolio, testimonials, FAQ, content blocks)
- **Cache headers** (ETag, Last-Modified) for optimal frontend performance
- **Featured flags** for portfolio items and testimonials
- **Automatic cache invalidation** on content changes

All content endpoints now operate exclusively via Eloquent ORM with no raw SQL.

---

## New Features

### 1. Media Upload Support

Portfolio items and testimonials now support direct file uploads via `multipart/form-data`.

#### Supported File Types
- `image/jpeg`, `image/jpg`
- `image/png`
- `image/gif`
- `image/webp`

#### File Size Limit
- Maximum: **5 MB** per file

#### Storage Location
- Files are stored in: `storage/uploads/portfolio/` and `storage/uploads/testimonials/`
- Public URLs: `https://3dprint-omsk.ru/storage/uploads/{type}/{filename}`

#### Secure Filenames
- Files are renamed with format: `{timestamp}_{hash}.{extension}`
- Example: `1704067200_a1b2c3d4e5f6g7h8.jpg`

---

### 2. Slug-Based Access

All content types now support unique, SEO-friendly slugs:

```bash
GET /api/services.php?slug=3d-printing
GET /api/portfolio.php?slug=custom-drone-parts
GET /api/testimonials.php?slug=john-doe
GET /api/faq.php?slug=what-is-3d-printing
GET /api/content.php?slug=hero-section
```

#### Slug Generation
- Automatically generated from title/name/question if not provided
- Cyrillic characters transliterated to Latin
- Special characters removed, spaces replaced with hyphens
- Duplicates prevented with numeric suffixes (e.g., `test-2`, `test-3`)

---

### 3. Cache Headers

All GET endpoints now emit HTTP cache headers for optimal client-side caching:

#### Headers Emitted
- `ETag: "{hash}"` - MD5 hash of `updated_at` timestamp
- `Last-Modified: {date}` - RFC 7231 formatted date
- `Cache-Control: public, max-age=300, must-revalidate` - 5-minute cache

#### Client Behavior
- Clients should send `If-None-Match` (ETag) or `If-Modified-Since` headers
- Server returns `304 Not Modified` if content unchanged
- Reduces bandwidth and improves load times

#### Example Request/Response
```http
GET /api/services.php HTTP/1.1
If-None-Match: "a1b2c3d4e5f6g7h8"

HTTP/1.1 304 Not Modified
ETag: "a1b2c3d4e5f6g7h8"
Last-Modified: Wed, 01 Jan 2025 12:00:00 GMT
Cache-Control: public, max-age=300, must-revalidate
```

---

### 4. Featured Flags

Portfolio items and testimonials can now be marked as "featured" for homepage/highlight sections.

#### Filtering by Featured
```bash
GET /api/portfolio.php?featured=true
GET /api/testimonials.php?featured=true
```

#### Response Example
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "title": "Amazing Project",
        "slug": "amazing-project",
        "featured": true,
        ...
      }
    ]
  }
}
```

---

## API Endpoints Updated

### Services API (`/api/services.php`)

#### GET - New Parameters
- `slug` - Get service by slug
- `featured` - Filter by featured status

#### POST/PUT - New Fields
- `slug` (optional) - Custom slug (auto-generated if not provided)
- `featured` (optional) - Mark as featured (default: false)

---

### Portfolio API (`/api/portfolio.php`)

#### GET - New Parameters
- `slug` - Get portfolio item by slug
- `featured` - Filter by featured status

#### POST - Media Upload
Supports both JSON and multipart/form-data:

**JSON Request (no file):**
```json
{
  "title": "Custom Drone Parts",
  "description": "High-precision drone components",
  "category": "aerospace",
  "tags": ["drone", "custom"],
  "active": true,
  "featured": true
}
```

**Multipart Form-Data (with file):**
```http
POST /api/portfolio.php HTTP/1.1
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary

------WebKitFormBoundary
Content-Disposition: form-data; name="title"

Custom Drone Parts
------WebKitFormBoundary
Content-Disposition: form-data; name="description"

High-precision drone components
------WebKitFormBoundary
Content-Disposition: form-data; name="image"; filename="drone.jpg"
Content-Type: image/jpeg

(binary image data)
------WebKitFormBoundary--
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 10,
    "message": "Portfolio item created successfully"
  }
}
```

#### New Response Fields
- `image_path` - Relative path to uploaded image
- `image_url` - Full public URL to image
- `image_size` - File size in bytes
- `image_mime` - MIME type
- `slug` - URL-friendly slug
- `featured` - Featured flag

#### DELETE - Cleanup
- Automatically deletes associated image file from storage

---

### Testimonials API (`/api/testimonials.php`)

#### GET - New Parameters
- `slug` - Get testimonial by slug
- `featured` - Filter by featured status
- `min_rating` - Filter by minimum rating (1-5)

#### POST - Avatar Upload
Supports both JSON and multipart/form-data:

**Multipart Form-Data (with avatar):**
```http
POST /api/testimonials.php HTTP/1.1
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary

------WebKitFormBoundary
Content-Disposition: form-data; name="name"

John Doe
------WebKitFormBoundary
Content-Disposition: form-data; name="text"

Excellent service, highly recommend!
------WebKitFormBoundary
Content-Disposition: form-data; name="rating"

5
------WebKitFormBoundary
Content-Disposition: form-data; name="avatar"; filename="avatar.jpg"
Content-Type: image/jpeg

(binary image data)
------WebKitFormBoundary--
```

#### New Response Fields
- `avatar_path` - Relative path to uploaded avatar
- `avatar` - Full public URL to avatar (also legacy field)
- `avatar_size` - File size in bytes
- `avatar_mime` - MIME type
- `slug` - URL-friendly slug
- `featured` - Featured flag

#### DELETE - Cleanup
- Automatically deletes associated avatar file from storage

---

### FAQ API (`/api/faq.php`)

#### GET - New Parameters
- `slug` - Get FAQ item by slug

#### POST/PUT - New Fields
- `slug` (optional) - Custom slug (auto-generated from question if not provided)

---

### Content Blocks API (`/api/content.php`)

#### GET - New Parameters
- `slug` - Get content block by slug
- `name` - Legacy support for block_name

#### POST/PUT - New Fields
- `slug` (optional) - Custom slug (auto-generated from block_name if not provided)

---

## Error Responses

### Media Upload Errors

**Invalid File Type:**
```json
{
  "success": false,
  "error": "Image upload failed: Invalid file type. Allowed types: image/jpeg, image/jpg, image/png, image/gif, image/webp"
}
```

**File Too Large:**
```json
{
  "success": false,
  "error": "Image upload failed: File size exceeds maximum allowed size of 5 MB"
}
```

**Upload Failed:**
```json
{
  "success": false,
  "error": "Image upload failed: Failed to move uploaded file to destination"
}
```

### Slug Conflicts
Slugs are automatically deduplicated with numeric suffixes, so conflicts are handled transparently.

---

## Cache Invalidation

The ContentCacheService automatically invalidates caches when content is created, updated, or deleted:

### Automatic Invalidation
- **Services**: On create/update/delete
- **Portfolio**: On create/update/delete
- **Testimonials**: On create/update/delete
- **FAQ**: On create/update/delete
- **Content Blocks**: On create/update/delete

### Manual Invalidation (if needed)
```php
use App\Services\ContentCacheService;

$cacheService = new ContentCacheService();
$cacheService->invalidateCache('services');
$cacheService->clearAll(); // Clear all resource caches
```

---

## Frontend Integration

### Fetching with Cache Support

```javascript
// Initial request
fetch('/api/services.php?active=true')
  .then(res => {
    const etag = res.headers.get('ETag');
    const lastModified = res.headers.get('Last-Modified');
    // Store these for subsequent requests
    localStorage.setItem('services-etag', etag);
    localStorage.setItem('services-lastmod', lastModified);
    return res.json();
  });

// Subsequent request with cache headers
const etag = localStorage.getItem('services-etag');
fetch('/api/services.php?active=true', {
  headers: {
    'If-None-Match': etag
  }
})
  .then(res => {
    if (res.status === 304) {
      // Content not modified, use cached data
      return JSON.parse(localStorage.getItem('services-data'));
    }
    return res.json();
  });
```

### Media Upload Example

```javascript
async function uploadPortfolioItem(formData) {
  const response = await fetch('/api/portfolio.php', {
    method: 'POST',
    body: formData, // FormData with file
    credentials: 'include', // Include session cookies
    headers: {
      'X-CSRF-Token': getCsrfToken() // CSRF token from session
    }
  });
  
  return response.json();
}

// Usage
const formData = new FormData();
formData.append('title', 'My Project');
formData.append('description', 'Description');
formData.append('image', fileInput.files[0]); // File from <input type="file">

const result = await uploadPortfolioItem(formData);
console.log(result.data.id); // New portfolio item ID
```

---

## Database Schema Updates

### New Columns Added

#### portfolio table
- `slug` VARCHAR(255) NOT NULL UNIQUE
- `featured` BOOLEAN DEFAULT FALSE
- `image_path` VARCHAR(500) NULL
- `image_size` INT NULL
- `image_mime` VARCHAR(100) NULL

#### testimonials table
- `slug` VARCHAR(255) NOT NULL UNIQUE
- `featured` BOOLEAN DEFAULT FALSE
- `avatar_path` VARCHAR(500) NULL
- `avatar_size` INT NULL
- `avatar_mime` VARCHAR(100) NULL

#### faq table
- `slug` VARCHAR(255) NOT NULL UNIQUE

#### content_blocks table
- `slug` VARCHAR(255) NOT NULL UNIQUE

### Migration Script
Run the migration to apply schema changes:
```bash
php scripts/migrate-content-fields.php
```

---

## Testing

### PHPUnit Tests
```bash
# Run all content API tests
vendor/bin/phpunit --testsuite Integration --filter ContentApiTest

# Run media upload tests
vendor/bin/phpunit --testsuite Unit --filter MediaUploadServiceTest

# Run all tests
composer test
```

### Manual Testing
See test cases in:
- `tests/Integration/ContentApiTest.php`
- `tests/Unit/MediaUploadServiceTest.php`

---

## Performance Considerations

1. **Cache Headers**: Reduces server load by allowing clients to cache responses
2. **Media Storage**: Files served directly from filesystem, not through PHP
3. **Slug Indexing**: Database indexes on slug columns for fast lookups
4. **Pagination**: All list endpoints support pagination to limit response size

---

## Security

1. **MIME Type Validation**: Prevents execution of malicious files
2. **File Size Limits**: Prevents DOS attacks via large uploads
3. **Secure Filenames**: Random hashes prevent path traversal attacks
4. **Authentication**: All write operations require admin authentication + CSRF token
5. **Storage Isolation**: Uploaded files stored outside web root (if configured)

---

## Backward Compatibility

- Legacy `block_name` parameter still supported for content blocks
- Existing `image_url` and `avatar` fields preserved for compatibility
- All existing API contracts maintained, new features are additive

---

## Support & Documentation

- **API Reference**: [docs/API_REFERENCE.md](API_REFERENCE.md)
- **Testing Guide**: [docs/TESTING.md](TESTING.md)
- **Architecture**: Memory section in agent context
