# SSE (Server-Sent Events) Troubleshooting Guide

## Problem: Content-Type Header Issues

### Symptom
- `/api/updates/stream.php` returns `Content-Type: text/html` instead of `text/event-stream`
- EventSource client fails to connect or immediately closes connection
- Browser console shows MIME type errors like:
  ```
  EventSource's response has a MIME type ("text/html") that is not "text/event-stream". Aborting the connection.
  ```

### Root Cause
The issue occurred because:

1. **PHP Side**: The SSE endpoint was loading `/api/bootstrap.php` which automatically calls `SecurityHeaders::apply()`. This sets `Content-Type: application/json` by default, which conflicts with the SSE requirement for `text/event-stream`.

2. **Web Server Side**: Nginx and Apache configurations were using `add_header` or `Header set` directives that override PHP's headers, causing conflicts.

### Solution

#### 1. PHP Fix (stream.php)
The SSE endpoint now:
- Sets headers **BEFORE** loading any dependencies
- Loads only necessary files (autoloader and Eloquent) **WITHOUT** loading bootstrap.php
- Avoids calling SecurityHeaders::apply() which would override Content-Type

```php
// SSE headers MUST be set BEFORE any output or other headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Load only necessary dependencies (skip bootstrap.php to avoid SecurityHeaders)
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap/eloquent.php';
```

#### 2. Nginx Fix
Removed conflicting `add_header` directives and kept only FastCGI-specific settings:

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

#### 3. Apache Fix
Removed conflicting `Header set` directives:

```apache
<Location /api/updates/stream.php>
    # Extended timeout for SSE long-lived connections
    php_value max_execution_time 3600
    
    # Let PHP set Content-Type and other SSE headers
</Location>
```

## Verification

### 1. Check Headers with curl
```bash
curl -N -H "Accept: text/event-stream" https://3dprint-omsk.ru/api/updates/stream.php
```

Expected output should start with:
```
id: 1
event: init
data: {"event":"connected","clientId":"client_...","timestamp":...}
```

### 2. Check Headers Only
```bash
curl -I https://3dprint-omsk.ru/api/updates/stream.php
```

Expected headers:
```
HTTP/2 200
content-type: text/event-stream
cache-control: no-cache
connection: keep-alive
x-accel-buffering: no
```

### 3. Browser Console Test
```javascript
const eventSource = new EventSource('/api/updates/stream.php');

eventSource.addEventListener('init', (event) => {
    console.log('Connected:', JSON.parse(event.data));
});

eventSource.addEventListener('error', (error) => {
    console.error('SSE Error:', error);
});
```

Should output:
```
Connected: {event: "connected", clientId: "client_...", ...}
```

## Common Issues and Fixes

### Issue: "MIME type mismatch"
**Cause**: Web server is overriding PHP headers
**Fix**: Remove `add_header Content-Type` from nginx or `Header set Content-Type` from Apache

### Issue: "Connection immediately closes"
**Cause**: Output buffering is enabled
**Fix**: Ensure `ob_end_clean()` is called before sending events

### Issue: "Headers already sent"
**Cause**: Output before header() calls
**Fix**: Ensure no echo, print, or whitespace before header() calls

### Issue: "Connection buffered/delayed"
**Cause**: FastCGI buffering is enabled
**Fix**: Set `fastcgi_buffering off` in nginx or use `php_flag output_buffering off` in Apache

### Issue: "Timeout after 30 seconds"
**Cause**: Default PHP/FastCGI timeout
**Fix**: Set `fastcgi_read_timeout 3600s` (nginx) or `php_value max_execution_time 3600` (Apache)

## Best Practices for SSE Endpoints

1. **Set headers first**: Always set SSE headers before any includes or output
2. **Avoid bootstrap.php**: Don't load files that set conflicting headers
3. **Disable buffering**: Use `ob_end_clean()` and disable FastCGI buffering
4. **Long timeouts**: Allow 1-hour connections for long-lived SSE streams
5. **Flush regularly**: Call `flush()` after each event to ensure immediate delivery
6. **Send heartbeats**: Keep connection alive with periodic heartbeat events
7. **Handle disconnects**: Check `connection_aborted()` and exit gracefully

## Security Considerations

1. **No authentication bypass**: SSE endpoints still need proper auth checks (if required)
2. **Rate limiting**: Apply rate limits to prevent abuse of long-lived connections
3. **CORS**: Configure CORS headers if SSE will be accessed cross-origin
4. **XSS protection**: Sanitize event data, especially if it includes user-generated content

## Performance Tips

1. **Connection pooling**: Limit concurrent SSE connections per client
2. **Event batching**: Batch multiple small events into single messages
3. **Compression**: Don't compress SSE streams (breaks real-time delivery)
4. **CDN bypass**: Exclude SSE endpoints from CDN caching

## References

- [MDN: Server-Sent Events](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events)
- [W3C: Server-Sent Events Specification](https://html.spec.whatwg.org/multipage/server-sent-events.html)
- [Nginx: FastCGI Buffering](http://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_buffering)
