# API Smoke Test Suite v2.0

Comprehensive end-to-end testing of all API endpoints with admin authentication and CRUD validation.

## Quick Start

```bash
# Test public endpoints only (no authentication)
php scripts/api_smoke.php --url=https://3dprint-omsk.ru --readonly

# Full CRUD test with admin authentication (development)
php scripts/api_smoke.php \
  --url=http://localhost:8000 \
  --admin-email=admin@example.com \
  --admin-password=SecurePass123

# Production read-only check (authenticated)
php scripts/api_smoke.php \
  --url=https://3dprint-omsk.ru \
  --admin-email=admin@example.com \
  --admin-password=SecurePass123 \
  --readonly
```

## Options

- `--url=<base_url>` - Base URL of the application (required)
- `--admin-email=<email>` - Admin email for authentication
- `--admin-password=<pass>` - Admin password for authentication
- `--readonly` - Run in read-only mode (safe for production, GET requests only)
- `--verbose` - Show detailed output (default)
- `--quiet` - Show minimal output
- `--help` - Show help message

## Test Modes

### Read-Only Mode (`--readonly`)

- Only performs GET requests
- Safe to run on production
- Tests public endpoints without auth
- Tests admin endpoints with auth (if credentials provided)
- No data modification
- Validates response structures

### Full CRUD Mode (default)

- Creates temporary test fixtures
- Tests POST, PUT, PATCH, DELETE operations
- Automatically cleans up all created resources
- Should only be run on development/staging environments
- Validates complete CRUD workflows

## Coverage

### Public Endpoints (no authentication)

- Health endpoint (`/api/test.php`)
- Content endpoints (services, portfolio, testimonials, FAQ, content blocks)
- Public settings endpoint (`/api/settings.php`)
- Public calculator settings endpoint (`/api/calculator-settings.php`)

### Authenticated Endpoints (requires admin credentials)

- Admin authentication flow (login with CSRF token)
- Content CRUD (services, portfolio, testimonials, FAQ, content blocks)
- Forms API (`/api/forms.php`) - create, read, update, delete forms
- Form Fields API (`/api/form-fields.php`) - manage form fields
- Form Submissions API (`/api/form-submissions.php`) - view submissions
- Calculator Settings Admin (`/api/calculator-settings.php`) - admin operations
- Settings Admin (`/api/settings.php`) - full settings access, audit history
- Admin Users API (`/api/admin/users.php`) - user management
- Audit Logs API (`/api/admin/audit-logs.php`) - view logs and stats
- Orders API (`/api/orders.php`) - complete order lifecycle

## Validation

- HTTP status codes (200, 201, 204, 404, etc.)
- Response structure (success/data/meta keys)
- Data integrity and persistence
- Authentication and authorization

## Cleanup

- Automatically deletes all created test resources
- Respects foreign key constraints
- Verifies successful cleanup
- Reports any cleanup failures

## Exit Codes

- `0` - All tests passed
- `1` - One or more tests failed

## Example Output

```
🧪 API Smoke Test Suite v2.0
Base URL: https://3dprint-omsk.ru
Mode: READ-ONLY (safe for production)
Auth: Enabled
================================================================================

📦 Testing: Admin Authentication
--------------------------------------------------------------------------------
  ✅ Admin login returns redirect
  ✅ Admin authentication successful

📦 Testing: Health/Test Endpoint
--------------------------------------------------------------------------------
  ✅ GET /api/test.php returns 200
  ✅ Response has correct structure
  ✅ Response has database_status

[... more test groups ...]

================================================================================
📊 Test Summary
================================================================================
Total Tests:  127
✅ Passed:    127
❌ Failed:    0
Success Rate: 100.0%

✅ ALL SMOKE TESTS PASSED
```

## Pre-Deployment Checklist

Before declaring the site synced with the database:

1. **Run full CRUD test on staging:**
   ```bash
   php scripts/api_smoke.php \
     --url=https://staging.3dprint-omsk.ru \
     --admin-email=admin@example.com \
     --admin-password=SecurePass123
   ```

2. **Run read-only check on production:**
   ```bash
   php scripts/api_smoke.php \
     --url=https://3dprint-omsk.ru \
     --admin-email=admin@example.com \
     --admin-password=SecurePass123 \
     --readonly
   ```

3. Verify all tests pass (100% success rate)

4. Review any warnings or context messages

5. Check that cleanup completed successfully

## CI/CD Integration

### GitHub Actions Example

```yaml
- name: Run API Smoke Tests
  run: |
    php scripts/api_smoke.php \
      --url=${{ secrets.STAGING_URL }} \
      --admin-email=${{ secrets.ADMIN_EMAIL }} \
      --admin-password=${{ secrets.ADMIN_PASSWORD }}
```

### Production Monitoring

```bash
# Add to cron for periodic health checks
0 */6 * * * php /path/to/scripts/api_smoke.php \
  --url=https://3dprint-omsk.ru \
  --admin-email=monitor@example.com \
  --admin-password=SecurePass123 \
  --readonly \
  --quiet >> /var/log/api_health.log 2>&1
```

## Troubleshooting

### Authentication Fails

**Symptom:** Admin login returns redirect fails

**Solutions:**
- Verify admin credentials are correct
- Check that admin user has `active` status
- Verify CSRF token is being extracted correctly
- Check rate limiting hasn't locked the account

### Tests Fail on Specific Endpoint

**Symptom:** One or more endpoint tests fail

**Solutions:**
- Check endpoint exists and is accessible
- Verify database has required tables
- Check API response format matches expected structure
- Review error context in test output

### Cleanup Fails

**Symptom:** Cleanup reports failures

**Solutions:**
- Check foreign key constraints
- Verify admin user has delete permissions
- Review which resources failed to delete in output
- Manually clean up remaining test data if needed

### Read-Only Mode Still Modifies Data

**Symptom:** Data changes in read-only mode

**Solutions:**
- Ensure `--readonly` flag is used
- Check that only GET requests are being made
- Review test logs for unexpected POST/PUT/DELETE

## Best Practices

1. **Always use `--readonly` on production**
2. **Run full CRUD on staging before deployment**
3. **Schedule periodic read-only checks via cron**
4. **Review failed tests before proceeding with deployment**
5. **Keep admin credentials secure (use environment variables)**
6. **Run tests after major changes to API endpoints**
7. **Monitor test execution time (should be under 30 seconds)**

## Related Documentation

- [Testing Guide](../docs/TESTING.md) - Complete testing documentation
- [API Reference](../docs/API_REFERENCE.md) - Full API documentation
- [Deployment Guide](../docs/DEPLOYMENT.md) - Deployment procedures
- [Production Runbook](../docs/PRODUCTION_RUNBOOK.md) - Operations guide
