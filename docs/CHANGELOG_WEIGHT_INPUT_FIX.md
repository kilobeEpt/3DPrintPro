# Weight Input Fix - Changelog

## Summary
Fixed issue where users could not clear the "Вес модели" (weight) and "Количество" (quantity) input fields due to immediate validation forcing values back to minimum.

## Problem
- Users could not clear input fields with backspace/delete
- `Calculator.validateWeight()` was called on every `input` event
- Empty string or invalid values were immediately forced to minimum (1)
- Poor UX - prevented normal editing behavior

## Solution
Implemented a two-phase validation approach:

### Phase 1: Input Event (Lenient)
- Allow empty strings during typing
- Store parsed numeric values when valid
- Only enforce maximum limits
- Do NOT clamp to minimum

### Phase 2: Blur Event (Strict)
- Apply full validation when user leaves the field
- Clamp empty or invalid values to minimum (1)
- Clamp out-of-range values to min/max limits

## Technical Changes

### File: `js/calculator.js`

#### 1. Modified `initInputs()` Method

**Weight Input Handler (Lines 56-70):**
```javascript
weightInput.addEventListener('input', (e) => {
    const value = e.target.value.trim();
    if (value === '') {
        this.data.weight = '';  // Allow empty
    } else {
        const numValue = parseFloat(value);
        this.data.weight = Number.isFinite(numValue) ? numValue : '';
        this.validateWeight(false);  // No min clamping
    }
});

weightInput.addEventListener('blur', (e) => {
    this.validateWeight(true);  // Apply min clamping
});
```

**Quantity Input Handler (Lines 75-89):**
- Same pattern as weight input
- Allows clearing without immediate reset
- Full validation on blur

#### 2. Updated `validateWeight()` Method (Lines 121-143)

**New Signature:**
```javascript
validateWeight(applyMinClamp = true)
```

**Behavior:**
- `applyMinClamp = false`: Allows empty values, only enforces max
- `applyMinClamp = true`: Enforces both min and max limits
- Handles empty strings and non-finite numbers gracefully

#### 3. Updated `validateQuantity()` Method (Lines 145-167)

**New Signature:**
```javascript
validateQuantity(applyMinClamp = true)
```

**Behavior:**
- Same pattern as validateWeight
- Allows empty during input
- Clamps to [1, 1000] on blur

#### 4. Enhanced `calculate()` Method (Lines 226-238)

**Improved Validation:**
```javascript
// Check for empty or invalid weight
if (!Number.isFinite(weight) || weight <= 0 || weight === '') {
    app.showNotification('Пожалуйста, введите корректный вес модели', 'error');
    return null;
}

// Check for empty or invalid quantity
if (!Number.isFinite(quantity) || quantity <= 0 || quantity === '') {
    app.showNotification('Пожалуйста, введите корректное количество', 'error');
    return null;
}
```

## User Experience Improvements

### Before
1. User clicks in weight field
2. Selects all and presses Delete
3. Field immediately resets to "1"
4. User cannot clear the field
5. Frustrating editing experience

### After
1. User clicks in weight field
2. Selects all and presses Delete
3. Field remains empty ✓
4. User can type new value freely
5. When clicking outside, value normalizes to 1 if empty
6. Smooth, natural editing experience

## Validation Rules

### Weight (Вес модели)
- **Minimum:** 1 gram
- **Maximum:** 10,000 grams
- **During Input:** Can be empty, only max enforced
- **On Blur:** Empty → 1, < 1 → 1, > 10000 → 10000
- **Decimal Support:** Yes (e.g., 123.45)

### Quantity (Количество)
- **Minimum:** 1 piece
- **Maximum:** 1,000 pieces
- **During Input:** Can be empty, only max enforced
- **On Blur:** Empty → 1, < 1 → 1, > 1000 → 1000
- **Decimal Support:** Parsed as integer

## Error Messages

- **Empty Weight:** "Пожалуйста, введите корректный вес модели"
- **Empty Quantity:** "Пожалуйста, введите корректное количество"
- **Max Weight:** "Максимальный вес - 10000г. Для больших заказов свяжитесь с нами."
- **Max Quantity:** "Для заказов более 1000 шт свяжитесь с нами напрямую."

## Backward Compatibility

✅ **Fully Compatible**
- Existing calculations work unchanged
- Default behavior when values are valid is identical
- Only input/blur behavior improved
- No changes to calculation logic
- No database schema changes
- No API changes

## Testing

See `TESTING.md` for comprehensive test cases.

### Quick Smoke Tests
1. ✅ Clear weight field - stays empty until blur
2. ✅ Clear quantity field - stays empty until blur
3. ✅ Calculate with empty fields - shows error
4. ✅ Calculate with valid values - works correctly
5. ✅ Order submission - correct data saved
6. ✅ Telegram notifications - correct values sent

## Dependencies

No new dependencies added.

## Browser Support

Works with all modern browsers that support:
- ES6 JavaScript
- `Number.isFinite()`
- Arrow functions
- Event listeners

(All major browsers since ~2015)

## Known Issues

None.

## Future Enhancements

Potential improvements for future versions:
- Add input debouncing for real-time calculation
- Show validation hints below inputs
- Add keyboard shortcuts (e.g., Escape to reset)
- Visual feedback for invalid values
- Accessibility improvements (ARIA labels, screen reader support)

## Related Issues

This fix resolves the UX issue where users reported they "cannot change the weight" or "field resets when I try to clear it".

## Authors

- Implementation Date: 2024
- Modified Files: 1 (`js/calculator.js`)
- Lines Changed: ~80 lines modified/added

## Rollback Instructions

If needed, revert `js/calculator.js` to previous version:
```bash
git checkout HEAD~1 js/calculator.js
```

The previous implementation:
- Validated immediately on input event
- Used `parseFloat(e.target.value) || 0` for weight
- Used `parseInt(e.target.value) || 1` for quantity
- Called validation on every keystroke
