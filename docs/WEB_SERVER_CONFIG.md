# Web Server Configuration Guide

Complete guide for configuring Nginx/Apache, DNS, and SSL for 3dprint-omsk.ru.

## Table of Contents

- [Overview](#overview)
- [Configuration Templates](#configuration-templates)
- [Nginx Configuration](#nginx-configuration)
- [Apache Configuration](#apache-configuration)
- [DNS Configuration](#dns-configuration)
- [SSL Certificate Setup](#ssl-certificate-setup)
- [Security Verification](#security-verification)
- [Rate Limiting & Firewall](#rate-limiting--firewall)
- [Troubleshooting](#troubleshooting)
- [Maintenance](#maintenance)

---

## Overview

This guide covers **Steps 4–6** of the production deployment workflow:

- **Step 4**: DNS configuration (A/AAAA records, MX/SPF/DKIM)
- **Step 5**: SSL certificate issuance and renewal
- **Step 6**: Web server configuration (Nginx or Apache)

### Prerequisites

Before proceeding, ensure you have completed:

- ✅ **Step 1**: Hosting environment validation ([HOSTING_AUDIT.md](HOSTING_AUDIT.md))
- ✅ **Step 2**: Database provisioning ([DATABASE_OPERATIONS.md](DATABASE_OPERATIONS.md))
- ✅ **Step 3**: File deployment and configuration ([DEPLOYMENT.md](DEPLOYMENT.md))

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                       DNS Layer                              │
│  3dprint-omsk.ru (A) → Server IP                            │
│  www.3dprint-omsk.ru (CNAME) → 3dprint-omsk.ru             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Web Server Layer                          │
│  HTTP (80) → HTTPS (443) redirect                           │
│  www → apex domain redirect                                  │
│  SSL/TLS termination (Let's Encrypt)                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                          │
│  Static HTML files (/)                                       │
│  PHP-FPM 7.4+ (API, Admin)                                  │
│  SSE endpoint (/api/updates/stream.php)                     │
└─────────────────────────────────────────────────────────────┘
```

---

## Configuration Templates

All configuration templates are located in `deploy/webserver/`:

```
deploy/webserver/
├── nginx.3dprint-omsk.conf      # Full Nginx configuration
├── apache.3dprint-omsk.conf     # Full Apache configuration
├── .htaccess.example            # Shared hosting alternative
└── snippets/
    └── security.conf            # Nginx security headers snippet
```

### Template Features

✅ **Both domains supported**: `3dprint-omsk.ru` and `www.3dprint-omsk.ru`  
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

---

## Nginx Configuration

### Installation Steps

#### 1. Copy Configuration File

```bash
# Copy main configuration
sudo cp deploy/webserver/nginx.3dprint-omsk.conf /etc/nginx/sites-available/3dprint-omsk.conf

# Copy security snippet
sudo mkdir -p /etc/nginx/snippets
sudo cp deploy/webserver/snippets/security.conf /etc/nginx/snippets/3dprint-security.conf
```

#### 2. Customize Paths

Edit `/etc/nginx/sites-available/3dprint-omsk.conf`:

```nginx
# Update PHP-FPM socket path (line 23)
upstream php_fpm {
    # Check your PHP version:
    # php -v
    #
    # Ubuntu/Debian PHP 7.4:
    server unix:/run/php/php7.4-fpm.sock;
    
    # Ubuntu/Debian PHP 8.1:
    # server unix:/run/php/php8.1-fpm.sock;
    
    # Ubuntu/Debian PHP 8.2:
    # server unix:/run/php/php8.2-fpm.sock;
}

# Update project root (line 87)
root /var/www/3dprint-omsk.ru;  # Change to your actual path

# Update SSL certificate paths (lines 99-101)
ssl_certificate /etc/letsencrypt/live/3dprint-omsk.ru/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/3dprint-omsk.ru/privkey.pem;
ssl_trusted_certificate /etc/letsencrypt/live/3dprint-omsk.ru/chain.pem;
```

**Finding PHP-FPM socket:**

```bash
# Method 1: Check running PHP-FPM
sudo systemctl status php*-fpm

# Method 2: Search for socket files
sudo find /run -name "php*-fpm.sock"

# Method 3: Check PHP-FPM pool configuration
sudo grep -r "listen = " /etc/php/*/fpm/pool.d/
```

#### 3. Enable Site

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/3dprint-omsk.conf /etc/nginx/sites-enabled/

# Remove default site (optional)
sudo rm /etc/nginx/sites-enabled/default
```

#### 4. Test Configuration

```bash
# Test Nginx configuration syntax
sudo nginx -t

# Expected output:
# nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
# nginx: configuration file /etc/nginx/nginx.conf test is successful
```

**Common errors and fixes:**

```bash
# Error: "upstream php_fpm not found"
# Fix: Check PHP-FPM socket path is correct

# Error: "permission denied" for socket
# Fix: Ensure www-data user can access PHP-FPM socket
sudo chown www-data:www-data /run/php/php7.4-fpm.sock

# Error: SSL certificate not found
# Fix: Obtain SSL certificates first (see SSL section below)
```

#### 5. Reload Nginx

```bash
# Reload configuration
sudo systemctl reload nginx

# Or restart if needed
sudo systemctl restart nginx

# Check status
sudo systemctl status nginx
```

#### 6. Verify PHP-FPM

```bash
# Check PHP-FPM is running
sudo systemctl status php7.4-fpm

# Enable PHP-FPM to start on boot
sudo systemctl enable php7.4-fpm

# View PHP-FPM logs
sudo tail -f /var/log/php7.4-fpm.log
```

### Nginx Security Snippet

The `snippets/security.conf` file contains reusable security headers:

```nginx
# Include in any server block:
include /etc/nginx/snippets/3dprint-security.conf;
```

This adds:
- Content Security Policy (CSP)
- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy
- HSTS (over HTTPS)

### Testing Nginx Configuration

```bash
# Test with curl
curl -I https://3dprint-omsk.ru

# Check security headers
curl -I https://3dprint-omsk.ru | grep -i "x-frame-options\|strict-transport\|content-security"

# Test redirects
curl -I http://3dprint-omsk.ru  # Should redirect to HTTPS
curl -I https://www.3dprint-omsk.ru  # Should redirect to apex
```

---

## Apache Configuration

### Installation Steps

#### 1. Copy Configuration File

```bash
# Copy main configuration
sudo cp deploy/webserver/apache.3dprint-omsk.conf /etc/apache2/sites-available/3dprint-omsk.conf

# For shared hosting, use .htaccess instead
cp deploy/webserver/.htaccess.example .htaccess
```

#### 2. Enable Required Modules

```bash
# Enable essential modules
sudo a2enmod rewrite ssl headers expires deflate http2

# Check enabled modules
apache2ctl -M

# Expected modules:
# - rewrite_module
# - ssl_module
# - headers_module
# - expires_module
# - deflate_module
# - http2_module (if available)
```

#### 3. Customize Paths

Edit `/etc/apache2/sites-available/3dprint-omsk.conf`:

```apache
# Update project root (line 64)
DocumentRoot /var/www/3dprint-omsk.ru

# Update directory path (line 100)
<Directory /var/www/3dprint-omsk.ru>

# Update SSL certificate paths (lines 71-73, 92-93)
SSLCertificateFile /etc/letsencrypt/live/3dprint-omsk.ru/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/3dprint-omsk.ru/privkey.pem

# Update denied directories (line 153)
<DirectoryMatch "^/var/www/3dprint-omsk.ru/(storage|database|...)">
```

#### 4. Enable Site

```bash
# Enable the site
sudo a2ensite 3dprint-omsk.conf

# Disable default site (optional)
sudo a2dissite 000-default.conf
```

#### 5. Test Configuration

```bash
# Test Apache configuration syntax
sudo apachectl configtest

# Expected output:
# Syntax OK
```

**Common errors and fixes:**

```bash
# Error: "Invalid command 'Header'"
# Fix: Enable headers module
sudo a2enmod headers

# Error: "SSLCertificateFile: file does not exist"
# Fix: Obtain SSL certificates first (see SSL section below)

# Error: "Forbidden" when accessing site
# Fix: Check directory permissions
sudo chown -R www-data:www-data /var/www/3dprint-omsk.ru
sudo chmod -R 755 /var/www/3dprint-omsk.ru
```

#### 6. Reload Apache

```bash
# Reload configuration
sudo systemctl reload apache2

# Or restart if needed
sudo systemctl restart apache2

# Check status
sudo systemctl status apache2
```

### Shared Hosting (.htaccess)

For shared hosting environments without server config access:

```bash
# Copy .htaccess to project root
cp deploy/webserver/.htaccess.example /path/to/site/.htaccess

# Verify .htaccess is enabled
# Contact hosting provider if mod_rewrite is not working
```

**Note**: `.htaccess` files may have performance overhead. Use main Apache config when possible.

### Testing Apache Configuration

```bash
# Test with curl
curl -I https://3dprint-omsk.ru

# Check security headers
curl -I https://3dprint-omsk.ru | grep -i "x-frame-options\|strict-transport\|content-security"

# Test redirects
curl -I http://3dprint-omsk.ru  # Should redirect to HTTPS
curl -I https://www.3dprint-omsk.ru  # Should redirect to apex

# Check PHP is working
curl https://3dprint-omsk.ru/api/test.php
```

---

## DNS Configuration

### Overview

Configure DNS records to point `3dprint-omsk.ru` and `www.3dprint-omsk.ru` to your server.

### Required DNS Records

#### A Records (IPv4)

```
Type: A
Name: @
Value: YOUR_SERVER_IP
TTL: 3600

Type: A
Name: www
Value: YOUR_SERVER_IP
TTL: 3600
```

**Or use CNAME for www:**

```
Type: CNAME
Name: www
Value: 3dprint-omsk.ru
TTL: 3600
```

#### AAAA Records (IPv6, optional but recommended)

```
Type: AAAA
Name: @
Value: YOUR_SERVER_IPV6
TTL: 3600

Type: AAAA
Name: www
Value: YOUR_SERVER_IPV6
TTL: 3600
```

#### MX Records (Email)

For receiving email at `@3dprint-omsk.ru`:

```
Type: MX
Name: @
Priority: 10
Value: mail.3dprint-omsk.ru
TTL: 3600
```

**Or use external email provider (e.g., Google Workspace, Yandex):**

```
# Example for Yandex Mail
Type: MX
Name: @
Priority: 10
Value: mx.yandex.ru
TTL: 3600
```

#### SPF Record (Email Authentication)

Prevent email spoofing:

```
Type: TXT
Name: @
Value: v=spf1 mx ~all
TTL: 3600
```

**For specific mail servers:**

```
# Allow Yandex Mail to send email
v=spf1 include:_spf.yandex.net ~all

# Allow Google Workspace to send email
v=spf1 include:_spf.google.com ~all

# Allow specific server IP
v=spf1 ip4:YOUR_SERVER_IP ~all

# Multiple sources
v=spf1 mx ip4:YOUR_SERVER_IP include:_spf.yandex.net ~all
```

#### DKIM Record (Email Signing)

Generate DKIM keys for email authentication:

```bash
# Install OpenDKIM
sudo apt-get install opendkim opendkim-tools

# Generate DKIM keys
sudo opendkim-genkey -t -s mail -d 3dprint-omsk.ru

# View public key
sudo cat mail.txt
```

Add as TXT record:

```
Type: TXT
Name: mail._domainkey
Value: v=DKIM1; k=rsa; p=YOUR_PUBLIC_KEY
TTL: 3600
```

#### DMARC Record (Email Policy)

Define email authentication policy:

```
Type: TXT
Name: _dmarc
Value: v=DMARC1; p=quarantine; rua=mailto:dmarc@3dprint-omsk.ru
TTL: 3600
```

### DNS Provider-Specific Guides

#### Reg.ru

1. Login to [reg.ru](https://www.reg.ru)
2. Go to **Domains** → Select **3dprint-omsk.ru**
3. Click **DNS servers and Zone**
4. Add records as shown above

#### Cloudflare

1. Login to [Cloudflare](https://dash.cloudflare.com)
2. Select **3dprint-omsk.ru**
3. Go to **DNS** → **Records**
4. Add records as shown above
5. **Important**: Set **Proxy status** to **DNS only** (gray cloud) initially

#### Yandex.Connect

1. Login to [Yandex.Connect](https://connect.yandex.ru)
2. Select **3dprint-omsk.ru**
3. Go to **DNS settings**
4. Add records as shown above

### DNS Propagation

DNS changes take time to propagate globally:

- **Initial propagation**: 15 minutes to 2 hours
- **Full propagation**: Up to 48 hours
- **TTL impact**: Lower TTL = faster updates

### Verify DNS Configuration

#### Using dig

```bash
# Check A record (apex)
dig 3dprint-omsk.ru A

# Check A record (www)
dig www.3dprint-omsk.ru A

# Check AAAA record (IPv6)
dig 3dprint-omsk.ru AAAA

# Check MX record
dig 3dprint-omsk.ru MX

# Check TXT records (SPF, DMARC)
dig 3dprint-omsk.ru TXT
```

#### Using nslookup

```bash
# Check A record
nslookup 3dprint-omsk.ru

# Check specific record type
nslookup -type=MX 3dprint-omsk.ru
nslookup -type=TXT 3dprint-omsk.ru
```

#### Using ping

```bash
# Verify domain resolves to correct IP
ping 3dprint-omsk.ru
ping www.3dprint-omsk.ru
```

#### Online Tools

- [DNS Checker](https://dnschecker.org) - Global DNS propagation
- [MXToolbox](https://mxtoolbox.com) - Email and DNS diagnostics
- [IntoDNS](https://intodns.com) - Comprehensive DNS health check

### Expected DNS Results

```bash
$ dig 3dprint-omsk.ru A +short
YOUR_SERVER_IP

$ dig www.3dprint-omsk.ru A +short
YOUR_SERVER_IP

$ dig 3dprint-omsk.ru MX +short
10 mx.yandex.ru.

$ dig 3dprint-omsk.ru TXT +short
"v=spf1 mx ~all"
```

---

## SSL Certificate Setup

### Let's Encrypt with Certbot

Let's Encrypt provides free, automated SSL certificates with 90-day validity and automatic renewal.

#### Installation (Ubuntu/Debian)

```bash
# Update package list
sudo apt-get update

# Install Certbot for Nginx
sudo apt-get install certbot python3-certbot-nginx

# Or install Certbot for Apache
sudo apt-get install certbot python3-certbot-apache
```

#### Installation (CentOS/RHEL)

```bash
# Install EPEL repository
sudo yum install epel-release

# Install Certbot
sudo yum install certbot python3-certbot-nginx  # For Nginx
sudo yum install certbot python3-certbot-apache  # For Apache
```

### Obtain SSL Certificate (Nginx)

#### Method 1: Automatic (Recommended)

Certbot automatically configures Nginx:

```bash
# Obtain and install certificate
sudo certbot --nginx -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# Follow prompts:
# 1. Enter email address for urgent renewal notifications
# 2. Agree to terms of service (Y)
# 3. Share email with EFF (optional) (Y/N)
# 4. Choose redirect option:
#    - 1: No redirect (not recommended)
#    - 2: Redirect HTTP to HTTPS (recommended)
```

#### Method 2: Manual (Certificate Only)

Obtain certificate without auto-configuration:

```bash
# Obtain certificate only (no auto-configuration)
sudo certbot certonly --nginx -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# Certificate files will be saved to:
# /etc/letsencrypt/live/3dprint-omsk.ru/fullchain.pem
# /etc/letsencrypt/live/3dprint-omsk.ru/privkey.pem
```

#### Method 3: Webroot (Existing Server)

Use webroot plugin if server is already running:

```bash
# Ensure webroot is accessible
sudo mkdir -p /var/www/3dprint-omsk.ru/.well-known/acme-challenge

# Obtain certificate via webroot
sudo certbot certonly --webroot \
  -w /var/www/3dprint-omsk.ru \
  -d 3dprint-omsk.ru \
  -d www.3dprint-omsk.ru
```

#### Method 4: Standalone (No Server Running)

Use standalone plugin if no web server is running yet:

```bash
# Stop web server temporarily
sudo systemctl stop nginx

# Obtain certificate using standalone server
sudo certbot certonly --standalone \
  -d 3dprint-omsk.ru \
  -d www.3dprint-omsk.ru

# Start web server again
sudo systemctl start nginx
```

### Obtain SSL Certificate (Apache)

#### Method 1: Automatic (Recommended)

```bash
# Obtain and install certificate
sudo certbot --apache -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# Certbot will:
# 1. Obtain certificate
# 2. Configure Apache VirtualHosts
# 3. Enable SSL module
# 4. Restart Apache
```

#### Method 2: Manual

```bash
# Obtain certificate only
sudo certbot certonly --apache -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# Manually update Apache configuration with certificate paths
```

### Certificate File Locations

After successful issuance:

```
/etc/letsencrypt/live/3dprint-omsk.ru/
├── fullchain.pem       # Full certificate chain (use for ssl_certificate)
├── privkey.pem         # Private key (use for ssl_certificate_key)
├── chain.pem           # Intermediate certificates (use for ssl_trusted_certificate)
└── cert.pem            # Domain certificate only
```

### Test SSL Configuration

```bash
# Test HTTPS access
curl -I https://3dprint-omsk.ru

# Verify certificate details
openssl s_client -connect 3dprint-omsk.ru:443 -servername 3dprint-omsk.ru

# Check certificate expiry
echo | openssl s_client -servername 3dprint-omsk.ru -connect 3dprint-omsk.ru:443 2>/dev/null | openssl x509 -noout -dates
```

### Automatic Renewal

Let's Encrypt certificates expire after 90 days. Enable automatic renewal:

#### Check Renewal Works

```bash
# Dry run (test renewal without actual renewal)
sudo certbot renew --dry-run

# Expected output:
# Congratulations, all simulated renewals succeeded
```

#### Setup Automatic Renewal (Systemd)

Certbot installs a systemd timer for automatic renewal:

```bash
# Check timer status
sudo systemctl status certbot.timer

# Enable timer (if not already enabled)
sudo systemctl enable certbot.timer

# View timer schedule
sudo systemctl list-timers | grep certbot
```

#### Setup Automatic Renewal (Cron)

Alternative to systemd timer:

```bash
# Edit crontab
sudo crontab -e

# Add renewal job (runs twice daily)
0 0,12 * * * certbot renew --quiet --post-hook "systemctl reload nginx"
```

#### Renewal Hooks

Execute commands after successful renewal:

```bash
# Create renewal hook script
sudo nano /etc/letsencrypt/renewal-hooks/deploy/reload-webserver.sh

# Add content:
#!/bin/bash
systemctl reload nginx
# Or for Apache:
# systemctl reload apache2

# Make executable
sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-webserver.sh
```

### Manual Renewal

Force certificate renewal before expiry:

```bash
# Renew all certificates
sudo certbot renew

# Renew specific certificate
sudo certbot renew --cert-name 3dprint-omsk.ru

# Force renewal even if not due
sudo certbot renew --force-renewal
```

### Certificate Management

#### List Certificates

```bash
# List all certificates
sudo certbot certificates

# Example output:
# Certificate Name: 3dprint-omsk.ru
#   Domains: 3dprint-omsk.ru www.3dprint-omsk.ru
#   Expiry Date: 2024-03-15 12:00:00+00:00 (VALID: 89 days)
```

#### Revoke Certificate

```bash
# Revoke certificate
sudo certbot revoke --cert-path /etc/letsencrypt/live/3dprint-omsk.ru/cert.pem

# Revoke and delete
sudo certbot revoke --cert-path /etc/letsencrypt/live/3dprint-omsk.ru/cert.pem --delete-after-revoke
```

#### Delete Certificate

```bash
# Delete certificate (doesn't revoke it)
sudo certbot delete --cert-name 3dprint-omsk.ru
```

### Troubleshooting SSL

#### Common Errors

**Error: "Connection refused on port 80"**

```bash
# Check if port 80 is open
sudo netstat -tuln | grep :80

# Check firewall
sudo ufw status
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

**Error: "DNS problem: NXDOMAIN"**

```bash
# Verify DNS is configured correctly
dig 3dprint-omsk.ru A

# Wait for DNS propagation (up to 48 hours)
```

**Error: "Timeout during connect"**

```bash
# Check web server is running
sudo systemctl status nginx

# Check if port 80 is accessible externally
curl -I http://3dprint-omsk.ru
```

**Error: "Certificate verification failed"**

```bash
# Check certificate files exist
sudo ls -la /etc/letsencrypt/live/3dprint-omsk.ru/

# Verify certificate is valid
sudo certbot certificates
```

### SSL Best Practices

✅ **Always redirect HTTP to HTTPS** (handled by templates)  
✅ **Use HTTP/2** for better performance  
✅ **Enable OCSP stapling** for faster SSL handshakes  
✅ **Set long HSTS max-age** (1 year minimum)  
✅ **Test with SSL Labs** (see Security Verification section)  
✅ **Monitor certificate expiry** (Certbot sends email warnings)  
✅ **Keep private keys secure** (600 permissions, root-only access)

---

## Security Verification

### Verify Security Headers

Use curl to check security headers are applied:

```bash
# Check all security headers
curl -I https://3dprint-omsk.ru

# Check specific headers
curl -I https://3dprint-omsk.ru | grep -i "x-frame-options"
curl -I https://3dprint-omsk.ru | grep -i "strict-transport-security"
curl -I https://3dprint-omsk.ru | grep -i "content-security-policy"
curl -I https://3dprint-omsk.ru | grep -i "permissions-policy"
curl -I https://3dprint-omsk.ru | grep -i "referrer-policy"
```

**Expected headers:**

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=(), ...
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' ...
```

### SSL Labs Test

Test SSL configuration with SSL Labs:

1. Visit [SSL Labs SSL Test](https://www.ssllabs.com/ssltest/)
2. Enter domain: `3dprint-omsk.ru`
3. Click **Submit**
4. Wait for test to complete (2-5 minutes)

**Expected grade: A or A+**

#### Achieving A+ Rating

- ✅ TLS 1.2 and 1.3 only (no TLS 1.0/1.1)
- ✅ Strong cipher suites
- ✅ HSTS enabled with long max-age
- ✅ OCSP stapling enabled
- ✅ Forward secrecy supported

### Security Headers Test

Test security headers with Security Headers:

1. Visit [Security Headers](https://securityheaders.com)
2. Enter URL: `https://3dprint-omsk.ru`
3. Click **Scan**

**Expected grade: A or A+**

### Mozilla Observatory Test

Comprehensive security test:

1. Visit [Mozilla Observatory](https://observatory.mozilla.org)
2. Enter domain: `3dprint-omsk.ru`
3. Click **Scan Me**

**Expected grade: A or higher**

### Manual Security Checks

#### Verify HTTPS Redirect

```bash
# HTTP should redirect to HTTPS
curl -I http://3dprint-omsk.ru

# Expected:
# HTTP/1.1 301 Moved Permanently
# Location: https://3dprint-omsk.ru/
```

#### Verify www Redirect

```bash
# www should redirect to apex
curl -I https://www.3dprint-omsk.ru

# Expected:
# HTTP/2 301
# location: https://3dprint-omsk.ru/
```

#### Verify Sensitive Files Blocked

```bash
# .env should be blocked
curl -I https://3dprint-omsk.ru/.env
# Expected: 403 Forbidden or 404 Not Found

# .git should be blocked
curl -I https://3dprint-omsk.ru/.git/config
# Expected: 403 Forbidden

# composer.json should be blocked
curl -I https://3dprint-omsk.ru/composer.json
# Expected: 403 Forbidden

# storage/ should be blocked (except uploads/)
curl -I https://3dprint-omsk.ru/storage/cache/settings.json
# Expected: 403 Forbidden

# uploads/ should be accessible
curl -I https://3dprint-omsk.ru/storage/uploads/portfolio/sample.jpg
# Expected: 200 OK (if file exists)
```

#### Verify PHP Files

```bash
# API endpoint should work
curl https://3dprint-omsk.ru/api/test.php

# Expected:
# {"success":true,"database_status":"Connected",...}

# Admin login should load
curl -I https://3dprint-omsk.ru/admin/login.php

# Expected: 200 OK
```

#### Verify SSE Endpoint

```bash
# SSE endpoint should have special headers
curl -I https://3dprint-omsk.ru/api/updates/stream.php

# Expected headers:
# Content-Type: text/event-stream
# Cache-Control: no-cache
# X-Accel-Buffering: no
```

**Troubleshooting**: If you see `Content-Type: text/html` or `application/json` instead of `text/event-stream`, see [SSE Troubleshooting Guide](SSE_TROUBLESHOOTING.md) for detailed fixes.

### Automated Security Scanning

#### Nikto Web Scanner

```bash
# Install nikto
sudo apt-get install nikto

# Scan website
nikto -h https://3dprint-omsk.ru

# Review results for vulnerabilities
```

#### OWASP ZAP

1. Download [OWASP ZAP](https://www.zaproxy.org/)
2. Launch ZAP
3. Enter URL: `https://3dprint-omsk.ru`
4. Click **Automated Scan**
5. Review findings

---

## Rate Limiting & Firewall

### Application-Level Rate Limiting

The application includes built-in rate limiting (see [SECURITY.md](SECURITY.md)):

- **Authentication**: 5 requests per 15 minutes
- **API reads**: 100 requests per minute
- **API writes**: 30 requests per minute
- **Admin**: 60 requests per minute
- **Public**: 60 requests per minute

This is handled by `RateLimiter` class and logged to `admin_action_logs`.

### Nginx Rate Limiting

Add rate limiting to Nginx configuration:

```nginx
# Add to http block in /etc/nginx/nginx.conf
http {
    # Define rate limit zones
    limit_req_zone $binary_remote_addr zone=api_limit:10m rate=100r/m;
    limit_req_zone $binary_remote_addr zone=admin_limit:10m rate=60r/m;
    limit_req_zone $binary_remote_addr zone=login_limit:10m rate=5r/m;
    
    # ... rest of config
}
```

Then in your server block:

```nginx
# Apply to API endpoints
location ^~ /api/ {
    limit_req zone=api_limit burst=20 nodelay;
    # ... rest of config
}

# Apply to admin panel
location ^~ /admin/ {
    limit_req zone=admin_limit burst=10 nodelay;
    # ... rest of config
}

# Strict limit on login endpoint
location = /admin/login-handler.php {
    limit_req zone=login_limit burst=2 nodelay;
    # ... rest of config
}
```

### Apache Rate Limiting (mod_evasive)

Install and configure mod_evasive:

```bash
# Install mod_evasive
sudo apt-get install libapache2-mod-evasive

# Enable module
sudo a2enmod evasive

# Configure
sudo nano /etc/apache2/mods-enabled/evasive.conf
```

Add configuration:

```apache
<IfModule mod_evasive20.c>
    DOSHashTableSize 3097
    DOSPageCount 10         # Max 10 requests to same page per interval
    DOSSiteCount 100        # Max 100 requests to site per interval
    DOSPageInterval 1       # Page interval in seconds
    DOSSiteInterval 1       # Site interval in seconds
    DOSBlockingPeriod 10    # Block IP for 10 seconds
    
    # Email notification
    DOSEmailNotify admin@3dprint-omsk.ru
    
    # Log directory
    DOSLogDir /var/log/apache2/mod_evasive
</IfModule>
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

### Firewall Configuration (UFW)

Configure UFW firewall for basic protection:

```bash
# Install UFW (Ubuntu/Debian)
sudo apt-get install ufw

# Default policies
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH (IMPORTANT: do this first!)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow MySQL (only if needed externally)
# sudo ufw allow 3306/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status verbose
```

### Firewall Configuration (firewalld)

For CentOS/RHEL using firewalld:

```bash
# Start firewalld
sudo systemctl start firewalld
sudo systemctl enable firewalld

# Allow HTTP and HTTPS
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https

# Allow SSH
sudo firewall-cmd --permanent --add-service=ssh

# Reload firewall
sudo firewall-cmd --reload

# Check status
sudo firewall-cmd --list-all
```

### Fail2Ban Configuration

Protect against brute-force attacks:

```bash
# Install Fail2Ban
sudo apt-get install fail2ban

# Create local configuration
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local
```

Configure Fail2Ban:

```ini
[DEFAULT]
bantime = 3600          # Ban for 1 hour
findtime = 600          # 10 minute window
maxretry = 5            # 5 attempts before ban
destemail = admin@3dprint-omsk.ru
sendername = Fail2Ban

[nginx-http-auth]
enabled = true

[nginx-noscript]
enabled = true

[nginx-badbots]
enabled = true

[nginx-noproxy]
enabled = true

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
logpath = /var/log/nginx/*error.log
```

Create custom filter for admin login attempts:

```bash
sudo nano /etc/fail2ban/filter.d/nginx-admin-login.conf
```

Add content:

```ini
[Definition]
failregex = ^<HOST> .* "POST /admin/login-handler.php HTTP.*" 401
ignoreregex =
```

Add jail:

```bash
sudo nano /etc/fail2ban/jail.local
```

Add at end:

```ini
[nginx-admin-login]
enabled = true
filter = nginx-admin-login
logpath = /var/log/nginx/3dprint-omsk.ru.access.log
maxretry = 5
bantime = 3600
```

Start Fail2Ban:

```bash
# Start service
sudo systemctl start fail2ban
sudo systemctl enable fail2ban

# Check status
sudo fail2ban-client status

# Check specific jail
sudo fail2ban-client status nginx-admin-login

# Unban IP (if needed)
sudo fail2ban-client set nginx-admin-login unbanip 1.2.3.4
```

### IP Whitelisting (Admin Access)

Restrict admin panel to specific IPs:

#### Nginx

```nginx
# Add to server block
location ^~ /admin/ {
    # Allow specific IPs
    allow 1.2.3.4;           # Your office IP
    allow 5.6.7.8;           # Your home IP
    deny all;                 # Deny everyone else
    
    # ... rest of config
}
```

#### Apache

```apache
# Add to admin directory block
<Location /admin>
    # Allow specific IPs
    Require ip 1.2.3.4       # Your office IP
    Require ip 5.6.7.8       # Your home IP
</Location>
```

#### .htaccess

```apache
# Add to .htaccess in /admin directory
<Files "*">
    Order Deny,Allow
    Deny from all
    Allow from 1.2.3.4
    Allow from 5.6.7.8
</Files>
```

---

## Troubleshooting

### Web Server Not Starting

#### Check Error Logs

```bash
# Nginx
sudo tail -f /var/log/nginx/error.log

# Apache
sudo tail -f /var/log/apache2/error.log
```

#### Check Configuration Syntax

```bash
# Nginx
sudo nginx -t

# Apache
sudo apachectl configtest
```

#### Check Port Conflicts

```bash
# Check what's using port 80
sudo netstat -tuln | grep :80

# Check what's using port 443
sudo netstat -tuln | grep :443

# Kill conflicting process (if safe)
sudo fuser -k 80/tcp
```

### PHP Not Processing

#### Check PHP-FPM Status

```bash
# Check service status
sudo systemctl status php7.4-fpm

# Restart PHP-FPM
sudo systemctl restart php7.4-fpm

# View logs
sudo tail -f /var/log/php7.4-fpm.log
```

#### Check Socket Permissions

```bash
# Check socket exists
ls -la /run/php/php7.4-fpm.sock

# Fix permissions if needed
sudo chown www-data:www-data /run/php/php7.4-fpm.sock
```

#### Check PHP Errors

Enable error display temporarily:

```bash
# Edit PHP-FPM pool config
sudo nano /etc/php/7.4/fpm/pool.d/www.conf

# Change:
php_flag[display_errors] = on
php_admin_value[error_log] = /var/log/fpm-php.www.log
php_admin_flag[log_errors] = on

# Restart PHP-FPM
sudo systemctl restart php7.4-fpm
```

### SSL Certificate Issues

#### Certificate Not Found

```bash
# Verify certificate files exist
sudo ls -la /etc/letsencrypt/live/3dprint-omsk.ru/

# Re-obtain certificate if missing
sudo certbot certonly --nginx -d 3dprint-omsk.ru -d www.3dprint-omsk.ru
```

#### Certificate Expired

```bash
# Check expiry date
sudo certbot certificates

# Renew expired certificate
sudo certbot renew --force-renewal

# Reload web server
sudo systemctl reload nginx  # or apache2
```

#### Mixed Content Warnings

Browser shows "Not Secure" despite HTTPS:

```bash
# Check for http:// resources in HTML/CSS/JS
grep -r "http://" /var/www/3dprint-omsk.ru/ --include="*.html" --include="*.css" --include="*.js"

# Update to https:// or use protocol-relative URLs (//)
```

### Permission Issues

#### 403 Forbidden Errors

```bash
# Check directory permissions
ls -la /var/www/3dprint-omsk.ru/

# Fix ownership
sudo chown -R www-data:www-data /var/www/3dprint-omsk.ru

# Fix permissions
sudo find /var/www/3dprint-omsk.ru -type d -exec chmod 755 {} \;
sudo find /var/www/3dprint-omsk.ru -type f -exec chmod 644 {} \;

# Writable directories
sudo chmod -R 775 /var/www/3dprint-omsk.ru/storage
sudo chmod -R 775 /var/www/3dprint-omsk.ru/logs
```

#### Upload Failures

```bash
# Check upload directory exists and is writable
sudo mkdir -p /var/www/3dprint-omsk.ru/storage/uploads
sudo chown -R www-data:www-data /var/www/3dprint-omsk.ru/storage
sudo chmod -R 775 /var/www/3dprint-omsk.ru/storage

# Check PHP upload limits
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

### Performance Issues

#### Enable Opcache

```bash
# Install PHP opcache
sudo apt-get install php7.4-opcache

# Configure opcache
sudo nano /etc/php/7.4/fpm/conf.d/10-opcache.ini
```

Add configuration:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

Restart PHP-FPM:

```bash
sudo systemctl restart php7.4-fpm
```

#### Monitor Resource Usage

```bash
# Check CPU and memory
htop

# Check disk I/O
iotop

# Check Nginx connections
sudo ss -s

# Check slow queries
sudo tail -f /var/log/mysql/mysql-slow.log
```

---

## Maintenance

### Regular Tasks

#### Weekly

- [ ] Check SSL certificate expiry
- [ ] Review web server error logs
- [ ] Monitor disk space usage
- [ ] Review Fail2Ban banned IPs

#### Monthly

- [ ] Update web server (Nginx/Apache)
- [ ] Update PHP-FPM
- [ ] Review security headers test results
- [ ] Test SSL configuration (SSL Labs)
- [ ] Review rate limiting logs

#### Quarterly

- [ ] Review and update firewall rules
- [ ] Audit IP whitelist (if used)
- [ ] Update security headers (CSP, Permissions-Policy)
- [ ] Penetration testing (Nikto, OWASP ZAP)

### Monitoring Setup

#### Server Status Page

Create simple status page:

```bash
# Nginx
sudo nano /usr/share/nginx/html/status.html
```

Add content:

```html
<!DOCTYPE html>
<html>
<head><title>Server Status</title></head>
<body>
    <h1>Server Online</h1>
    <p>Timestamp: <script>document.write(new Date());</script></p>
</body>
</html>
```

Configure Nginx:

```nginx
location = /status.html {
    root /usr/share/nginx/html;
    access_log off;
}
```

#### Uptime Monitoring

Use external monitoring service:

- [UptimeRobot](https://uptimerobot.com) - Free up to 50 monitors
- [Pingdom](https://www.pingdom.com) - Advanced monitoring
- [StatusCake](https://www.statuscake.com) - Free tier available

Monitor endpoints:
- `https://3dprint-omsk.ru/` (homepage)
- `https://3dprint-omsk.ru/api/test.php` (API health)
- `https://3dprint-omsk.ru/status.html` (server status)

### Log Rotation

Configure log rotation to prevent disk space issues:

```bash
# Create logrotate config
sudo nano /etc/logrotate.d/3dprint-omsk
```

Add content:

```
/var/log/nginx/3dprint-omsk.ru*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 `cat /var/run/nginx.pid`
    endscript
}
```

Test log rotation:

```bash
sudo logrotate -d /etc/logrotate.d/3dprint-omsk
sudo logrotate -f /etc/logrotate.d/3dprint-omsk
```

### Backup Web Server Configuration

```bash
# Create backup directory
sudo mkdir -p /backup/webserver

# Backup Nginx configuration
sudo tar -czf /backup/webserver/nginx-$(date +%Y%m%d).tar.gz /etc/nginx/

# Backup Apache configuration
sudo tar -czf /backup/webserver/apache-$(date +%Y%m%d).tar.gz /etc/apache2/

# Backup SSL certificates
sudo tar -czf /backup/webserver/ssl-$(date +%Y%m%d).tar.gz /etc/letsencrypt/

# Keep last 30 days of backups
find /backup/webserver -name "*.tar.gz" -mtime +30 -delete
```

Add to crontab for daily backups:

```bash
0 3 * * * tar -czf /backup/webserver/nginx-$(date +\%Y\%m\%d).tar.gz /etc/nginx/ 2>&1 >> /var/log/backup.log
```

---

## Related Documentation

- [DEPLOYMENT.md](DEPLOYMENT.md) - Complete deployment guide
- [PRODUCTION_RUNBOOK.md](PRODUCTION_RUNBOOK.md) - End-to-end production operations
- [HOSTING_AUDIT.md](HOSTING_AUDIT.md) - Hosting environment validation
- [DATABASE_OPERATIONS.md](DATABASE_OPERATIONS.md) - Database setup and management
- [SECURITY.md](SECURITY.md) - Security best practices and hardening

---

## Quick Reference

### Configuration Files

```
deploy/webserver/
├── nginx.3dprint-omsk.conf           # Full Nginx config
├── apache.3dprint-omsk.conf          # Full Apache config
├── .htaccess.example                 # Shared hosting alternative
└── snippets/
    └── security.conf                 # Nginx security headers
```

### Installation Commands

#### Nginx

```bash
sudo cp deploy/webserver/nginx.3dprint-omsk.conf /etc/nginx/sites-available/3dprint-omsk.conf
sudo cp deploy/webserver/snippets/security.conf /etc/nginx/snippets/3dprint-security.conf
sudo ln -s /etc/nginx/sites-available/3dprint-omsk.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Apache

```bash
sudo cp deploy/webserver/apache.3dprint-omsk.conf /etc/apache2/sites-available/3dprint-omsk.conf
sudo a2enmod rewrite ssl headers expires deflate http2
sudo a2ensite 3dprint-omsk.conf
sudo apachectl configtest
sudo systemctl reload apache2
```

#### SSL Certificate

```bash
# Nginx
sudo certbot --nginx -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# Apache
sudo certbot --apache -d 3dprint-omsk.ru -d www.3dprint-omsk.ru

# Test renewal
sudo certbot renew --dry-run
```

### Verification Commands

```bash
# Test Nginx configuration
sudo nginx -t

# Test Apache configuration
sudo apachectl configtest

# Check DNS
dig 3dprint-omsk.ru A
dig www.3dprint-omsk.ru A

# Check SSL
openssl s_client -connect 3dprint-omsk.ru:443 -servername 3dprint-omsk.ru

# Check security headers
curl -I https://3dprint-omsk.ru

# Test redirects
curl -I http://3dprint-omsk.ru  # Should redirect to HTTPS
curl -I https://www.3dprint-omsk.ru  # Should redirect to apex
```

---

**Document Version**: 1.0  
**Last Updated**: 2024-01-20  
**Deployment Phase**: Steps 4-6
