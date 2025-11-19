# Test Suite

PHPUnit test suite for 3D Print Pro platform covering form validation, settings service, and integration workflows.

## Quick Start

```bash
# Run all tests
composer test

# Run unit tests only
vendor/bin/phpunit --testsuite Unit

# Run integration tests only
vendor/bin/phpunit --testsuite Integration
```

## Test Structure

```
tests/
├── bootstrap.php              # Test environment initialization
├── Unit/                      # Unit tests (isolated components)
│   ├── SettingsServiceTest.php    # Settings service tests
│   └── FormValidationTest.php     # Form validation tests
└── Integration/               # Integration tests (multiple components)
    └── FormSubmissionTest.php     # Form submission workflow tests
```

## Test Categories

### Unit Tests

**SettingsServiceTest.php** - 25+ tests covering:
- Get/set operations
- Type casting (string, int, bool, float, array, JSON)
- Validation rules (min, max, type, length)
- Cache operations (warm, invalidate, auto-cache)
- Audit logging
- Grouped reads
- Bulk updates

**FormValidationTest.php** - 15+ tests covering:
- Form creation
- Field types (text, email, phone, textarea, select, etc.)
- Validation rules
- Field relationships
- Query scopes (required, byType, active)
- Sort ordering

### Integration Tests

**FormSubmissionTest.php** - 10+ tests covering:
- Complete submission workflow
- Form-order linking
- Multiple submissions
- Status updates
- JSON data handling
- Bulk processing
- Cascade delete

## Database

Tests use an **in-memory SQLite database** that is:
- Created fresh for each test run
- Isolated from production/development databases
- Fast (no disk I/O)
- Automatically cleaned between tests

Schema is created in `bootstrap.php` covering all tables:
- settings, settings_audit
- forms, form_fields, form_submissions, form_submission_values
- orders

## Helper Functions

Available in `bootstrap.php`:

```php
// Seed test data
seedTestData([
    'forms' => [
        ['name' => 'Test Form', 'slug' => 'test', 'active' => true],
    ],
]);

// Clean all test data
cleanTestData();
```

## Writing New Tests

1. Extend `PHPUnit\Framework\TestCase`
2. Place in appropriate directory (Unit or Integration)
3. Use `setUp()` to initialize and `tearDown()` to cleanup
4. Call `cleanTestData()` to ensure test isolation

### Example

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cleanTestData();
    }
    
    public function testSomething()
    {
        // Arrange
        $expected = 'value';
        
        // Act
        $result = doSomething();
        
        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

## Running Specific Tests

```bash
# Single file
vendor/bin/phpunit tests/Unit/SettingsServiceTest.php

# Single method
vendor/bin/phpunit --filter testSetAndGetSingleSetting

# Pattern matching
vendor/bin/phpunit --filter Validation
```

## Debugging

```bash
# Verbose output
vendor/bin/phpunit --verbose

# Stop on first failure
vendor/bin/phpunit --stop-on-failure

# Show test names
vendor/bin/phpunit --testdox
```

## Coverage

Generate HTML coverage report (requires Xdebug):

```bash
composer test-coverage
open coverage/index.html
```

## CI Integration

Tests are designed to run in CI environments:
- No external dependencies
- SQLite in-memory database
- Self-contained test data
- Fast execution (<10 seconds)

## Documentation

See [docs/TESTING.md](../docs/TESTING.md) for complete testing guide.

---

**Test Framework:** PHPUnit 9.5  
**Database:** SQLite (in-memory)  
**Coverage:** Unit, Integration  
**Total Tests:** 50+ assertions
