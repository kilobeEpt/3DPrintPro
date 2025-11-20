# Hosting Readiness Audit Guide

Comprehensive guide for validating hosting environment compatibility before deploying 3D Print Pro.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Running the Audit](#running-the-audit)
4. [Interpreting Results](#interpreting-results)
5. [Remediation Guide](#remediation-guide)
6. [CI/CD Integration](#cicd-integration)
7. [Troubleshooting](#troubleshooting)

## Overview

The hosting audit utility (`scripts/hosting-audit.php`) performs comprehensive validation of your hosting environment to ensure all requirements for 3D Print Pro are met before deployment.

### What It Checks

The audit validates:

- **PHP Runtime**: Version 7.4 or higher
- **PHP Extensions**: Required and optional extensions
- **CLI Tools**: Command-line utilities for deployment and maintenance
- **Services**: MySQL database server and optional Redis cache
- **Resources**: Disk space, memory, and PHP limits
- **Permissions**: Write access to critical directories
- **File System**: Project root write access for SSH user

### Why Run This Audit

Running the hosting audit **before deployment**:

- ✅ Identifies missing dependencies early
- ✅ Prevents deployment failures
- ✅ Provides actionable remediation steps
- ✅ Validates shared hosting compatibility
- ✅ Generates reports for deployment tickets
- ✅ Ensures optimal performance

## Prerequisites

### Minimum Requirements

Before running the audit, ensure you have:

1. **SSH Access**: Terminal access to your hosting server
2. **PHP CLI**: PHP 7.4+ command-line interface
3. **File Upload**: Ability to upload files via SFTP/SCP
4. **Basic Commands**: Access to standard Unix commands

### Upload the Script

If deploying to a new server, upload the audit script first:

```bash
# Via SCP
scp scripts/hosting-audit.php user@your-server.com:/path/to/project/scripts/

# Via SFTP
sftp user@your-server.com
put scripts/hosting-audit.php /path/to/project/scripts/
chmod +x /path/to/project/scripts/hosting-audit.php
```

For shared hosting, use your hosting panel's file manager to upload the script.

## Running the Audit

### Basic Usage

```bash
# Navigate to project root
cd /path/to/project

# Run audit with default settings
php scripts/hosting-audit.php
```

### Command Options

#### JSON Output (CI/CD)

Generate machine-readable output for automation:

```bash
php scripts/hosting-audit.php --format=json > audit-report.json
```

#### Strict Mode

Treat warnings as failures (recommended for production):

```bash
php scripts/hosting-audit.php --strict
```

#### Shared Hosting Mode

Skip Redis checks for shared hosting environments:

```bash
php scripts/hosting-audit.php --skip-redis
```

#### Check Specific Extensions

Validate only required extensions:

```bash
php scripts/hosting-audit.php --assert pdo_mysql,mbstring,json
```

#### Combined Options

```bash
# Shared hosting with JSON output
php scripts/hosting-audit.php --skip-redis --format=json

# Strict mode without Redis
php scripts/hosting-audit.php --strict --skip-redis
```

### Exit Codes

The script uses standard exit codes:

- `0` - All required checks passed (ready for deployment)
- `1` - One or more required checks failed (not ready)
- `2` - Invalid usage or arguments (check syntax)

Example usage in scripts:

```bash
if php scripts/hosting-audit.php; then
    echo "Environment ready for deployment"
    ./deploy.sh
else
    echo "Environment not ready. Check audit output."
    exit 1
fi
```

## Interpreting Results

### Output Format

The human-readable output includes:

```
================================================================================
                       HOSTING READINESS AUDIT
                         3D Print Pro Platform
================================================================================

Timestamp: 2025-01-19 14:30:00
Hostname:  production-server
PHP:       8.1.2

--------------------------------------------------------------------------------
  PHP Runtime
--------------------------------------------------------------------------------
  PHP Version                                          ✓ PASS
    Requirement: >= 7.4.0
    Actual:      8.1.2

--------------------------------------------------------------------------------
  PHP Extensions
--------------------------------------------------------------------------------
  Extension: pdo_mysql                                 ✓ PASS [CRITICAL]
    Requirement: Loaded
    Actual:      Loaded
    Description: MySQL database connectivity

  Extension: imagick                                   ⚠ WARN
    Requirement: Loaded
    Actual:      Not loaded
    Description: Advanced image processing (optional)
    Remediation: Optional: Install PHP extension: imagick for enhanced functionality

[... more checks ...]

================================================================================
                                  SUMMARY
================================================================================

  Total Checks:    25
  Passed:          22  ✓ PASS
  Failed:           0  ✗ FAIL
  Warnings:         3  ⚠ WARN

  Overall Status: ⚠ WARN

  ⚠️  HOSTING ENVIRONMENT PARTIALLY READY
  Some optional features may not be available.

================================================================================
```

### Status Indicators

Each check has one of three statuses:

| Status | Symbol | Meaning | Action Required |
|--------|--------|---------|----------------|
| `PASS` | ✓ | Check passed | None |
| `FAIL` | ✗ | Critical issue | Must fix before deployment |
| `WARN` | ⚠ | Non-critical issue | Optional, recommended to fix |

### Critical vs Optional

Checks marked `[CRITICAL]` must pass for deployment. Non-critical checks enable optional features:

- **Critical**: PHP version, PDO MySQL, core extensions, storage permissions
- **Optional**: ImageMagick, Redis, Node.js, advanced tools

## Remediation Guide

### PHP Version Issues

**Problem**: PHP version < 7.4

**Solution**:

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install php8.1-cli

# CentOS/RHEL
sudo yum install php81-cli

# Shared hosting: Contact your provider to upgrade PHP version
```

### Missing PHP Extensions

#### Core Extensions (Required)

```bash
# Ubuntu/Debian
sudo apt-get install php8.1-pdo php8.1-mysql php8.1-mbstring \
    php8.1-intl php8.1-json php8.1-curl php8.1-openssl php8.1-zip

# CentOS/RHEL
sudo yum install php81-pdo php81-mysqlnd php81-mbstring \
    php81-intl php81-json php81-curl php81-openssl php81-zip

# Restart PHP
sudo systemctl restart php8.1-fpm
```

#### Optional Extensions

```bash
# GD (image processing)
sudo apt-get install php8.1-gd

# ImageMagick (advanced image processing)
sudo apt-get install php8.1-imagick
```

### CLI Tools

#### Composer

```bash
# Download and install globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verify
composer --version
```

#### MySQL Client

```bash
# Ubuntu/Debian
sudo apt-get install mysql-client

# CentOS/RHEL
sudo yum install mysql
```

#### Node.js & npm (Optional)

```bash
# Using NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify
node --version
npm --version
```

#### Redis CLI (Optional)

```bash
# Ubuntu/Debian
sudo apt-get install redis-tools

# CentOS/RHEL
sudo yum install redis
```

#### Certbot (Optional)

```bash
# Ubuntu/Debian
sudo apt-get install certbot python3-certbot-apache

# CentOS/RHEL
sudo yum install certbot python3-certbot-apache
```

### Service Issues

#### MySQL Not Running

```bash
# Check status
systemctl status mysql

# Start service
sudo systemctl start mysql

# Enable on boot
sudo systemctl enable mysql
```

#### Redis Not Running (Optional)

```bash
# Install Redis
sudo apt-get install redis-server

# Start service
sudo systemctl start redis

# Enable on boot
sudo systemctl enable redis
```

### Resource Issues

#### Low Disk Space

```bash
# Check disk usage
df -h

# Find large files
du -sh /* | sort -hr | head -n 10

# Clean package cache
sudo apt-get clean
sudo apt-get autoclean

# Remove old logs
sudo journalctl --vacuum-time=7d
```

#### Low Memory

```bash
# Check memory usage
free -m

# Check processes
top -o %MEM

# For cloud hosting: Upgrade your plan
# For VPS: Add swap space (if needed)
```

#### PHP Limits Too Low

Edit `php.ini` (location varies by system):

```ini
; Increase memory limit
memory_limit = 256M

; Increase execution time
max_execution_time = 60

; Increase upload size (for media)
upload_max_filesize = 10M
post_max_size = 10M
```

Find your `php.ini`:

```bash
php --ini | grep "Loaded Configuration File"
```

Restart PHP after changes:

```bash
sudo systemctl restart php8.1-fpm
```

### Permission Issues

#### Storage Directories Not Writable

```bash
cd /path/to/project

# Create missing directories
mkdir -p storage/cache storage/uploads storage/backups logs

# Set permissions
chmod 755 storage logs
chmod 755 storage/cache storage/uploads storage/backups

# Ensure web server can write
sudo chown -R www-data:www-data storage logs

# Or for your user
sudo chown -R $USER:$USER storage logs
```

#### Project Root Not Writable

```bash
# For dedicated server
sudo chown -R your-user:your-user /path/to/project

# For shared hosting
# Contact support to adjust permissions
```

### Shared Hosting Adjustments

For shared hosting environments, some checks may not apply:

```bash
# Skip Redis and optional services
php scripts/hosting-audit.php --skip-redis

# Focus on critical requirements only
php scripts/hosting-audit.php --assert pdo_mysql,mbstring,intl,json,curl,openssl,zip
```

## CI/CD Integration

### GitHub Actions

```yaml
name: Hosting Audit

on:
  push:
    branches: [ main, staging ]

jobs:
  audit:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: pdo_mysql, mbstring, intl, json, curl, openssl, zip
      
      - name: Run Hosting Audit
        run: php scripts/hosting-audit.php --format=json > audit-report.json
      
      - name: Upload Audit Report
        uses: actions/upload-artifact@v3
        with:
          name: hosting-audit-report
          path: audit-report.json
      
      - name: Check Audit Results
        run: |
          if php scripts/hosting-audit.php; then
            echo "✅ Hosting audit passed"
          else
            echo "❌ Hosting audit failed"
            exit 1
          fi
```

### GitLab CI

```yaml
hosting-audit:
  stage: test
  image: php:8.1-cli
  script:
    - apt-get update && apt-get install -y php-pdo-mysql php-mbstring php-intl
    - php scripts/hosting-audit.php --format=json | tee audit-report.json
    - php scripts/hosting-audit.php
  artifacts:
    paths:
      - audit-report.json
    expire_in: 1 week
  only:
    - main
    - staging
```

### Pre-Deployment Script

Create `pre-deploy.sh`:

```bash
#!/bin/bash
set -e

echo "Running hosting audit..."

if php scripts/hosting-audit.php --strict; then
    echo "✅ Hosting audit passed. Proceeding with deployment."
    exit 0
else
    echo "❌ Hosting audit failed. Deployment blocked."
    echo "Review audit output above and fix issues before deploying."
    exit 1
fi
```

Make executable and use before deployment:

```bash
chmod +x pre-deploy.sh
./pre-deploy.sh && ./deploy.sh
```

## Attaching Reports to Deployment Tickets

### Generate Report for Ticket

```bash
# Human-readable report
php scripts/hosting-audit.php > hosting-audit-report.txt

# JSON report for parsing
php scripts/hosting-audit.php --format=json > hosting-audit-report.json
```

### Ticket Template

When creating deployment tickets, include:

```markdown
## Pre-Deployment Checklist

- [ ] Hosting audit completed
- [ ] Audit report attached
- [ ] All CRITICAL checks passed
- [ ] Warnings reviewed and acceptable

### Audit Summary

- **Date**: 2025-01-19 14:30:00
- **Hostname**: production-server
- **PHP Version**: 8.1.2
- **Total Checks**: 25
- **Passed**: 22
- **Failed**: 0
- **Warnings**: 3
- **Overall Status**: READY ✅

**Warnings (Optional features)**:
- ImageMagick not installed (optional image processing)
- Redis not running (optional caching)
- Node.js not installed (optional build tools)

**Action**: Proceed with deployment. Optional features documented.

**Audit Report**: See attached `hosting-audit-report.txt`
```

### Automated Ticket Updates

Use CI/CD to automatically attach reports:

```bash
# In your CI/CD pipeline
php scripts/hosting-audit.php --format=json > report.json

# Parse and post to ticket system
curl -X POST "https://jira.example.com/rest/api/2/issue/DEPLOY-123/attachments" \
  -H "Authorization: Bearer $JIRA_TOKEN" \
  -F "file=@report.json"
```

## Troubleshooting

### Common Issues

#### "php: command not found"

**Cause**: PHP CLI not installed or not in PATH

**Solution**:

```bash
# Check if PHP is installed
which php || which php8.1 || which php7.4

# If found with version, create symlink
sudo ln -s /usr/bin/php8.1 /usr/bin/php

# If not found, install PHP CLI
sudo apt-get install php8.1-cli
```

#### "Permission denied" running script

**Cause**: Script not executable

**Solution**:

```bash
chmod +x scripts/hosting-audit.php
```

#### False positives on service checks

**Cause**: Services running but not detected by audit

**Solution**:

```bash
# Verify service manually
systemctl status mysql
ps aux | grep mysql

# If running, this is a detection issue (non-critical)
# Proceed with deployment and verify database connectivity separately
```

#### Shared hosting limitations

**Cause**: Restricted access to system commands

**Solution**:

```bash
# Use shared hosting mode
php scripts/hosting-audit.php --skip-redis

# Focus on PHP environment only
# Services/tools may be managed by hosting provider
```

### Getting Help

If the audit identifies issues you cannot resolve:

1. **Hosting Provider**: Contact support for server-level issues
2. **Documentation**: Review [DEPLOYMENT.md](DEPLOYMENT.md) for detailed setup
3. **System Admin**: Consult your system administrator for server configuration
4. **Issue Tracker**: Report bugs or improvements to the project repository

## Best Practices

### When to Run the Audit

- ✅ Before first deployment to new server
- ✅ After server upgrades or migrations
- ✅ Before major platform updates
- ✅ After PHP version changes
- ✅ Periodically (quarterly) for health checks

### Regular Audits

Set up a cron job for periodic audits:

```bash
# Add to crontab (weekly on Sundays at 2 AM)
0 2 * * 0 php /path/to/project/scripts/hosting-audit.php --format=json > /path/to/logs/weekly-audit.json
```

### Documentation

Keep audit reports with deployment records:

```
deployments/
├── 2025-01-15-production/
│   ├── audit-report.txt
│   ├── deployment-log.txt
│   └── config-backup.tar.gz
├── 2025-01-19-staging/
│   ├── audit-report.json
│   └── deployment-notes.md
```

## Hosting Requirements Summary

Based on audit checks, here are the complete hosting requirements:

### Mandatory Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP Version | 7.4 | 8.1+ |
| PHP Memory | 128M | 256M+ |
| Disk Space | 1 GB free | 5 GB+ free |
| System Memory | 256 MB free | 1 GB+ free |
| MySQL | 5.7+ | 8.0+ |
| Web Server | Apache/Nginx | Apache/Nginx with SSL |

### Required PHP Extensions

- `pdo_mysql` - Database connectivity
- `mbstring` - Multi-byte string support
- `intl` - Internationalization
- `json` - JSON processing
- `curl` - HTTP client
- `openssl` - Encryption
- `zip` - Archive handling

### Optional PHP Extensions

- `gd` - Basic image processing
- `imagick` - Advanced image manipulation

### Required CLI Tools

- `composer` - Dependency management
- `php` - PHP command-line
- `mysql` - Database client
- `mysqldump` - Database backups

### Optional CLI Tools

- `node` & `npm` - JavaScript tooling
- `redis-cli` - Cache management
- `certbot` - SSL automation

### File System Requirements

- `storage/` - Writable (755)
- `storage/cache/` - Writable (755)
- `storage/uploads/` - Writable (755)
- `storage/backups/` - Writable (755)
- `logs/` - Writable (755)
- Project root - Writable by SSH user

## Next Steps

After running the audit and resolving any issues:

1. ✅ Review audit report and confirm all CRITICAL checks pass
2. ✅ Address optional warnings if desired features needed
3. ✅ Attach audit report to deployment ticket
4. ✅ Proceed to [Step 2: Database Setup](DEPLOYMENT.md#step-2-database-setup) in deployment guide
5. ✅ Continue following [DEPLOYMENT.md](DEPLOYMENT.md) checklist

## Related Documentation

- [Deployment Guide](DEPLOYMENT.md) - Complete deployment process
- [Setup Guide](SETUP_GUIDE.md) - Initial project setup
- [Security Guide](SECURITY.md) - Security hardening
- [Troubleshooting](TROUBLESHOOTING.md) - Common issues and solutions

---

**Last Updated**: 2025-01-19  
**Version**: 1.0.0
