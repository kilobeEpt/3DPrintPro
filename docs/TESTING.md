# Testing Guide

Complete guide to running and maintaining the test suite for 3D Print Pro platform.

## Table of Contents

1. [Overview](#overview)
2. [Setup](#setup)
3. [Running Tests](#running-tests)
4. [Test Structure](#test-structure)
5. [Writing Tests](#writing-tests)
6. [Smoke Tests](#smoke-tests)
7. [Continuous Integration](#continuous-integration)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The platform includes a comprehensive test suite using **PHPUnit 9.5** for unit and integration testing, plus smoke scripts for end-to-end validation.

### Test Coverage

**Unit Tests:**
- ✅ Settings Service (typed casting, caching, validation, audit logging)
- ✅ Form Validation (field types, rules, relationships, scopes)

**Integration Tests:**
- ✅ Form Submission (complete submission flow, order linking, status updates)

**Smoke Tests:**
- ✅ Form API (seeds, fetches, submits, validates persistence)
- ✅ Settings Service (cache operations, type casting, validation)
- ✅ Eloquent ORM (database operations, model relationships)

---

## Setup

### Prerequisites

- PHP 7.4+ with PDO, PDO_SQLite extensions
- Composer 2.0+
- SQLite3 (for test database)

### Installation

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Verify PHPUnit installation:**
   ```bash
   vendor/bin/phpunit --version
   ```
   
   Expected output: `PHPUnit 9.5.x`

3. **Test database configuration:**
   
   Tests use an in-memory SQLite database configured in `phpunit.xml`:
   ```xml
   <php>
       <env name="DB_CONNECTION" value="sqlite"/>
       <env name="DB_DATABASE" value=":memory:"/>
   </php>
   ```

---

## Running Tests

### All Tests

Run the complete test suite:

```bash
composer test
```

Or directly via PHPUnit:

```bash
vendor/bin/phpunit
```

### Specific Test Suites

**Unit tests only:**
```bash
vendor/bin/phpunit --testsuite Unit
```

**Integration tests only:**
```bash
vendor/bin/phpunit --testsuite Integration
```

### Specific Test Files

```bash
vendor/bin/phpunit tests/Unit/SettingsServiceTest.php
vendor/bin/phpunit tests/Unit/FormValidationTest.php
vendor/bin/phpunit tests/Integration/FormSubmissionTest.php
```

### Individual Test Methods

```bash
vendor/bin/phpunit --filter testSetAndGetSingleSetting
vendor/bin/phpunit --filter testCompleteFormSubmissionFlow
```

### Test Coverage

Generate HTML coverage report (requires Xdebug):

```bash
composer test-coverage
```

Report will be generated in `coverage/` directory. Open `coverage/index.html` in a browser.

### Verbose Output

```bash
vendor/bin/phpunit --verbose
vendor/bin/phpunit --testdox
```

---

## Test Structure

```
tests/
├── bootstrap.php              # PHPUnit bootstrap (Eloquent initialization, schema)
├── Unit/                      # Unit tests (isolated component testing)
│   ├── SettingsServiceTest.php
│   └── FormValidationTest.php
└── Integration/               # Integration tests (multiple components)
    └── FormSubmissionTest.php
```

### Bootstrap File

`tests/bootstrap.php` initializes the test environment:

1. Loads Composer autoloader
2. Initializes Eloquent ORM with SQLite in-memory database
3. Creates test database schema (all tables)
4. Provides helper functions (`seedTestData()`, `cleanTestData()`)

---

## Writing Tests

### Unit Test Example

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\SettingsService;

class MyServiceTest extends TestCase
{
    private $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MyService();
        cleanTestData(); // Clean database before each test
    }
    
    protected function tearDown(): void
    {
        cleanTestData(); // Clean database after each test
        parent::tearDown();
    }
    
    public function testSomething()
    {
        $result = $this->service->doSomething();
        $this->assertEquals('expected', $result);
    }
}
```

### Integration Test Example

```php
<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Form;
use App\Models\FormSubmission;

class MyIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cleanTestData();
    }
    
    public function testCompleteWorkflow()
    {
        // Create test data
        $form = Form::create([
            'name' => 'Test Form',
            'slug' => 'test-form',
            'active' => true,
        ]);
        
        // Perform actions
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => ['test' => 'data'],
            'status' => 'pending',
        ]);
        
        // Assert results
        $this->assertNotNull($submission->id);
        $this->assertEquals('pending', $submission->status);
    }
}
```

### Best Practices

1. **Isolation:** Each test should be independent
2. **Cleanup:** Use `setUp()` and `tearDown()` for data cleanup
3. **Descriptive Names:** Test method names should describe what they test
4. **Single Assertion Focus:** Each test should focus on one behavior
5. **AAA Pattern:** Arrange, Act, Assert
6. **Mock External Dependencies:** Don't rely on external APIs or services

---

## Smoke Tests

Smoke tests are PHP scripts that perform end-to-end validation using real database operations.

### API Smoke Test Suite (v2.0)

Comprehensive end-to-end testing of all API endpoints with admin authentication and CRUD validation.

**Basic Usage:**

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

**Available Options:**

- `--url=<base_url>` - Base URL of the application (required)
- `--admin-email=<email>` - Admin email for authentication
- `--admin-password=<pass>` - Admin password for authentication
- `--readonly` - Run in read-only mode (safe for production, GET requests only)
- `--verbose` - Show detailed output (default)
- `--quiet` - Show minimal output
- `--help` - Show help message

**Test Modes:**

1. **Read-Only Mode (`--readonly`)**
   - Only performs GET requests
   - Safe to run on production
   - Tests public endpoints without auth
   - Tests admin endpoints with auth (if credentials provided)
   - No data modification
   - Validates response structures

2. **Full CRUD Mode (default)**
   - Creates temporary test fixtures
   - Tests POST, PUT, PATCH, DELETE operations
   - Automatically cleans up all created resources
   - Should only be run on development/staging environments
   - Validates complete CRUD workflows

**What it tests:**

**Public Endpoints (no authentication):**
- Health endpoint (`/api/test.php`)
- Content endpoints (services, portfolio, testimonials, FAQ, content blocks)
- Public settings endpoint (`/api/settings.php`)
- Public calculator settings endpoint (`/api/calculator-settings.php`)

**Authenticated Endpoints (requires admin credentials):**
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

**Response Validation:**
- HTTP status codes (200, 201, 204, 404, etc.)
- Response structure (success/data/meta keys)
- Data integrity and persistence
- Authentication and authorization

**Cleanup:**
- Automatically deletes all created test resources
- Respects foreign key constraints
- Verifies successful cleanup
- Reports any cleanup failures

**Exit Codes:**
- `0` - All tests passed
- `1` - One or more tests failed

**Example Output:**

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

**Pre-Deployment Checklist:**

Before declaring the site synced with the database:

1. Run full CRUD test on staging:
   ```bash
   php scripts/api_smoke.php \
     --url=https://staging.3dprint-omsk.ru \
     --admin-email=admin@example.com \
     --admin-password=SecurePass123
   ```

2. Run read-only check on production:
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

### Form API Smoke Test

Tests the complete form submission workflow:

```bash
php scripts/form-api-smoke.php
```

**What it tests:**
1. Creates a test form with fields
2. Retrieves form from database
3. Validates required fields
4. Submits form data
5. Verifies submission persistence
6. Creates linked order
7. Verifies order-submission relationship
8. Tests validation rules
9. Queries submissions by status
10. Updates submission status

### Settings Service Smoke Test

Tests the settings service functionality:

```bash
php scripts/test-settings-service.php
```

**What it tests:**
- Get all settings
- Set/get single setting
- Cache operations (warm, invalidate)
- Grouped reads
- Bulk updates
- Audit logging
- Validation rules
- Type casting (string, int, bool, float, array, JSON)

### Eloquent ORM Smoke Test

Tests Eloquent ORM functionality:

```bash
php scripts/eloquent-smoke.php
```

**What it tests:**
- Database connection
- Model CRUD operations
- Relationships
- Query scopes
- JSON casting

---

## Continuous Integration

### CI Configuration Example (GitHub Actions)

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  phpunit:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: pdo, pdo_sqlite, mbstring, json
          
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
        
      - name: Run PHPUnit tests
        run: composer test
        
      - name: Run smoke tests
        run: |
          php scripts/form-api-smoke.php
          php scripts/test-settings-service.php
```

### Pre-commit Hook

Add to `.git/hooks/pre-commit`:

```bash
#!/bin/bash

echo "Running PHPUnit tests..."
composer test

if [ $? -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi

echo "All tests passed!"
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

---

## Troubleshooting

### Common Issues

#### 1. "Class not found" Errors

**Solution:** Regenerate autoload files:
```bash
composer dump-autoload
```

#### 2. "Table not found" Errors

**Cause:** Test database schema not created

**Solution:** Check `tests/bootstrap.php` - the schema is created automatically. If issues persist, ensure SQLite is installed:
```bash
php -m | grep pdo_sqlite
```

#### 3. Cache-Related Test Failures

**Cause:** Cache directory doesn't exist or isn't writable

**Solution:**
```bash
mkdir -p storage/cache
chmod 755 storage/cache
```

#### 4. Tests Pass Individually but Fail Together

**Cause:** Tests are not properly isolated (shared state)

**Solution:** Ensure each test calls `cleanTestData()` in `setUp()`:
```php
protected function setUp(): void
{
    parent::setUp();
    cleanTestData();
}
```

#### 5. PHPUnit Not Found

**Cause:** Composer dependencies not installed

**Solution:**
```bash
composer install
```

#### 6. Slow Test Execution

**Cause:** Real database operations or missing indexes

**Solution:**
- Tests use in-memory SQLite which is fast
- If tests are slow, check for external API calls or file I/O
- Consider mocking external dependencies

### Debug Mode

Run tests with verbose output and stack traces:

```bash
vendor/bin/phpunit --verbose --debug
```

Show only failed test details:

```bash
vendor/bin/phpunit --stop-on-failure
```

### Test-Specific Configuration

Create `phpunit.xml.local` for local overrides (gitignored):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value="storage/test.sqlite"/>
    </php>
</phpunit>
```

---

## Test Metrics

After running tests, review:

1. **Pass Rate:** Should be 100%
2. **Coverage:** Aim for 80%+ on critical services
3. **Execution Time:** Should be under 10 seconds
4. **Test Count:** Currently 50+ assertions

### Generate Test Report

```bash
vendor/bin/phpunit --testdox-html report.html
```

---

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Ensure all existing tests pass
3. Add smoke tests for new workflows
4. Update this documentation
5. Run full test suite before committing

---

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Eloquent ORM Documentation](https://laravel.com/docs/8.x/eloquent)
- [Project API Reference](API_REFERENCE.md)
- [Test Checklist](TEST_CHECKLIST.md)

---

**Last Updated:** January 2025  
**Test Framework:** PHPUnit 9.5  
**Test Database:** SQLite (in-memory)  
**Coverage:** Unit, Integration, Smoke Tests
