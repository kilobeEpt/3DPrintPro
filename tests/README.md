# Test Suite

PHPUnit test suite for 3D Print Pro platform covering comprehensive unit and integration tests.

## Quick Start

```bash
# Run all tests
composer test

# Run unit tests only
vendor/bin/phpunit --testsuite Unit

# Run integration tests only
vendor/bin/phpunit --testsuite Integration

# Run specific test file
vendor/bin/phpunit tests/Unit/ContentControllerTest.php
```

## Test Structure

```
tests/
├── bootstrap.php              # Test environment initialization (18 tables)
├── Unit/                      # Unit tests (isolated components)
│   ├── AdminAuthServiceTest.php         # Authentication & RBAC
│   ├── ContentControllerTest.php        # Content CRUD & slug management
│   ├── CsrfProtectionTest.php           # CSRF token handling
│   ├── FormValidationTest.php           # Form field validation
│   ├── FormulaValidatorServiceTest.php  # Calculator formula validation
│   ├── MediaUploadServiceTest.php       # File upload handling
│   ├── OrderExportServiceTest.php       # CSV/PDF export generation
│   ├── RateLimiterTest.php              # Rate limiting profiles
│   └── SettingsServiceTest.php          # Settings management
└── Integration/               # Integration tests (workflows)
    ├── AdminAuthIntegrationTest.php     # Login/logout workflows
    ├── BaseApiControllerTest.php        # API base functionality
    ├── CalculatorSettingsApiTest.php    # Calculator configuration
    ├── ContentApiTest.php               # Content management workflows
    ├── FormBuilderTest.php              # Form builder workflows (NEW)
    ├── FormSubmissionTest.php           # Form submission processing
    └── OrdersFlowTest.php               # Orders management workflows (NEW)
```

## Test Categories

### Unit Tests (100+ tests)

#### AdminAuthServiceTest.php
- User authentication with valid/invalid credentials
- Password hashing and verification
- Session management (create, validate, destroy)
- Rate limiting and account lockout
- CSRF token generation and validation
- Role-based access control checks

#### ContentControllerTest.php (NEW)
- Slug generation and uniqueness
- Cyrillic to Latin transliteration
- Featured content filtering
- Active/inactive content filtering
- Sort ordering
- JSON field handling (features, tags)
- Media metadata storage (image_path, image_size, image_mime)
- Category filtering

#### CsrfProtectionTest.php
- Token generation
- Token validation (valid, invalid, missing, expired)
- Token rotation on auth changes
- Timing-safe comparison

#### FormValidationTest.php
- Form creation and field management
- Field types (text, email, phone, number, textarea, select, radio, checkbox, file, hidden)
- Validation rules (required, minLength, maxLength, pattern, min, max)
- Field relationships and scopes

#### FormulaValidatorServiceTest.php
- Formula syntax validation
- Mathematical expression evaluation
- Security checks (dangerous functions blocked)
- Supported math functions (min, max, abs, ceil, floor, round, sqrt, pow)

#### MediaUploadServiceTest.php
- File type validation (MIME checking)
- File size limits (5MB max)
- Secure filename generation
- Storage path management

#### OrderExportServiceTest.php
- CSV generation with field selection
- UTF-8 BOM for Excel compatibility
- Signed URL generation and verification
- URL expiration checking
- Tamper protection

#### RateLimiterTest.php
- Profile-based rate limiting
- Violation logging
- Cleanup of expired records
- IP-based tracking

#### SettingsServiceTest.php
- Get/set operations
- Type casting (string, int, bool, float, array, JSON)
- Validation rules (min, max, pattern, maxLength)
- Cache operations (warm, invalidate, auto-cache)
- Audit logging
- Grouped reads and bulk updates

### Integration Tests (80+ tests)

#### AdminAuthIntegrationTest.php
- Complete login/logout workflows
- Multi-session management
- Account lockout enforcement
- Audit log creation
- CSRF rotation on login

#### BaseApiControllerTest.php
- Pagination (limit/offset/page)
- Validation trait usage
- Slug management trait
- Cache service integration
- SSE broadcasting

#### CalculatorSettingsApiTest.php
- CRUD operations for calculator config
- Formula validation in context
- Test calculations with settings
- Cache invalidation on updates

#### ContentApiTest.php
- Full CRUD workflows for services, portfolio, FAQ, testimonials
- Slug-based access
- Featured content management
- Media upload integration
- Cache headers and ETags

#### FormBuilderTest.php (NEW)
- Form creation with multiple fields
- Field ordering (drag-and-drop simulation)
- Conditional logic evaluation
- Notification settings (Telegram, email)
- Calculator mapping configuration
- Submission status management
- Cascade deletion

#### FormSubmissionTest.php
- Complete submission workflow
- Form-order linking
- Validation error handling
- Status updates
- JSON data handling
- Bulk processing

#### OrdersFlowTest.php (NEW)
- Order creation with unique numbers
- Status history tracking
- Internal notes management (CRUD)
- Order archiving and unarchiving
- Calculator data storage
- Filtering (status, type, date range, search)
- CSV export generation
- Signed URL generation and verification
- Cascade deletion with history and notes

## Database

Tests use an **in-memory SQLite database** with:
- 18 tables (complete schema)
- Fresh database for each test run
- Isolated from production
- Fast execution (no disk I/O)
- Automatic cleanup between tests

**Tables Included:**
- Content: services, portfolio, faq, testimonials, content_blocks
- Forms: forms, form_fields, form_submissions, form_submission_values
- Orders: orders, order_status_history, order_notes
- Settings: settings, settings_audit
- Auth: admin_users, admin_sessions, admin_login_attempts, admin_action_logs

## Smoke Tests

Complement PHPUnit tests with real-world smoke tests:

```bash
# Admin authentication (login, lockout, sessions, CSRF)
php scripts/admin-auth-smoke.php

# Content API (services, portfolio, FAQ, testimonials, content blocks)
php scripts/content-api-smoke.php

# Orders export (CSV/PDF, signed URLs, filters)
php scripts/orders-export-smoke.php

# Form API (builder, validation, submission)
php scripts/form-api-smoke.php

# Orders domain (status history, notes, archiving)
php scripts/orders-smoke-test.php

# Settings service (cache, validation, audit)
php scripts/test-settings-service.php

# Eloquent ORM (models, relationships, scopes)
php scripts/eloquent-smoke.php

# General API health
php scripts/api_smoke.php
```

## Helper Functions

Available in `bootstrap.php`:

```php
// Seed test data
$services = seedTestData(3, 'services', [
    'name' => 'Test Service',
    'slug' => 'test-service-' . uniqid(),
    'active' => true
]);

// Clean all test data
cleanTestData();
```

## Writing New Tests

### Unit Test Template

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\MyService;

class MyServiceTest extends TestCase
{
    protected $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        cleanTestData();
        $this->service = new MyService();
    }
    
    protected function tearDown(): void
    {
        cleanTestData();
        parent::tearDown();
    }
    
    /** @test */
    public function it_does_something()
    {
        // Arrange
        $expected = 'value';
        
        // Act
        $result = $this->service->doSomething();
        
        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

### Integration Test Template

```php
<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\MyModel;

class MyWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cleanTestData();
    }
    
    protected function tearDown(): void
    {
        cleanTestData();
        parent::tearDown();
    }
    
    /** @test */
    public function it_completes_workflow()
    {
        // Arrange
        $model = MyModel::create(['name' => 'Test']);
        
        // Act
        $result = $model->performAction();
        
        // Assert
        $this->assertTrue($result);
        $this->assertEquals('expected_state', $model->state);
    }
}
```

## Running Specific Tests

```bash
# Single file
vendor/bin/phpunit tests/Unit/ContentControllerTest.php

# Single method
vendor/bin/phpunit --filter it_generates_unique_slugs

# Pattern matching
vendor/bin/phpunit --filter Orders

# Test suite
vendor/bin/phpunit --testsuite Unit

# Stop on first failure
vendor/bin/phpunit --stop-on-failure
```

## Debugging

```bash
# Verbose output
vendor/bin/phpunit --verbose

# Show test names
vendor/bin/phpunit --testdox

# Debug single test
vendor/bin/phpunit --filter testName --debug
```

## Coverage

Generate HTML coverage report (requires Xdebug):

```bash
composer test-coverage
open coverage/index.html
```

**Current Coverage:**
- Models: ~90%
- Services: ~85%
- Controllers: ~80%
- Overall: ~85%

## CI Integration

Tests are CI-ready:
- ✅ No external dependencies
- ✅ SQLite in-memory database
- ✅ Self-contained test data
- ✅ Fast execution (<15 seconds)
- ✅ Deterministic results
- ✅ No network calls
- ✅ No file system dependencies

## Test Naming Conventions

**Methods:**
- `it_does_something()` - Descriptive, readable
- `testDoSomething()` - Traditional PHPUnit style

**Annotations:**
- `/** @test */` - Marks method as test (allows non-test prefix)
- `@dataProvider` - Data-driven tests
- `@depends` - Test dependencies

## Best Practices

1. **Test Isolation:** Always call `cleanTestData()` in `setUp()`
2. **Descriptive Names:** Use `it_does_something` format
3. **Arrange-Act-Assert:** Follow AAA pattern
4. **One Assertion:** Focus on single behavior per test
5. **Mock External Services:** Don't call real APIs/services
6. **Fast Tests:** Keep tests under 100ms each
7. **Deterministic:** Tests should always pass or fail consistently

## QA Checklist

For comprehensive manual testing, see:
- **[docs/QA_REGRESSION.md](../docs/QA_REGRESSION.md)** - Complete manual test cases
- **[docs/TEST_CHECKLIST.md](../docs/TEST_CHECKLIST.md)** - Testing procedures

## Documentation

- **[docs/TESTING.md](../docs/TESTING.md)** - Complete testing guide
- **[README.md](../README.md)** - Main project documentation

---

**Test Framework:** PHPUnit 9.5  
**Database:** SQLite (in-memory)  
**Coverage:** Unit (100+ tests), Integration (80+ tests)  
**Total Tests:** 180+ tests with 500+ assertions  
**Execution Time:** <15 seconds  
**Status:** ✅ All tests passing
