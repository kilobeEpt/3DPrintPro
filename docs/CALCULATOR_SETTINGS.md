# Calculator Settings System

## Overview

The calculator settings system provides dynamic configuration management for the 3D printing calculator, allowing administrators to modify prices, materials, services, quality multipliers, discounts, and calculation formulas without code changes.

## Architecture

### Database Storage
All calculator configuration is stored in the `settings` table with keys prefixed by `calculator.`:

- `calculator.materials` - Array of material definitions
- `calculator.services` - Array of additional services
- `calculator.quality_multipliers` - Object of quality level definitions
- `calculator.discounts` - Array of volume discount tiers
- `calculator.formulas` - Object of calculation formulas
- `calculator.validation` - Object of input validation rules

### API Layer

**Public Endpoint** (no auth required):
```
GET /api/calculator-settings.php
```
Returns active configuration for use by the frontend calculator.

**Admin Endpoints** (require authentication):
```
GET /api/calculator-settings.php?admin=1
POST /api/calculator-settings.php?action=materials
POST /api/calculator-settings.php?action=services
POST /api/calculator-settings.php?action=quality
POST /api/calculator-settings.php?action=discounts
POST /api/calculator-settings.php?action=formulas
POST /api/calculator-settings.php?action=validate
POST /api/calculator-settings.php?action=test
```

### Frontend Integration

**Configuration Loader** (`js/calculator-api-loader.js`):
- Automatically fetches configuration from API on page load
- Caches configuration in localStorage with 5-minute TTL
- Provides fallback to hardcoded CONFIG if API unavailable
- Exposes global `window.calculatorConfigLoader` instance

**Calculator Updates** (`js/calculator.js`):
- Modified to use API configuration when available
- Falls back to CONFIG object for backward compatibility
- All calculation methods check `this.apiConfig` first

### Admin UI

**Page**: `/admin/calculator-settings.php`
**Module**: `/admin/js/modules/calculator-settings.js`

**Features**:
- Tabbed interface for different configuration sections
- CRUD operations for materials, services, and discounts
- Inline editing for quality multipliers
- Formula editor with validation
- Test sandbox for running calculations
- Real-time validation and error display

## Formula Validator Service

**Class**: `App\Services\FormulaValidatorService`

**Features**:
- Validates mathematical expressions for safety
- Blocks dangerous functions (eval, system, etc.)
- Checks for balanced parentheses
- Validates variable declarations
- Supports math functions: min, max, abs, ceil, floor, round, sqrt, pow
- Test evaluation with sample data

**Example Formulas**:
```php
'infill_factor' => '0.3 + (infill / 100 * 0.7)'
'material_cost' => 'weight * material_price * infill_factor'
'labor_cost' => '500 + (weight * 2)'
'print_time' => '(weight / 10) * time_multiplier * quantity'
```

## Data Structures

### Material
```json
{
  "key": "pla",
  "name": "PLA",
  "price": 50,
  "technology": "fdm",
  "active": true,
  "order": 1
}
```

### Service
```json
{
  "key": "modeling",
  "name": "3D моделирование",
  "price": 500,
  "unit": "час",
  "active": true,
  "order": 1
}
```

### Quality Multiplier
```json
{
  "normal": {
    "name": "Нормальное",
    "multiplier": 1.0,
    "time": 1.0,
    "active": true,
    "order": 1
  }
}
```

### Discount
```json
{
  "minQuantity": 10,
  "percent": 10,
  "active": true
}
```

### Formula
```json
{
  "infill_factor": {
    "name": "Infill Factor",
    "description": "Calculates material usage based on infill percentage",
    "formula": "0.3 + (infill / 100 * 0.7)",
    "variables": ["infill"],
    "active": true
  }
}
```

## Setup

### 1. Seed Initial Configuration
```bash
php scripts/seed-calculator-settings.php
```

Options:
- `--force` - Overwrite existing settings

### 2. Include API Loader
Add to your calculator page HTML (before calculator.js):
```html
<script src="/js/calculator-api-loader.js"></script>
<script src="/js/calculator.js"></script>
```

### 3. Configure Frontend
The calculator will automatically use API configuration. The CONFIG object in `config.js` now serves as a fallback only.

## Usage

### Admin: Update Materials
1. Navigate to Admin Panel → Calculator Settings
2. Click Materials tab
3. Add/Edit/Delete materials
4. Click "Save All Changes"

### Admin: Test Configuration
1. Go to Sandbox tab
2. Enter test parameters (weight, quantity, etc.)
3. Click "Calculate"
4. Review estimated price and time

### Admin: Edit Formulas
1. Go to Formulas tab
2. Click Edit on a formula
3. Modify the formula expression
4. Click "Validate Formula" to test
5. Save changes

### Developer: Access Configuration
```javascript
// Load configuration
const config = await window.calculatorConfigLoader.getConfig();

// Access materials
const materials = config.materials;

// Force reload (e.g., after admin changes)
await window.calculatorConfigLoader.reloadConfig();

// Clear cache
window.calculatorConfigLoader.clearCache();
```

## Validation

### Material Validation
- `key` - Required, non-empty string
- `name` - Required, non-empty string
- `price` - Required, non-negative number
- `technology` - Required, one of: fdm, sla, sls

### Service Validation
- `key` - Required, non-empty string
- `name` - Required, non-empty string
- `price` - Required, non-negative number
- `unit` - Required, non-empty string

### Quality Validation
- `name` - Required, non-empty string
- `multiplier` - Required, positive number
- `time` - Required, positive number

### Discount Validation
- `minQuantity` - Required, integer >= 1
- `percent` - Required, number between 0-100

### Formula Validation
- Must be valid mathematical expression
- Only allowed operators: +, -, *, /, ()
- Only allowed functions: min, max, abs, ceil, floor, round, sqrt, pow
- All variables must be declared
- Must have balanced parentheses
- Must evaluate to numeric result

## Testing

### Unit Tests
```bash
composer test -- --filter FormulaValidatorServiceTest
```

Tests formula validation, evaluation, and security.

### Integration Tests
```bash
composer test -- --filter CalculatorSettingsApiTest
```

Tests CRUD operations, caching, and calculation logic.

### Manual Testing
1. Seed settings: `php scripts/seed-calculator-settings.php`
2. Access API: `curl http://localhost/api/calculator-settings.php`
3. Check cache: Browser DevTools → Application → Local Storage
4. Test calculation: Use sandbox in admin panel

## Security

### Formula Security
- Dangerous functions blocked (eval, exec, system, etc.)
- PHP variable access prevented
- Code injection patterns rejected
- Safe evaluation using PHP's built-in math functions

### API Security
- Public endpoint returns only active items
- Admin endpoints require authentication
- CSRF protection on write operations
- Audit logging of all changes
- Rate limiting on update operations

## Troubleshooting

### Calculator Shows Hardcoded Prices
**Solution**: Check browser console for API errors. Verify settings are seeded.

### Formula Validation Fails
**Solution**: Check formula syntax. Ensure all variables are declared. Use test button.

### Changes Not Reflected
**Solution**: 
1. Clear localStorage cache
2. Hard refresh browser (Ctrl+Shift+R)
3. Verify settings saved in DB

### API Returns 500 Error
**Solution**: Check PHP error logs. Verify database connection. Ensure settings table exists.

## Migration from Hardcoded CONFIG

### Before (config.js)
```javascript
const CONFIG = {
  materialPrices: {
    pla: { name: 'PLA', price: 50, technology: 'fdm' }
  }
};
```

### After (Database)
```json
{
  "calculator.materials": [
    {
      "key": "pla",
      "name": "PLA",
      "price": 50,
      "technology": "fdm",
      "active": true,
      "order": 1
    }
  ]
}
```

### Backward Compatibility
The system maintains full backward compatibility:
- CONFIG object still works
- calculator.js checks apiConfig first, falls back to CONFIG
- No breaking changes to existing installations

## Performance

### Caching Strategy
- **Frontend**: 5-minute localStorage cache
- **Backend**: 5-minute file cache in `storage/cache/settings.json`
- **Cache invalidation**: Automatic on write operations

### Load Times
- Initial load: ~100ms (API call + parse)
- Cached load: <5ms (localStorage read)
- Settings save: ~200ms (DB write + cache invalidate)

## Future Enhancements

Potential improvements:
1. Formula templates library
2. Import/Export configuration
3. A/B testing different price points
4. Historical price tracking
5. Multi-currency support
6. Seasonal pricing rules
7. Customer-specific pricing tiers

## Support

For issues or questions:
1. Check logs: `/var/log/apache2/error.log`
2. Review audit history in admin panel
3. Test API directly: `curl` commands
4. Check browser DevTools Network tab
