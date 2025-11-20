# GitHub Actions Workflows

This directory contains CI/CD workflows for the 3D Print Pro platform.

## Workflows

### deploy.yml

**Purpose:** Automated testing and deployment to production with rollback support.

**Triggers:**
- Push to `main` branch (automatic)
- Manual trigger via `workflow_dispatch` (for rollbacks and manual deploys)

**Features:**
- ✅ Automated PHPUnit testing on PHP 8.1
- 🔒 Production environment gate requiring manual approval
- 📦 Timestamped release directories for easy rollback
- 🔄 Atomic deployment using symlinks
- 🧪 Post-deployment smoke tests
- 📊 Deployment logs uploaded as artifacts
- 🔙 One-click rollback via workflow inputs
- 🧹 Automatic cleanup of old releases (keeps last 5)

**Usage:**

See [docs/CI_CD.md](../../docs/CI_CD.md) for complete documentation.

**Quick Start:**

```bash
# Automatic deployment (push to main)
git push origin main

# Manual deployment via GitHub CLI
gh workflow run deploy.yml --ref main

# Rollback to previous release
gh workflow run deploy.yml --ref main \
  -f rollback_release=release_20240120_120530
```

## Required Secrets

Configure in: Repository Settings → Secrets and variables → Actions

| Secret | Description | Example |
|--------|-------------|---------|
| `DEPLOY_HOST` | Production server hostname | `3dprint-omsk.ru` |
| `DEPLOY_USER` | SSH username | `deploy` |
| `DEPLOY_PATH` | Deployment directory | `/var/www/3dprint-omsk.ru` |
| `SSH_KEY` | Private SSH key | `-----BEGIN RSA PRIVATE KEY-----...` |
| `ENVIRONMENT` | Environment name | `production` |

## Environment Configuration

**Production Environment:**
- Name: `production`
- URL: `https://3dprint-omsk.ru`
- Protection rules:
  - ✅ Required reviewers
  - ⏱️ Wait timer (optional)
  - 🌿 Deployment branches: `main` only

**Setup:**

1. Go to Repository Settings → Environments
2. Click "New environment"
3. Name: `production`
4. Add protection rules:
   - Check "Required reviewers"
   - Add team members
   - (Optional) Set wait timer
   - (Optional) Restrict deployment branches to `main`
5. Save

## Workflow Inputs

### Manual Trigger (workflow_dispatch)

| Input | Type | Description | Default |
|-------|------|-------------|---------|
| `rollback_release` | string | Release directory to rollback to (e.g., `release_20240120_143022`) | _(empty)_ |
| `skip_tests` | boolean | Skip test execution (not recommended) | `false` |
| `force_deploy` | boolean | Force deployment even if checks fail (dangerous) | `false` |

### Examples

**Normal deployment:**
```yaml
rollback_release: ""
skip_tests: false
force_deploy: false
```

**Rollback:**
```yaml
rollback_release: "release_20240120_120530"
skip_tests: false
force_deploy: false
```

**Emergency deployment (skip tests):**
```yaml
rollback_release: ""
skip_tests: true
force_deploy: true
```

## Pipeline Flow

### Automatic Deployment (Push to main)

```mermaid
graph TD
    A[Push to main] --> B[Checkout Code]
    B --> C[Setup PHP 8.1]
    C --> D[Install Dependencies]
    D --> E[Run PHPUnit Tests]
    E --> F{Tests Pass?}
    F -->|No| G[Fail Build]
    F -->|Yes| H[Wait for Approval]
    H --> I[Create Deployment Package]
    I --> J[Deploy to Server]
    J --> K[Run Smoke Tests]
    K --> L{Tests Pass?}
    L -->|No| M[Alert Team]
    L -->|Yes| N[Cleanup Old Releases]
    N --> O[Upload Logs]
    O --> P[Deployment Success]
```

### Rollback Flow

```mermaid
graph TD
    A[Trigger Rollback] --> B[Verify Release Exists]
    B --> C{Release Found?}
    C -->|No| D[Fail with Error]
    C -->|Yes| E[Switch Symlink]
    E --> F[Run Smoke Tests]
    F --> G{Tests Pass?}
    G -->|No| H[Alert Team]
    G -->|Yes| I[Rollback Success]
```

## Artifacts

### Test Results

- **Name:** `test-results-{sha}`
- **Contents:**
  - PHPUnit test output
  - Test logs
- **Retention:** 7 days
- **Size:** ~1-5 MB

### Deployment Logs

- **Name:** `deployment-logs-{sha}`
- **Contents:**
  - Deployment script output
  - Server-side logs
  - Smoke test results
- **Retention:** 30 days
- **Size:** ~500 KB - 2 MB

### Accessing Artifacts

**Via GitHub UI:**
1. Go to Actions tab
2. Click on workflow run
3. Scroll to "Artifacts" section
4. Click to download

**Via GitHub CLI:**
```bash
gh run list --workflow=deploy.yml --limit 5
gh run download <run-id>
```

## Directory Structure on Server

```
/var/www/3dprint-omsk.ru/
├── current -> releases/release_20240120_143022  # Symlink to current release
├── releases/
│   ├── release_20240120_143022/  # Current release
│   ├── release_20240120_120530/  # Previous release
│   ├── release_20240119_184512/  # Older release
│   └── ...
├── shared/
│   ├── uploads/       # Shared uploads directory
│   ├── backups/       # Shared backups directory
│   └── .env           # Shared environment file (optional)
└── previous_release.txt  # Tracks previous release for easy rollback
```

## Monitoring & Alerts

### GitHub Checks

Deployment status visible in:
- Commit page (green check or red X)
- Pull request checks
- Actions tab

### Notifications

Configure notifications in repository settings:
- Settings → Notifications
- Enable "Actions" notifications
- Choose email/GitHub/mobile preferences

### Custom Integrations

Add webhook notifications to the workflow:

```yaml
- name: Notify deployment
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

## Troubleshooting

### Tests Fail

```bash
# Run tests locally
composer test

# View test logs
cat storage/logs/test_*.log

# Check specific test
vendor/bin/phpunit tests/Unit/SpecificTest.php --verbose
```

### Deployment Fails

```bash
# Check deployment logs artifact
gh run download <run-id>
cat deployment-logs/deploy.log

# SSH to server and check logs
ssh deploy@3dprint-omsk.ru
tail -f /var/www/3dprint-omsk.ru/current/storage/logs/deploy_*.log
```

### Rollback Needed

See [Rollback Procedures](../../docs/CI_CD.md#rollback-procedures) in CI/CD documentation.

## Best Practices

1. ✅ **Always run tests locally before pushing**
2. ✅ **Use feature branches and pull requests**
3. ✅ **Require code review before merging to main**
4. ✅ **Monitor deployments in Actions tab**
5. ✅ **Keep production environment protection enabled**
6. ✅ **Review deployment logs after each deployment**
7. ✅ **Test critical features manually after deployment**
8. ✅ **Document any manual interventions**

## Security

- 🔒 SSH keys stored as encrypted secrets
- 🔒 Environment variables never logged
- 🔒 Production requires manual approval
- 🔒 Deployment artifacts retention limited
- 🔒 Only `main` branch can deploy to production
- 🔒 Secrets masked in logs

## Links

- **Complete CI/CD Documentation:** [docs/CI_CD.md](../../docs/CI_CD.md)
- **Production Runbook:** [docs/PRODUCTION_RUNBOOK.md](../../docs/PRODUCTION_RUNBOOK.md)
- **Deployment Guide:** [docs/DEPLOYMENT.md](../../docs/DEPLOYMENT.md)
- **GitHub Actions Docs:** https://docs.github.com/en/actions

---

**Questions?** See [docs/CI_CD.md](../../docs/CI_CD.md) for detailed documentation and troubleshooting.
