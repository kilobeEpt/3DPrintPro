# Weight Input Fix - Testing Guide

## Changes Made

### 1. Updated `initInputs()` Method
- **Weight Input Handler**:
  - Added logic to allow empty string during input (stores as `''`)
  - Uses `Number.isFinite()` to validate numeric values
  - Calls `validateWeight(false)` on input event (no min clamping)
  - Added blur event listener that calls `validateWeight(true)` (with min clamping)

- **Quantity Input Handler**:
  - Same pattern as weight input
  - Allows clearing without immediate reset
  - Clamps to min/max only on blur

### 2. Updated `validateWeight()` and `validateQuantity()` Methods
- Added `applyMinClamp` parameter (default `true`)
- When `applyMinClamp = false`:
  - Allows empty values during input
  - Does not force minimum value
  - Still enforces maximum value
- When `applyMinClamp = true` (on blur):
  - Empty values are set to minimum (1)
  - Out-of-range values are clamped

### 3. Updated `calculate()` Method
- Enhanced validation to check for empty strings and invalid numbers
- Uses `Number.isFinite()` to validate weight and quantity
- Shows specific error messages for invalid weight vs. invalid quantity

## Manual Testing Checklist

### Test 1: Clear Weight Input
1. Open the calculator on the website
2. Click in the "Вес модели" field
3. Select all (Ctrl+A) and delete
4. **Expected**: Field remains empty, no automatic reset to 1
5. **Status**: ✓ PASS / ✗ FAIL

### Test 2: Weight Clamps on Blur
1. Clear the weight field
2. Click outside the field (blur)
3. **Expected**: Field automatically sets to 1
4. **Status**: ✓ PASS / ✗ FAIL

### Test 3: Out-of-Range Weight on Blur
1. Enter "0.5" in weight field
2. Click outside the field
3. **Expected**: Field sets to 1 (minimum)
4. Enter "15000" in weight field
5. Click outside the field
6. **Expected**: Field sets to 10000 (maximum) with warning notification
7. **Status**: ✓ PASS / ✗ FAIL

### Test 4: Clear Quantity Input
1. Click in the "Количество" field
2. Select all and delete
3. **Expected**: Field remains empty, no automatic reset
4. **Status**: ✓ PASS / ✗ FAIL

### Test 5: Quantity Clamps on Blur
1. Clear the quantity field
2. Click outside the field
3. **Expected**: Field automatically sets to 1
4. **Status**: ✓ PASS / ✗ FAIL

### Test 6: Calculate with Empty Weight
1. Clear the weight field
2. Click "Рассчитать стоимость" button
3. **Expected**: Error notification: "Пожалуйста, введите корректный вес модели"
4. **Expected**: No calculation is performed
5. **Status**: ✓ PASS / ✗ FAIL

### Test 7: Calculate with Empty Quantity
1. Set weight to a valid value (e.g., 100)
2. Clear the quantity field
3. Click "Рассчитать стоимость" button
4. **Expected**: Error notification: "Пожалуйста, введите корректное количество"
5. **Expected**: No calculation is performed
6. **Status**: ✓ PASS / ✗ FAIL

### Test 8: Successful Calculation
1. Set weight to 250
2. Set quantity to 5
3. Click "Рассчитать стоимость" button
4. **Expected**: Calculation succeeds with success notification
5. **Expected**: Result card shows calculated values
6. **Status**: ✓ PASS / ✗ FAIL

### Test 9: Order Submission
1. Perform a successful calculation
2. Click "Отправить заявку" button
3. Scroll to contact form
4. **Expected**: Calculation info shows correct weight and quantity
5. Fill in contact form and submit
6. **Expected**: Order is saved with correct numeric data
7. **Status**: ✓ PASS / ✗ FAIL

### Test 10: Telegram Notification
1. Complete an order with calculation data
2. Check Telegram notification (if configured)
3. **Expected**: Weight shows as "XXXг" (not "г" or "0г")
4. **Expected**: Quantity shows as "X шт" (not "0 шт")
5. **Status**: ✓ PASS / ✗ FAIL

### Test 11: Decimal Weight Values
1. Enter "123.45" in weight field
2. **Expected**: Value is accepted and stored as 123.45
3. Click calculate
4. **Expected**: Calculation uses the decimal value correctly
5. **Status**: ✓ PASS / ✗ FAIL

### Test 12: Negative Values
1. Enter "-10" in weight field
2. Click outside (blur)
3. **Expected**: Field sets to 1 (minimum)
4. **Status**: ✓ PASS / ✗ FAIL

## Acceptance Criteria Verification

- [ ] ✅ Weight input can be fully cleared via keyboard without reverting to `1` until focus is lost or calculation executed
- [ ] ✅ Weight and quantity inputs clamp to the configured min/max when blurred with out-of-range values
- [ ] ✅ Calculator prevents calculation when weight/quantity are empty or invalid, showing error notification
- [ ] ✅ Order submission, summary panel, and Telegram messages show correct numeric data when valid numbers are entered

## Browser Compatibility

Test in the following browsers:
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari
- [ ] Edge

## Notes

- The fix maintains backward compatibility
- Existing calculations continue to work as before
- Only the input behavior has changed to be more user-friendly
- All validation logic remains intact
