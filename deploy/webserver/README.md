# Web Server Configuration Templates

Hardened configuration templates for Nginx and Apache with SSL, security headers, and performance optimizations.

## Files

```
deploy/webserver/
├── nginx.3dprint-omsk.conf      # Full Nginx configuration
├── apache.3dprint-omsk.conf     # Full Apache configuration
├── .htaccess.example            # Shared hosting alternative
└── snippets/
    └── security.conf            # Nginx security headers snippet
```

## Features

✅ **Both domains**: `3dprint-omsk.ru` and `www.3dprint-omsk.ru`  
✅ **HTTP→HTTPS redirects**: All HTTP traffic redirected to HTTPS  
✅ **www→apex redirects**: `www.3dprint-omsk.ru` → `3dprint-omsk.ru`  
✅ **SSL/TLS**: Modern ciphers (TLS 1.2, 1.3), OCSP stapling  
✅ **Security headers**: CSP, HSTS, X-Frame-Options, Permissions-Policy  
✅ **PHP-FPM integration**: FastCGI for API and admin endpoints  
✅ **SSE support**: Special handling for `/api/updates/stream.php`  
✅ **Upload limits**: 5MB max (aligned with MediaUploadService)  
✅ **Compression**: gzip/deflate for text assets  
✅ **Browser caching**: Long-term caching for static assets  
✅ **Sensitive file blocking**: Deny access to `.env`, `.git`, `storage/`, etc.

## Quick Start

### Nginx

```bash
# 1. Copy files
sudo cp nginx.3dprint-omsk.conf /etc/nginx/sites-available/3dprint-omsk.conf
sudo cp snippets/security.conf /etc/nginx/snippets/3dprint-security.conf

# 2. Customize paths (PHP-FPM socket, project root, SSL certificates)
sudo nano /etc/nginx/sites-available/3dprint-omsk.conf

# 3. Enable site
sudo ln -s /etc/nginx/sites-available/3dprint-omsk.conf /etc/nginx/sites-enabled/

# 4. Test configuration
sudo nginx -t

# 5. Reload Nginx
sudo systemctl reload nginx
```

### Apache

```bash
# 1. Copy file
sudo cp apache.3dprint-omsk.conf /etc/apache2/sites-available/3dprint-omsk.conf

# 2. Enable required modules
sudo a2enmod rewrite ssl headers expires deflate http2

# 3. Customize paths (DocumentRoot, SSL certificates)
sudo nano /etc/apache2/sites-available/3dprint-omsk.conf

# 4. Enable site
sudo a2ensite 3dprint-omsk.conf

# 5. Test configuration
sudo apachectl configtest

# 6. Reload Apache
sudo systemctl reload apache2
```

### Shared Hosting (.htaccess)

```bash
# Copy to project root
cp .htaccess.example /path/to/site/.htaccess
```

## Customization Required

Before deploying, you **must** customize these values:

### Nginx (`nginx.3dprint-omsk.conf`)

**Line 28**: PHP-FPM socket path
```nginx
server unix:/run/php/php7.4-fpm.sock;
# Change to match your PHP version:
# - Ubuntu/Debian PHP 7.4: /run/php/php7.4-fpm.sock
# - Ubuntu/Debian PHP 8.1: /run/php/php8.1-fpm.sock
# - Ubuntu/Debian PHP 8.2: /run/php/php8.2-fpm.sock
```

**Line 91**: Project root
```nginx
root /var/www/3dprint-omsk.ru;
# Change to your actual project path
```

**Lines 64-66, 99-101**: SSL certificate paths
```nginx
ssl_certificate /etc/letsencrypt/live/3dprint-omsk.ru/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/3dprint-omsk.ru/privkey.pem;
ssl_trusted_certificate /etc/letsencrypt/live/3dprint-omsk.ru/chain.pem;
# These paths are set after running certbot
```

**Line 120**: Security snippet include path
```nginx
include /etc/nginx/snippets/3dprint-security.conf;
# Ensure this matches where you copied security.conf
```

### Apache (`apache.3dprint-omsk.conf`)

**Line 64**: DocumentRoot
```apache
DocumentRoot /var/www/3dprint-omsk.ru
# Change to your actual project path
```

**Lines 41, 71-73, 92-93**: SSL certificate paths
```apache
SSLCertificateFile /etc/letsencrypt/live/3dprint-omsk.ru/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/3dprint-omsk.ru/privkey.pem
# These paths are set after running certbot
```

**Lines 119, 166**: Directory paths
```apache
<Directory /var/www/3dprint-omsk.ru>
<DirectoryMatch "^/var/www/3dprint-omsk.ru/(storage|database|...)">
# Change to match your DocumentRoot
```

## Finding PHP-FPM Socket

```bash
# Method 1: Check running PHP-FPM
sudo systemctl status php*-fpm

# Method 2: Search for socket files
sudo find /run -name "php*-fpm.sock"

# Method 3: Check PHP-FPM pool configuration
sudo grep -r "listen = " /etc/php/*/fpm/pool.d/
```

## SSL Certificate Setup

Obtain Let's Encrypt certificate with certbot:

```bash
# For Nginx
sudo certbot --nginx -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# For Apache
sudo certbot --apache -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# Test automatic renewal
sudo certbot renew --dry-run
```

## Verification

### Test Configuration Syntax

```bash
# Nginx
sudo nginx -t

# Apache
sudo apachectl configtest
```

### Test Security Headers

```bash
# Check security headers
curl -I https://3dprint-omsk.ru | grep -i "x-frame-options\|strict-transport\|content-security"

# Test redirects
curl -I http://3dprint-omsk.ru  # Should redirect to HTTPS
curl -I https://www.3dprint-omsk.ru  # Should redirect to apex

# Test sensitive file blocking
curl -I https://3dprint-omsk.ru/.env  # Should be 403 or 404
curl -I https://3dprint-omsk.ru/composer.json  # Should be 403 or 404
```

### Online Testing

- [SSL Labs SSL Test](https://www.ssllabs.com/ssltest/) - Expected grade: A or A+
- [Security Headers](https://securityheaders.com) - Expected grade: A or A+
- [Mozilla Observatory](https://observatory.mozilla.org) - Expected grade: A or higher

## Documentation

For complete setup instructions, see:

📖 **[docs/WEB_SERVER_CONFIG.md](../../docs/WEB_SERVER_CONFIG.md)** - Comprehensive guide covering:

- Nginx/Apache installation and configuration
- DNS setup (A/AAAA, MX, SPF, DKIM, DMARC records)
- SSL certificate issuance and renewal
- Security header verification
- Rate limiting and firewall configuration
- Troubleshooting and maintenance

## Alignment with SecurityHeaders Class

The configuration templates are aligned with the `SecurityHeaders` class in `api/helpers/security_headers.php`:

### Security Headers Mapping

| Header | Nginx/Apache Config | SecurityHeaders Class |
|--------|---------------------|----------------------|
| X-Content-Type-Options | `nosniff` | ✅ Matches |
| X-Frame-Options | `DENY` | ✅ Matches |
| X-XSS-Protection | `1; mode=block` | ✅ Matches |
| Referrer-Policy | `strict-origin-when-cross-origin` | ✅ Matches |
| Permissions-Policy | Disables camera, mic, geo, etc. | ✅ Matches |
| HSTS | `max-age=31536000; includeSubDomains; preload` | ✅ Matches |
| Content-Security-Policy | PUBLIC context policy | ✅ Matches |

### Context-Aware CSP

The web server config uses the **PUBLIC** context CSP by default, which allows:
- Google Analytics (`www.google-analytics.com`)
- Yandex Metrica (`mc.yandex.ru`)
- YouTube embeds (`www.youtube.com`)
- Google Fonts (`fonts.googleapis.com`, `fonts.gstatic.com`)

For the **admin panel**, the PHP `SecurityHeaders` class applies a stricter policy at runtime.

For **API endpoints**, PHP headers take precedence with minimal CSP (`default-src 'none'`).

## Notes

### Nginx vs Apache

- **Nginx**: Better performance for static files and concurrent connections
- **Apache**: Easier to configure, better `.htaccess` support for shared hosting

### Shared Hosting Limitations

The `.htaccess.example` file provides a subset of features for shared hosting:
- ✅ HTTPS redirect
- ✅ www → apex redirect
- ✅ Security headers
- ✅ Compression
- ✅ Browser caching
- ❌ No upstream PHP-FPM control
- ❌ Performance may be lower than VPS with Nginx

### Performance Optimization

For high-traffic production sites:
- Enable HTTP/2 (included in configs)
- Enable Brotli compression (commented out in Nginx config)
- Use CDN for static assets (CloudFlare, BunnyCDN)
- Enable PHP OPcache (see [WEB_SERVER_CONFIG.md](../../docs/WEB_SERVER_CONFIG.md))
- Consider Redis for session storage (see `.env.production.example`)

## Troubleshooting

### SSE (Server-Sent Events) Issues

If you encounter Content-Type header issues with the SSE endpoint:
- See [docs/SSE_TROUBLESHOOTING.md](../../docs/SSE_TROUBLESHOOTING.md) for detailed fixes
- Common symptom: EventSource fails with "MIME type mismatch" error
- Fix: Ensure web server config doesn't override PHP's Content-Type header

### General Support

For issues or questions:

1. Check [docs/WEB_SERVER_CONFIG.md](../../docs/WEB_SERVER_CONFIG.md) troubleshooting section
2. Review error logs:
   - Nginx: `/var/log/nginx/3dprint-omsk.ru.error.log`
   - Apache: `/var/log/apache2/3dprint-omsk.ru.error.log`
3. Test configuration syntax: `nginx -t` or `apachectl configtest`
4. Verify DNS propagation: `dig 3dprint-omsk.ru A`
5. Check SSL certificate: `certbot certificates`

---

**Version**: 1.0  
**Last Updated**: 2024-01-20  
**Compatibility**: Nginx 1.18+, Apache 2.4+, PHP 7.4+
