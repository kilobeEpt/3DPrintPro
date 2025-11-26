# Testing Checklist - Critical Fixes

## 🔍 Automated Verification Results

### ✅ 1. Missing JS Files Check
```bash
grep -n "calculator.js\|telegram.js" includes/footer.php
```
**Result:** ✅ No references to calculator.js or telegram.js in footer.php

### ✅ 2. calculatePrice Function Check
```bash
grep -rn "calculatePrice" --include="*.php" --include="*.js" .
```
**Result:** ✅ No calculatePrice references found

### ✅ 3. Calculator Links Check
```bash
grep -n "#calculator" index.php portfolio.php services.php includes/header.php
```
**Result:** ✅ No #calculator references in main PHP files

### ✅ 4. Order Form Check
```bash
grep -n 'id="order-form"' index.php
```
**Result:** ✅ Order form exists at line 271

### ✅ 5. Portfolio Images Check
```bash
grep "image.*=>" data/content.php | grep -c "placeholder"
```
**Result:** ✅ All 6 portfolio images use placeholders

---

## 🧪 Manual Testing Checklist

### Browser Console Testing

1. **Open Website**
   - [ ] Navigate to https://3dprint-omsk.ru (or local development URL)
   - [ ] Press F12 to open Developer Tools
   - [ ] Go to Console tab

2. **Check for Errors**
   - [ ] ✅ No 404 error for `/js/calculator.js`
   - [ ] ✅ No 404 error for `/js/telegram.js`
   - [ ] ✅ No error "calculatePrice is not defined"
   - [ ] ✅ Should see: "✅ Static app initialized"
   - [ ] ✅ Console should be clean (no red errors)

### Network Tab Testing

3. **Check Network Requests**
   - [ ] Go to Network tab (F12)
   - [ ] Reload page (Ctrl+R or Cmd+R)
   - [ ] Filter by "JS"
   - [ ] ✅ Verify these JS files load successfully (200 OK):
     - `/js/utils.js`
     - `/js/validators.js`
     - `/js/order-form.js`
     - `/js/main.js`
   - [ ] ✅ No 404 errors for any JS files

4. **Check Portfolio Images**
   - [ ] Filter by "Img" in Network tab
   - [ ] Scroll to Portfolio section
   - [ ] ✅ All 6 placeholder images load (200 OK from via.placeholder.com)
   - [ ] ✅ Images display correctly with different colors

### Functionality Testing

5. **Theme Toggle**
   - [ ] Click theme toggle button (moon/sun icon in header)
   - [ ] ✅ Theme switches between light and dark
   - [ ] ✅ All text is readable in both themes
   - [ ] ✅ Icon changes (moon ↔ sun)
   - [ ] Reload page
   - [ ] ✅ Theme persists after reload

6. **Navigation**
   - [ ] Click "Заказать" in main navigation
   - [ ] ✅ Scrolls to order form section
   - [ ] Click "Главная" → scroll down → click hero button "Заказать 3D печать"
   - [ ] ✅ Scrolls to order form section
   - [ ] Go to Services page → click "Заказать услугу"
   - [ ] ✅ Redirects to home page order form section
   - [ ] Go to Portfolio page → click "Заказать 3D печать"
   - [ ] ✅ Redirects to home page order form section

7. **Order Form**
   - [ ] Fill out all required fields:
     - ФИО: "Test User"
     - Email: "test@example.com"
     - Телефон: "+7 (999) 123-45-67"
     - Telegram: "testuser"
     - Услуга: "FDM печать"
     - Описание: "Test order description with minimum 10 characters"
   - [ ] Check privacy checkbox
   - [ ] Click "Отправить заказ"
   - [ ] ✅ Form submits successfully
   - [ ] ✅ Success notification appears
   - [ ] ✅ Form resets after submission
   - [ ] Check Telegram bot
   - [ ] ✅ Notification received in Telegram

8. **Contact Form** (separate form at bottom)
   - [ ] Fill out contact form fields
   - [ ] Click "Отправить сообщение"
   - [ ] ✅ Form submits successfully
   - [ ] ✅ Success notification appears

9. **FAQ Accordion**
   - [ ] Click on any FAQ question
   - [ ] ✅ Answer expands/collapses smoothly
   - [ ] ✅ Chevron icon rotates
   - [ ] ✅ No console errors

10. **Testimonials Slider**
    - [ ] Wait 5 seconds
    - [ ] ✅ Testimonials auto-slide
    - [ ] Click left/right arrows
    - [ ] ✅ Manual navigation works

11. **Stats Animation**
    - [ ] Scroll to stats section
    - [ ] ✅ Numbers animate from 0 to target
    - [ ] ✅ Animation triggers only once

12. **Portfolio**
    - [ ] Go to Portfolio page
    - [ ] ✅ All 6 images display (colorful placeholders)
    - [ ] Click filter buttons (Промышленность, Ювелирные изделия, etc.)
    - [ ] ✅ Filtering works correctly
    - [ ] ✅ No console errors

13. **Services**
    - [ ] Go to Services page
    - [ ] ✅ All service cards display correctly
    - [ ] Click "Заказать услугу" on any service
    - [ ] ✅ Redirects to order form on home page

### Mobile Testing

14. **Responsive Design**
    - [ ] Open DevTools (F12) → Toggle device toolbar (Ctrl+Shift+M)
    - [ ] Test on iPhone SE (375px)
    - [ ] ✅ Navigation hamburger menu works
    - [ ] ✅ Forms are usable
    - [ ] ✅ Theme toggle visible and works
    - [ ] Test on iPad (768px)
    - [ ] ✅ Layout adapts correctly
    - [ ] Test on Desktop (1920px)
    - [ ] ✅ Full layout displays properly

### Performance Testing

15. **Page Load Speed**
    - [ ] Go to Network tab
    - [ ] Hard reload (Ctrl+Shift+R)
    - [ ] Check total page load time
    - [ ] ✅ Page loads in reasonable time (<3s on good connection)
    - [ ] ✅ No blocking resources

---

## 🐛 Common Issues & Solutions

### Issue: "calculatePrice is not defined"
**Status:** ✅ FIXED - Function and all references removed

### Issue: 404 for calculator.js
**Status:** ✅ FIXED - Reference removed from footer.php

### Issue: 404 for telegram.js
**Status:** ✅ FIXED - Reference removed from footer.php

### Issue: Portfolio images 404
**Status:** ✅ FIXED - Replaced with placeholder images

### Issue: Calculator section broken
**Status:** ✅ FIXED - Entire section removed, links redirect to order form

---

## 📝 Test Results

**Date Tested:** _______________

**Tester:** _______________

**Browser:** _______________

**All Tests Passed:** [ ] YES [ ] NO

**Notes:**
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

---

## 🚀 Deployment Checklist

Before deploying to production:

- [ ] All automated checks pass
- [ ] Manual testing complete (all checkboxes above checked)
- [ ] Theme toggle works in both themes
- [ ] Order form submits successfully
- [ ] Telegram notifications work
- [ ] No console errors
- [ ] Portfolio images load
- [ ] Mobile responsive
- [ ] Performance acceptable

**Ready for Production:** [ ] YES [ ] NO
