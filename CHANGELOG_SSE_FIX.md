# SSE Content-Type Header Fix - Changelog

## Issue
The SSE endpoint `/api/updates/stream.php` was returning `Content-Type: text/html` instead of `text/event-stream`, causing EventSource client failures and SSE connection drops.

## Root Causes

### 1. PHP Side Issue
The SSE endpoint was loading `/api/bootstrap.php` which automatically called `SecurityHeaders::apply()`. This set `Content-Type: application/json` by default, overriding the SSE-specific header that was set later in the file.

### 2. Web Server Configuration Issues
- **Nginx**: Used `add_header Content-Type "text/event-stream"` which conflicted with PHP's headers
- **Apache**: Used `Header set Content-Type "text/event-stream"` which also overrode PHP's headers
- Both configurations had unnecessary proxy-related directives that weren't appropriate for FastCGI

## Changes Made

### 1. `/api/updates/stream.php`
**Changed**: Refactored to set headers first and load dependencies without bootstrap.php

**Before**:
```php
require_once __DIR__ . '/../bootstrap.php';  // Calls SecurityHeaders::apply()

use App\Services\ContentCacheService;
use App\Services\SSEBroadcaster;

header('Content-Type: text/event-stream');  // Too late - already overridden
```

**After**:
```php
// SSE headers MUST be set BEFORE any output or other headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Load only necessary dependencies (skip bootstrap.php to avoid SecurityHeaders)
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap/eloquent.php';

use App\Services\ContentCacheService;
use App\Services\SSEBroadcaster;
```

### 2. `/deploy/webserver/nginx.3dprint-omsk.conf`
**Changed**: Removed conflicting header directives, kept only FastCGI settings

**Before**:
```nginx
location = /api/updates/stream.php {
    proxy_buffering off;  # Wrong - this is for proxying, not FastCGI
    proxy_cache off;
    
    fastcgi_pass php_fpm;
    fastcgi_buffering off;
    fastcgi_read_timeout 3600s;
    
    add_header Content-Type "text/event-stream";  # Conflicts with PHP
    add_header Cache-Control "no-cache";
    add_header X-Accel-Buffering "no";
}
```

**After**:
```nginx
location = /api/updates/stream.php {
    fastcgi_pass php_fpm;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    
    # SSE-specific FastCGI settings
    fastcgi_buffering off;          # Disable FastCGI buffering
    fastcgi_read_timeout 3600s;     # Allow long connections (1 hour)
    fastcgi_cache_bypass 1;         # Bypass FastCGI cache
    
    # Let PHP set Content-Type and other SSE headers
}
```

### 3. `/deploy/webserver/apache.3dprint-omsk.conf`
**Changed**: Removed conflicting Header directives

**Before**:
```apache
<Location /api/updates/stream.php>
    Header set Content-Type "text/event-stream"  # Overrides PHP
    Header set Cache-Control "no-cache"
    Header set X-Accel-Buffering "no"
    php_value max_execution_time 3600
</Location>
```

**After**:
```apache
<Location /api/updates/stream.php>
    # Extended timeout for SSE long-lived connections
    php_value max_execution_time 3600
    
    # Let PHP set Content-Type and other SSE headers
</Location>
```

## New Documentation

### 1. `/docs/SSE_TROUBLESHOOTING.md`
Comprehensive troubleshooting guide covering:
- Root cause analysis
- Step-by-step solutions
- Verification methods (curl, browser console)
- Common issues and fixes
- Best practices for SSE endpoints
- Security considerations
- Performance tips

### 2. Updated Existing Docs
- `/docs/CONTENT_SYNC_SSE.md` - Added troubleshooting section with link to new guide
- `/docs/WEB_SERVER_CONFIG.md` - Added troubleshooting note in SSE verification section

## Verification

### Command Line Test
```bash
# Check headers
curl -I https://3dprint-omsk.ru/api/updates/stream.php

# Expected output:
# HTTP/2 200
# content-type: text/event-stream
# cache-control: no-cache
# connection: keep-alive
# x-accel-buffering: no
```

### Browser Console Test
```javascript
const eventSource = new EventSource('/api/updates/stream.php');

eventSource.addEventListener('init', (event) => {
    console.log('Connected:', JSON.parse(event.data));
});

eventSource.addEventListener('error', (error) => {
    console.error('SSE Error:', error);
});
```

## Files Changed

- ✅ `/api/updates/stream.php` - Fixed header order and dependencies
- ✅ `/deploy/webserver/nginx.3dprint-omsk.conf` - Removed conflicting headers
- ✅ `/deploy/webserver/apache.3dprint-omsk.conf` - Removed conflicting headers
- ✅ `/docs/SSE_TROUBLESHOOTING.md` - New comprehensive guide (NEW)
- ✅ `/docs/CONTENT_SYNC_SSE.md` - Added troubleshooting section
- ✅ `/docs/WEB_SERVER_CONFIG.md` - Added troubleshooting note
- ✅ `/test-sse-headers.php` - Test script for verification (NEW)

## Impact

### Before Fix
- ❌ SSE connections failed with MIME type errors
- ❌ EventSource client immediately disconnected
- ❌ Real-time content updates didn't work
- ❌ Browser console showed errors

### After Fix
- ✅ SSE connections work correctly
- ✅ EventSource client receives events
- ✅ Real-time content updates functional
- ✅ No browser console errors
- ✅ Proper Content-Type: text/event-stream header

## Deployment Notes

### For Production Servers

1. **Update PHP files**: Deploy new `/api/updates/stream.php`
   ```bash
   git pull origin fix/sse-content-type-headers-nginx-buffering
   ```

2. **Update Nginx config** (if using Nginx):
   ```bash
   sudo cp deploy/webserver/nginx.3dprint-omsk.conf /etc/nginx/sites-available/3dprint-omsk.conf
   sudo nginx -t
   sudo systemctl reload nginx
   ```

3. **Update Apache config** (if using Apache):
   ```bash
   sudo cp deploy/webserver/apache.3dprint-omsk.conf /etc/apache2/sites-available/3dprint-omsk.conf
   sudo apache2ctl configtest
   sudo systemctl reload apache2
   ```

4. **Verify the fix**:
   ```bash
   curl -I https://3dprint-omsk.ru/api/updates/stream.php | grep -i content-type
   # Should output: content-type: text/event-stream
   ```

### No Database Changes Required
This fix only involves configuration and code changes - no database migrations needed.

### No Breaking Changes
This fix is backward compatible and doesn't affect existing functionality.

## Testing Checklist

- ✅ SSE endpoint returns correct Content-Type header
- ✅ SSE endpoint returns all required headers (Cache-Control, Connection, X-Accel-Buffering)
- ✅ No early output before header() calls
- ✅ EventSource client connects successfully
- ✅ Events are received in browser console
- ✅ Real-time content invalidation works
- ✅ Long-lived connections (5 min) work without timeout
- ✅ Heartbeat events are received
- ✅ Connection can be closed and reopened

## Related Issues

- Fixes EventSource MIME type mismatch errors
- Fixes immediate SSE connection drops
- Fixes real-time content sync not working
- Improves nginx/apache configuration for SSE endpoints

## References

- [MDN: Server-Sent Events](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events)
- [W3C: SSE Specification](https://html.spec.whatwg.org/multipage/server-sent-events.html)
- [Nginx FastCGI Module](http://nginx.org/en/docs/http/ngx_http_fastcgi_module.html)
