# Calculator Settings API Fix

## Problem Description

The `/api/calculator-settings.php` endpoint was returning empty or malformed JSON responses, causing "SyntaxError: Unexpected end of JSON input" errors in the frontend calculator.

## Root Cause

The primary issue was a **missing `serverError()` method** in `BaseApiController`. The `CalculatorSettingsController` was calling `$this->serverError()` in exception handlers, but this method didn't exist, causing PHP fatal errors that resulted in empty HTTP responses.

## Changes Made

### 1. Added Missing `serverError()` Method

**File**: `app/Http/Controllers/Api/BaseApiController.php`

```php
/**
 * Server error response (500)
 * 
 * @param string $message
 * @return void
 */
protected function serverError($message = 'Internal server error')
{
    \ApiResponse::serverError($message);
}
```

This method is called 8 times in `CalculatorSettingsController`:
- `getConfig()` - lines 101
- `getAdminConfig()` - line 115
- `updateMaterials()` - line 154
- `updateServices()` - line 193
- `updateQualityMultipliers()` - line 232
- `updateDiscounts()` - line 271
- `updateFormulas()` - line 316
- `testCalculation()` - line 410

### 2. Enhanced Error Handling in Controller

**File**: `app/Http/Controllers/Api/CalculatorSettingsController.php`

Added:
- Type safety checks to ensure all config arrays are actually arrays (not null)
- Better error logging with stack traces
- Explicit array validation before filtering

### 3. Improved Frontend Error Detection

**File**: `js/calculator-api-loader.js`

Enhanced `fetchFromApi()` method to:
- Read response as text first to detect empty responses
- Provide better error messages for JSON parse errors
- Log first 200 chars of response for debugging
- Validate that materials array exists and is non-empty
- Fall back to CONFIG if API returns empty data

## API Response Format

The API now always returns properly structured JSON:

### Success Response
```json
{
  "success": true,
  "data": {
    "materials": [...],
    "services": [...],
    "quality_multipliers": {...},
    "discounts": [...],
    "formulas": {...},
    "validation": {...}
  },
  "meta": {
    "cache_ttl": 300,
    "version": "1.0"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Failed to load calculator configuration"
}
```

## Fallback Mechanism

The calculator now has multiple layers of fallback:

1. **API (Primary)**: Fetch from `/api/calculator-settings.php`
2. **LocalStorage Cache**: 5-minute cached API response
3. **CONFIG Object (Fallback)**: Hardcoded values from `config.js`

The frontend automatically falls back if:
- API returns HTTP error (4xx, 5xx)
- Response is empty or not JSON
- Response has invalid structure
- Materials array is empty

## Testing the Fix

### 1. Test API Endpoint Directly

```bash
# Run the test script
php test-calculator-api.php
```

Expected output:
```
✅ Valid JSON response
Success: true
Data keys: materials, services, quality_multipliers, discounts, formulas, validation
Materials count: X
Services count: X

✅ API test passed!
```

### 2. Test with cURL

```bash
curl -s http://localhost/api/calculator-settings.php | python3 -m json.tool
```

### 3. Test in Browser

Open the browser console on a page with the calculator and check:

```javascript
// Check if loader is initialized
console.log(window.calculatorConfigLoader);

// Manually test the loader
window.calculatorConfigLoader.loadConfig()
  .then(config => console.log('✅ Config loaded:', config))
  .catch(error => console.error('❌ Error:', error));
```

### 4. Check Error Logs

```bash
# Check API logs
tail -f storage/logs/api_*.log

# Check for PHP errors
tail -f /var/log/nginx/error.log  # or Apache error log
```

## Seeding Calculator Settings

If the API returns empty arrays, seed the database:

```bash
php scripts/seed-calculator-settings.php
```

Or force overwrite existing settings:

```bash
php scripts/seed-calculator-settings.php --force
```

## Common Issues & Solutions

### Issue: "Empty API response"

**Cause**: PHP fatal error or server misconfiguration

**Solution**:
1. Check PHP error logs
2. Verify bootstrap dependencies are loaded
3. Ensure database connection is working
4. Run: `php test-calculator-api.php`

### Issue: "No materials in API response"

**Cause**: Settings not seeded in database

**Solution**:
```bash
php scripts/seed-calculator-settings.php
```

### Issue: Calculator still using old prices

**Cause**: LocalStorage cache not cleared

**Solution**:
```javascript
// In browser console
localStorage.removeItem('calculator_config');
window.calculatorConfigLoader.reloadConfig();
```

### Issue: "Invalid JSON in API response"

**Cause**: Output before JSON (warnings, echo statements)

**Solution**:
1. Check for `echo`, `print`, or `var_dump()` in PHP files
2. Ensure error display is off in production
3. Check bootstrap.php for output
4. Verify Content-Type header is set correctly

## Verification Checklist

- [x] `serverError()` method exists in BaseApiController
- [x] All 8 calls to `serverError()` in CalculatorSettingsController work
- [x] API returns valid JSON with proper structure
- [x] Frontend properly detects and logs errors
- [x] Fallback to CONFIG object works when API fails
- [x] Empty response detection works
- [x] Calculator settings are seeded in database
- [x] Test script passes

## Related Files

- `api/calculator-settings.php` - API endpoint
- `app/Http/Controllers/Api/CalculatorSettingsController.php` - Controller
- `app/Http/Controllers/Api/BaseApiController.php` - Base controller with common methods
- `js/calculator-api-loader.js` - Frontend API loader
- `js/calculator.js` - Calculator logic with fallback
- `scripts/seed-calculator-settings.php` - Database seeder
- `test-calculator-api.php` - API test script

## Additional Documentation

- [Calculator Settings Guide](CALCULATOR_SETTINGS.md)
- [API Reference](API_REFERENCE.md)
- [Testing Guide](TESTING.md)
