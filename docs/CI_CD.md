# CI/CD Pipeline Documentation

## Overview

The 3D Print Pro platform uses **GitHub Actions** for continuous integration and deployment. This document covers the complete CI/CD pipeline, including testing, deployment, rollback procedures, and monitoring integration.

## Table of Contents

1. [Pipeline Architecture](#pipeline-architecture)
2. [Required GitHub Secrets](#required-github-secrets)
3. [Deployment Workflow](#deployment-workflow)
4. [Rollback Procedures](#rollback-procedures)
5. [Manual Deployment](#manual-deployment)
6. [Monitoring & Logging](#monitoring--logging)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)

---

## Pipeline Architecture

### Workflow File

- **Location:** `.github/workflows/deploy.yml`
- **Triggers:**
  - Push to `main` branch (automatic deployment)
  - Manual trigger via `workflow_dispatch` (for rollbacks and manual deploys)

### Jobs Overview

#### 1. Test Job (`test`)

Runs comprehensive unit and integration tests to ensure code quality.

**Steps:**
- Checkout code from repository
- Setup PHP 8.1 with required extensions
- Cache and install Composer dependencies
- Create test environment (SQLite in-memory database)
- Execute PHPUnit test suite (`composer test`)
- Upload test results as artifacts

**PHP Version Strategy:**
- Tests run on PHP 8.1 for forward compatibility
- Production supports PHP 7.4+ (configured via `composer.json` platform)
- Ensures new code works on both current and future PHP versions

**Test Results:**
- Available as workflow artifacts for 7 days
- Includes test output and logs from `storage/logs/`

#### 2. Deploy Job (`deploy`)

Deploys code to production server after tests pass.

**Steps:**
1. Checkout code
2. Install production dependencies (`composer install --no-dev`)
3. Create deployment package (exclude dev files, cache, logs)
4. Configure SSH authentication
5. Create timestamped release directory on server
6. Sync files to new release directory
7. Setup shared directories (uploads, backups)
8. Run deployment script (`scripts/deploy.sh --ci`)
9. Activate new release (atomic symlink switch)
10. Run post-deployment smoke tests
11. Cleanup old releases (keep last 5)
12. Collect and upload deployment logs

**Environment Gate:**
- Requires manual approval via GitHub Environments (`production`)
- Protects against accidental deployments
- Configurable in repository settings

---

## Required GitHub Secrets

Configure these secrets in your GitHub repository settings (Settings → Secrets and variables → Actions → Repository secrets).

### Core Deployment Secrets

| Secret Name | Description | Example Value |
|-------------|-------------|---------------|
| `DEPLOY_HOST` | Production server hostname or IP | `3dprint-omsk.ru` or `192.168.1.100` |
| `DEPLOY_USER` | SSH username for deployment | `deploy` or `www-data` |
| `DEPLOY_PATH` | Absolute path to deployment directory | `/var/www/3dprint-omsk.ru` |
| `SSH_KEY` | Private SSH key for authentication | `-----BEGIN RSA PRIVATE KEY-----...` |
| `ENVIRONMENT` | Environment name (for logging) | `production` |

### Optional Secrets

| Secret Name | Description | When Needed |
|-------------|-------------|-------------|
| `SLACK_WEBHOOK` | Slack webhook URL for notifications | If using Slack alerts |
| `SENTRY_DSN` | Sentry DSN for error tracking | If using Sentry |

### Setting Up SSH Key

1. **Generate SSH key pair** (on your local machine):
   ```bash
   ssh-keygen -t rsa -b 4096 -C "github-actions-deploy" -f deploy_key
   ```

2. **Add public key to server**:
   ```bash
   ssh-copy-id -i deploy_key.pub deploy@3dprint-omsk.ru
   ```
   
   Or manually:
   ```bash
   cat deploy_key.pub  # Copy output
   ssh deploy@3dprint-omsk.ru
   echo "paste-public-key-here" >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   ```

3. **Add private key to GitHub**:
   - Copy contents of `deploy_key` (the private key)
   - Go to GitHub repo → Settings → Secrets → New repository secret
   - Name: `SSH_KEY`
   - Value: Paste entire private key including `-----BEGIN` and `-----END` lines

4. **Secure your key**:
   ```bash
   # Delete local copies after adding to GitHub
   rm deploy_key deploy_key.pub
   ```

---

## Deployment Workflow

### Automatic Deployment (Push to main)

1. **Developer pushes to `main` branch**
   ```bash
   git checkout main
   git merge feature/my-feature
   git push origin main
   ```

2. **GitHub Actions automatically:**
   - Runs test suite
   - Waits for manual approval (production environment gate)
   - Deploys to production
   - Runs smoke tests
   - Reports results

3. **Monitor deployment:**
   - Go to GitHub repo → Actions tab
   - Click on the running workflow
   - Watch real-time logs

### Manual Deployment (workflow_dispatch)

Use for controlled deployments or rollbacks.

**Via GitHub UI:**

1. Go to **Actions** tab in GitHub repository
2. Select **Deploy to Production** workflow
3. Click **Run workflow** button
4. Configure options:
   - **Branch:** Select branch to deploy (usually `main`)
   - **Rollback release:** Leave empty for new deployment
   - **Skip tests:** Check only if emergency deployment needed
   - **Force deploy:** Check to bypass validation (use with caution)
5. Click **Run workflow**
6. Approve deployment in Environments page when prompted

**Via GitHub CLI:**

```bash
# Install GitHub CLI
brew install gh  # macOS
# or download from https://cli.github.com/

# Authenticate
gh auth login

# Trigger deployment
gh workflow run deploy.yml --ref main

# Trigger with options
gh workflow run deploy.yml --ref main \
  -f skip_tests=false \
  -f force_deploy=false
```

---

## Rollback Procedures

### Quick Rollback (Recommended)

Use GitHub Actions workflow for safe, audited rollbacks.

**Steps:**

1. **Identify previous release:**
   ```bash
   ssh deploy@3dprint-omsk.ru
   cd /var/www/3dprint-omsk.ru/releases
   ls -lt  # List releases by date (newest first)
   ```
   
   Output example:
   ```
   release_20240120_143022  # Current (broken)
   release_20240120_120530  # Previous (working)
   release_20240119_184512
   ```

2. **Trigger rollback via GitHub:**
   - Go to Actions → Deploy to Production → Run workflow
   - Enter rollback release name: `release_20240120_120530`
   - Leave other options default
   - Click Run workflow
   - Approve when prompted

3. **Verify rollback:**
   - Check deployment summary in Actions
   - Visit https://3dprint-omsk.ru and test functionality
   - Check deployment logs artifact

**What happens during rollback:**
- Symlink `current` points to specified release directory
- Deployment script runs with `--skip-composer --skip-tests` flags
- Smoke tests verify basic functionality
- Previous configuration (.env) is preserved
- No new files are uploaded (instant rollback)

### Emergency Rollback (SSH)

Use only when GitHub Actions is unavailable.

```bash
# 1. SSH to server
ssh deploy@3dprint-omsk.ru

# 2. Navigate to deployment directory
cd /var/www/3dprint-omsk.ru

# 3. List available releases
ls -lt releases/

# 4. Check current release
readlink current
# Output: releases/release_20240120_143022

# 5. Verify previous release exists
ls -la releases/release_20240120_120530

# 6. Atomic rollback (switch symlink)
ln -sfn releases/release_20240120_120530 current_temp
mv -Tf current_temp current

# 7. Verify switch
readlink current
# Output: releases/release_20240120_120530

# 8. Test application
curl -I https://3dprint-omsk.ru
curl https://3dprint-omsk.ru/api/test.php

# 9. Check logs
tail -f logs/api.log
```

### Rollback Best Practices

1. **Always test first:**
   - Use staging environment if available
   - Run smoke tests before declaring rollback successful

2. **Document the issue:**
   - Note symptoms and error messages
   - Create GitHub issue describing the problem
   - Link issue to rollback deployment

3. **Investigate root cause:**
   - Review deployment logs artifact
   - Check application error logs
   - Identify what went wrong

4. **Plan fix:**
   - Create hotfix branch
   - Fix the issue
   - Test thoroughly
   - Deploy normally (not emergency deployment)

---

## Manual Deployment

### Prerequisites

Ensure server is prepared:

```bash
# 1. Verify hosting environment
ssh deploy@3dprint-omsk.ru
cd /var/www/3dprint-omsk.ru
php scripts/hosting-audit.php --strict

# 2. Check current status
readlink current
ls -lt releases/

# 3. Verify disk space
df -h

# 4. Check database connection
php -r "require 'vendor/autoload.php'; \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); \$dotenv->load(); echo 'DB: ' . getenv('DB_DATABASE') . PHP_EOL;"
```

### Manual SSH Deployment

If GitHub Actions is unavailable:

```bash
# 1. Create release directory
RELEASE_DIR="release_$(date +%Y%m%d_%H%M%S)"
mkdir -p /var/www/3dprint-omsk.ru/releases/${RELEASE_DIR}

# 2. Sync files from local machine
rsync -avz --progress \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='storage/cache/*' \
  --exclude='storage/logs/*' \
  --exclude='tests' \
  ./ deploy@3dprint-omsk.ru:/var/www/3dprint-omsk.ru/releases/${RELEASE_DIR}/

# 3. SSH to server
ssh deploy@3dprint-omsk.ru

# 4. Setup environment
cd /var/www/3dprint-omsk.ru/releases/${RELEASE_DIR}
cp ../../current/.env .env  # Copy production .env
composer install --no-dev --optimize-autoloader

# 5. Setup shared directories
ln -sf /var/www/3dprint-omsk.ru/shared/uploads storage/uploads
ln -sf /var/www/3dprint-omsk.ru/shared/backups storage/backups

# 6. Run deployment script
bash scripts/deploy.sh --ci

# 7. Activate release
cd /var/www/3dprint-omsk.ru
ln -sfn releases/${RELEASE_DIR} current

# 8. Test
curl -I https://3dprint-omsk.ru
php current/scripts/api_smoke.php
```

---

## Monitoring & Logging

### Deployment Logs

#### GitHub Actions Artifacts

**Test Results:**
- **Location:** Workflow run → Artifacts → `test-results-{sha}`
- **Contents:**
  - PHPUnit output
  - Test logs from `storage/logs/`
- **Retention:** 7 days

**Deployment Logs:**
- **Location:** Workflow run → Artifacts → `deployment-logs-{sha}`
- **Contents:**
  - Deployment script output
  - Server-side logs
  - Smoke test results
- **Retention:** 30 days

**Accessing Artifacts:**

```bash
# Via GitHub CLI
gh run list --workflow=deploy.yml --limit 5
gh run view <run-id> --log
gh run download <run-id>  # Downloads all artifacts

# Via Web UI
# GitHub repo → Actions → Select workflow run → Scroll to Artifacts section
```

#### Server-Side Logs

**Deployment Logs:**
```bash
ssh deploy@3dprint-omsk.ru
cd /var/www/3dprint-omsk.ru/current
ls -lt storage/logs/deploy_*.log

# View latest deployment log
tail -f storage/logs/deploy_$(ls -t storage/logs/deploy_*.log | head -1 | cut -d'/' -f3)
```

**Application Logs:**
```bash
# API errors
tail -f logs/api.log

# Admin actions
tail -f logs/admin.log

# Web server logs
tail -f /var/log/nginx/3dprint-omsk.ru.access.log
tail -f /var/log/nginx/3dprint-omsk.ru.error.log
```

### Deployment Report

Each deployment generates a comprehensive report:

```bash
cat storage/logs/deploy_report_YYYYMMDD_HHMMSS.txt
```

**Report includes:**
- Timestamp and server info
- Deployment steps completed
- PHP version
- Next steps checklist
- Important file locations
- Documentation links

### Integration with Monitoring Systems

#### GitHub Commit Status

Deployment status is reflected in commit checks:
- ✅ Green check: Tests passed, deployment successful
- ❌ Red X: Tests failed or deployment failed
- 🟡 Yellow dot: Deployment pending approval

#### Slack Notifications (Optional)

Add Slack webhook to receive deployment notifications:

```yaml
# Add to .github/workflows/deploy.yml
- name: Notify Slack
  if: always()
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    text: 'Deployment to production: ${{ job.status }}'
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

#### Sentry Integration (Optional)

Track deployment events in Sentry:

```bash
# Add to deployment script
curl https://sentry.io/api/0/organizations/YOUR_ORG/releases/ \
  -X POST \
  -H "Authorization: Bearer $SENTRY_AUTH_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "version": "'$RELEASE_DIR'",
    "projects": ["3dprint-pro"],
    "dateReleased": "'$(date -u +"%Y-%m-%dT%H:%M:%S.%3NZ")'"
  }'
```

#### Custom Webhooks

Trigger custom webhooks on deployment events:

```bash
# Post-deployment webhook
curl -X POST https://your-monitoring.com/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "deployment",
    "status": "success",
    "release": "'$RELEASE_DIR'",
    "timestamp": "'$(date -u +"%Y-%m-%dT%H:%M:%S")'"
  }'
```

---

## Troubleshooting

### Common Issues

#### 1. Tests Fail on PHP Version Mismatch

**Symptom:**
```
PHP Fatal error: Call to undefined function...
```

**Solution:**
- Ensure PHP 7.4+ compatibility in code
- Check `composer.json` platform setting
- Review deprecated function usage

#### 2. Deployment Fails: SSH Connection Timeout

**Symptom:**
```
ssh: connect to host 3dprint-omsk.ru port 22: Connection timed out
```

**Solutions:**
- Verify `DEPLOY_HOST` secret is correct
- Check server firewall allows GitHub IPs
- Ensure SSH service is running
- Verify SSH key is properly configured

**GitHub IPs to whitelist:**
- See: https://api.github.com/meta (look for `actions` IPs)
- Or allow all GitHub IP ranges

#### 3. Permission Denied Errors

**Symptom:**
```
rsync: mkdir "/var/www/3dprint-omsk.ru/releases/release_..." failed: Permission denied
```

**Solutions:**
```bash
# Check directory ownership
ssh deploy@3dprint-omsk.ru
ls -la /var/www/3dprint-omsk.ru/

# Fix ownership
sudo chown -R deploy:deploy /var/www/3dprint-omsk.ru/

# Fix permissions
chmod 755 /var/www/3dprint-omsk.ru/
chmod 755 /var/www/3dprint-omsk.ru/releases/
```

#### 4. Smoke Tests Fail After Deployment

**Symptom:**
```
✗ Smoke tests failed
❌ GET /api/test.php returns 200
```

**Investigation:**
```bash
# Check application logs
ssh deploy@3dprint-omsk.ru
cd /var/www/3dprint-omsk.ru/current
tail -f logs/api.log

# Test endpoints manually
curl -v https://3dprint-omsk.ru/api/test.php

# Check database connectivity
php -r "require 'vendor/autoload.php'; require 'bootstrap/eloquent.php';"

# Verify .env configuration
cat .env | grep -v PASSWORD
```

**Common causes:**
- Database credentials incorrect
- Missing .env file
- PHP extensions not loaded
- File permissions incorrect

#### 5. Rollback Not Working

**Symptom:**
```
ln: failed to create symbolic link 'current': File exists
```

**Solution:**
```bash
# Force recreate symlink
cd /var/www/3dprint-omsk.ru
rm -f current
ln -sf releases/release_YYYYMMDD_HHMMSS current

# Or use atomic switch
ln -sfn releases/release_YYYYMMDD_HHMMSS current_temp
mv -Tf current_temp current
```

### Debug Mode

Enable verbose logging in workflow:

```yaml
# Add to job step
- name: Debug deployment
  run: |
    set -x  # Enable command tracing
    # your deployment commands
```

View deployment logs with full details:

```bash
# SSH to server
ssh deploy@3dprint-omsk.ru

# Run deploy script manually with verbose output
cd /var/www/3dprint-omsk.ru/current
bash -x scripts/deploy.sh --ci 2>&1 | tee debug.log
```

### Getting Help

1. **Check documentation:**
   - [Production Runbook](./PRODUCTION_RUNBOOK.md)
   - [Deployment Guide](./DEPLOYMENT.md)
   - [Troubleshooting Guide](./TROUBLESHOOTING.md)

2. **Review logs:**
   - GitHub Actions workflow logs
   - Deployment artifacts
   - Server application logs
   - Web server error logs

3. **Test locally:**
   ```bash
   # Run tests locally
   composer test
   
   # Test deployment script (dry run)
   bash scripts/deploy.sh --dry-run
   
   # Run smoke tests
   php scripts/api_smoke.php
   ```

---

## Best Practices

### 1. Always Test Before Merging

```bash
# Run full test suite
composer test

# Run smoke tests
php scripts/api_smoke.php

# Test in staging environment
git push origin feature-branch
# Deploy to staging, verify
```

### 2. Use Feature Branches

```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes, commit
git add .
git commit -m "feat: add new feature"

# Push and create PR
git push origin feature/my-feature
```

### 3. Enable Production Environment Protection

**In GitHub Settings:**
1. Settings → Environments → production
2. Enable "Required reviewers" (add team members)
3. Enable "Wait timer" (optional delay before deployment)
4. Enable "Deployment branches" → Only `main` branch

### 4. Monitor Deployments

- Set up Slack/email notifications
- Review deployment summaries in GitHub Actions
- Check server logs after each deployment
- Run manual smoke tests on critical features

### 5. Maintain Release History

```bash
# Keep at least 5 previous releases
cd /var/www/3dprint-omsk.ru/releases
ls -lt | head -6

# Document deployments
# Tag releases in Git
git tag -a v1.2.3 -m "Production deployment 2024-01-20"
git push origin v1.2.3
```

### 6. Regular Backups

Automate database backups before deployments:

```bash
# In deployment script, add pre-deployment backup
cd /var/www/3dprint-omsk.ru/current
php database/backup.php --retention=30 --verify
```

### 7. Use Deployment Windows

Schedule deployments during low-traffic periods:
- Avoid peak business hours
- Notify team before deployment
- Have rollback plan ready

### 8. Security Best Practices

- Rotate SSH keys regularly
- Use least-privilege SSH user
- Never commit secrets to repository
- Regularly update dependencies
- Monitor security advisories

---

## Quick Reference

### Common Commands

```bash
# Trigger deployment
gh workflow run deploy.yml --ref main

# View workflow status
gh run list --workflow=deploy.yml --limit 5

# Download deployment logs
gh run download <run-id>

# SSH to production
ssh deploy@3dprint-omsk.ru

# View current release
readlink /var/www/3dprint-omsk.ru/current

# List releases
ls -lt /var/www/3dprint-omsk.ru/releases/

# Rollback (emergency)
cd /var/www/3dprint-omsk.ru
ln -sfn releases/release_YYYYMMDD_HHMMSS current

# Test deployment
curl -I https://3dprint-omsk.ru
php current/scripts/api_smoke.php
```

### Important Files

| File | Purpose | Location |
|------|---------|----------|
| Workflow | CI/CD pipeline definition | `.github/workflows/deploy.yml` |
| Deploy script | Server-side deployment automation | `scripts/deploy.sh` |
| Smoke tests | Post-deployment verification | `scripts/api_smoke.php` |
| Hosting audit | Pre-deployment checks | `scripts/hosting-audit.php` |
| Deploy logs | Deployment execution logs | `storage/logs/deploy_*.log` |
| Deploy reports | Human-readable summaries | `storage/logs/deploy_report_*.txt` |

### Links

- **GitHub Actions Documentation:** https://docs.github.com/en/actions
- **GitHub CLI:** https://cli.github.com/
- **Production Runbook:** [PRODUCTION_RUNBOOK.md](./PRODUCTION_RUNBOOK.md)
- **Deployment Guide:** [DEPLOYMENT.md](./DEPLOYMENT.md)
- **Security Guide:** [SECURITY.md](./SECURITY.md)

---

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2024-01-20 | Initial CI/CD pipeline implementation |

---

**Related Documentation:**
- [Production Runbook](./PRODUCTION_RUNBOOK.md) - Complete production deployment and operations guide
- [Deployment Guide](./DEPLOYMENT.md) - Manual deployment procedures
- [Hosting Audit](./HOSTING_AUDIT.md) - Server requirements and validation
- [Security Guide](./SECURITY.md) - Security best practices and hardening
- [Database Operations](./DATABASE_OPERATIONS.md) - Backup and restore procedures
