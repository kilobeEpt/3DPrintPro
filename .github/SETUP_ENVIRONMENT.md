# GitHub Environment Setup Guide

This guide helps you configure the **production environment** in GitHub for protected deployments.

## Overview

The deployment workflow uses a GitHub Environment called `production` to:
- Require manual approval before deployments
- Store environment-specific settings
- Track deployment history
- Provide rollback capabilities

## Setup Steps

### 1. Create Production Environment

1. Go to your GitHub repository
2. Click **Settings** → **Environments**
3. Click **New environment**
4. Name: `production`
5. Click **Configure environment**

### 2. Configure Protection Rules

#### Required Reviewers

1. Under "Deployment protection rules"
2. Check ✅ **Required reviewers**
3. Click **Add reviewer**
4. Select team members who should approve deployments
5. Recommended: Add at least 2 reviewers

**Best practice:** Don't approve your own deployments in production.

#### Wait Timer (Optional)

1. Check ✅ **Wait timer**
2. Set delay: `5` minutes
3. This gives you time to cancel accidental deployments

#### Deployment Branches

1. Under "Deployment branches"
2. Select **Selected branches**
3. Add rule: `main`
4. Click **Add rule**

This prevents deployments from feature branches.

### 3. Add Environment URL

1. Under "Environment URL"
2. Enter: `https://3dprint-omsk.ru`
3. This will be displayed in deployment summaries

### 4. Save Configuration

Click **Save protection rules**

## GitHub Secrets Setup

Configure these secrets in: **Settings** → **Secrets and variables** → **Actions**

### Required Secrets

| Secret Name | Description | How to Get |
|-------------|-------------|------------|
| `DEPLOY_HOST` | Production server hostname | Your server domain or IP (e.g., `3dprint-omsk.ru`) |
| `DEPLOY_USER` | SSH username | Server SSH user (e.g., `deploy` or `www-data`) |
| `DEPLOY_PATH` | Deployment directory | Absolute path (e.g., `/var/www/3dprint-omsk.ru`) |
| `SSH_KEY` | Private SSH key | Generate with `ssh-keygen` (see below) |
| `ENVIRONMENT` | Environment name | `production` |

### Generating SSH Key

Run on your local machine:

```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy" -f ~/.ssh/deploy_key

# Copy public key to server
ssh-copy-id -i ~/.ssh/deploy_key.pub deploy@3dprint-omsk.ru

# Verify key works
ssh -i ~/.ssh/deploy_key deploy@3dprint-omsk.ru "echo 'Connection successful'"

# Display private key (copy this to GitHub secret)
cat ~/.ssh/deploy_key
```

**Security:**
- ✅ Use a dedicated deploy key (not your personal SSH key)
- ✅ Restrict key permissions on server (`chmod 600 ~/.ssh/authorized_keys`)
- ✅ Delete local copies after adding to GitHub
- ✅ Consider using GitHub's Deploy Keys feature for read-only access

### Adding Secrets to GitHub

1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Enter name (e.g., `SSH_KEY`)
4. Paste value
5. Click **Add secret**
6. Repeat for all secrets

## Server Setup

### 1. Create Deployment Directory Structure

SSH to your server and run:

```bash
# Create base directory
sudo mkdir -p /var/www/3dprint-omsk.ru
sudo chown deploy:deploy /var/www/3dprint-omsk.ru
cd /var/www/3dprint-omsk.ru

# Create shared directories
mkdir -p shared/uploads/{portfolio,testimonials}
mkdir -p shared/backups
mkdir -p releases

# Set permissions
chmod 755 shared shared/uploads shared/backups releases
```

### 2. Add Deploy User to Web Server Group

```bash
# For Nginx
sudo usermod -aG www-data deploy

# For Apache
sudo usermod -aG apache deploy

# Verify group membership
groups deploy
```

### 3. Configure SSH Access

```bash
# Ensure .ssh directory exists
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Verify authorized_keys has GitHub Actions key
cat ~/.ssh/authorized_keys

# Set correct permissions
chmod 600 ~/.ssh/authorized_keys
```

### 4. Test Deployment Structure

Use the post-deploy script to setup shared directories:

```bash
# Clone or upload your project code temporarily
cd /tmp
git clone your-repo
cd your-repo

# Setup shared directories
bash scripts/post-deploy.sh --setup-shared --deploy-path /var/www/3dprint-omsk.ru

# Verify structure
ls -la /var/www/3dprint-omsk.ru
```

## Testing the Workflow

### 1. Test SSH Connection

From your local machine:

```bash
# Install GitHub CLI
brew install gh  # macOS
# or visit https://cli.github.com/

# Authenticate
gh auth login

# Test workflow syntax
gh workflow view deploy.yml
```

### 2. Trigger Test Deployment

```bash
# Option 1: Push to main
git checkout main
git commit --allow-empty -m "test: trigger deployment"
git push origin main

# Option 2: Manual trigger
gh workflow run deploy.yml --ref main
```

### 3. Monitor Deployment

```bash
# List workflow runs
gh run list --workflow=deploy.yml --limit 5

# Watch live logs
gh run watch

# View specific run
gh run view <run-id> --log
```

### 4. Approve Deployment

1. Go to **Actions** tab in GitHub
2. Click on the running workflow
3. Click **Review deployments**
4. Select **production**
5. Add comment (optional)
6. Click **Approve and deploy**

## Rollback Testing

Test the rollback mechanism:

```bash
# List available releases on server
ssh deploy@3dprint-omsk.ru "ls -lt /var/www/3dprint-omsk.ru/releases/"

# Trigger rollback via GitHub Actions
gh workflow run deploy.yml --ref main \
  -f rollback_release=release_20240120_120530

# Or via GitHub UI:
# Actions → Deploy to Production → Run workflow
# Enter rollback_release: release_20240120_120530
```

## Monitoring & Alerts

### View Deployment History

1. Go to repository
2. Click **Environments** (right sidebar)
3. Click **production**
4. View deployment history with approval trail

### Download Deployment Logs

```bash
# List recent runs
gh run list --workflow=deploy.yml --limit 10

# Download artifacts
gh run download <run-id>

# View deployment logs
cat deployment-logs/deploy.log
```

### GitHub Notifications

Configure in: **Settings** → **Notifications**
- Enable **Actions** notifications
- Choose email, web, or mobile
- Set notification frequency

## Troubleshooting

### "Environment not found" Error

**Solution:**
1. Ensure environment is created in Settings → Environments
2. Name must exactly match: `production` (lowercase)
3. Workflow file must reference correct environment name

### "No permission to deploy" Error

**Solution:**
1. Ensure you're added as required reviewer
2. Wait for deployment to request approval
3. Click "Review deployments" button
4. Select environment and approve

### SSH Connection Fails

**Solution:**
```bash
# Test SSH key locally
ssh -i ~/.ssh/deploy_key deploy@3dprint-omsk.ru

# Check key is added to server
ssh deploy@3dprint-omsk.ru "cat ~/.ssh/authorized_keys"

# Verify key format in GitHub secret
# Must include header/footer:
# -----BEGIN RSA PRIVATE KEY-----
# ...
# -----END RSA PRIVATE KEY-----
```

### Deployment Fails at rsync Step

**Solution:**
```bash
# Verify deployment directory exists
ssh deploy@3dprint-omsk.ru "ls -la /var/www/3dprint-omsk.ru"

# Check permissions
ssh deploy@3dprint-omsk.ru "touch /var/www/3dprint-omsk.ru/releases/test.txt"

# Create structure if missing
ssh deploy@3dprint-omsk.ru "mkdir -p /var/www/3dprint-omsk.ru/releases"
```

## Security Best Practices

1. ✅ **Never commit secrets** to repository
2. ✅ **Use dedicated deploy keys** with minimal permissions
3. ✅ **Enable 2FA** on GitHub accounts with deployment access
4. ✅ **Require code review** before merging to main
5. ✅ **Audit deployment logs** regularly
6. ✅ **Rotate SSH keys** every 6-12 months
7. ✅ **Monitor failed login attempts** on server
8. ✅ **Keep GitHub Actions secrets** up to date

## Next Steps

Once environment is configured:

1. ✅ Test deployment to production
2. ✅ Verify rollback works
3. ✅ Document deployment process for team
4. ✅ Setup monitoring and alerts
5. ✅ Schedule regular deployment drills

## Documentation

- **Complete CI/CD Guide:** [docs/CI_CD.md](../docs/CI_CD.md)
- **Deployment Guide:** [docs/DEPLOYMENT.md](../docs/DEPLOYMENT.md)
- **Production Runbook:** [docs/PRODUCTION_RUNBOOK.md](../docs/PRODUCTION_RUNBOOK.md)
- **GitHub Actions Docs:** https://docs.github.com/en/actions/deployment/targeting-different-environments/using-environments-for-deployment

---

**Questions?** See [docs/CI_CD.md](../docs/CI_CD.md) or open an issue in the repository.
