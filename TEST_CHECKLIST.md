# ✅ COMPLETE TEST CHECKLIST

**Date:** January 2025  
**Site:** https://ch167436.tw1.ru/  
**Purpose:** Verify all functionality before production launch  

---

## 🔧 PRE-FLIGHT CHECKS

### 1. File Existence ✅
Run these commands to verify all files exist:

```bash
# HTML Pages (10 files)
ls -la *.html | wc -l  # Should be 10

# JavaScript Files (7 files)
ls -la js/*.js | wc -l  # Should be 7

# CSS Files (4 files)
ls -la css/*.css | wc -l  # Should be 4

# API Files (15 files)
ls -la api/*.php | wc -l  # Should be 13-15
```

### 2. Config Files ✅
```bash
# Verify config.php exists with production credentials
cat api/config.php | grep "DB_NAME"  # Should show: ch167436_3dprint
cat api/config.php | grep "DB_USER"  # Should show: ch167436_3dprint

# Verify .gitignore includes config.php
grep "api/config.php" .gitignore  # Should show: api/config.php
```

---

## 🌐 API ENDPOINT TESTS

### Test 1: Database Connection
**URL:** https://ch167436.tw1.ru/api/test.php

**Expected Response:**
```json
{
  "success": true,
  "message": "API is working correctly",
  "database_status": "Connected",
  "database_info": {
    "host": "localhost",
    "name": "ch167436_3dprint",
    "user": "ch167436_3dprint"
  },
  "tables_info": {
    "services": { "total": 6, "active": 6 },
    "testimonials": { "total": 4, "active": 4 },
    "faq": { "total": 6, "active": 6 },
    ...
  }
}
```

**✅ Pass Criteria:**
- success: true
- database_status: "Connected"
- All tables show total > 0

**❌ Fail Actions:**
- If database_status is not "Connected", check api/config.php credentials
- If tables show total: 0, proceed to initialization

---

### Test 2: Database Tables Status
**URL:** https://ch167436.tw1.ru/api/init-check.php

**Expected Result:**
- HTML page showing 7 tables
- Each table shows:
  - Total Records > 0
  - Active Records > 0
  - Status: ✅ OK

**✅ Pass Criteria:**
- All 7 tables exist
- All tables have data (total > 0)
- Active records > 0

**❌ Fail Actions:**
- If any table shows 0 active records, click "Fix: Set all to active=1"
- If tables are empty, click "Initialize Database"

---

### Test 3: Services API
**URL:** https://ch167436.tw1.ru/api/services.php

**Expected Response:**
```json
{
  "success": true,
  "services": [
    {
      "id": 1,
      "name": "FDM печать",
      "slug": "fdm",
      "description": "...",
      "active": 1,
      ...
    },
    ...
  ],
  "count": 6
}
```

**✅ Pass Criteria:**
- success: true
- services array has 6+ items
- Each service has: id, name, slug, description, active: 1

**❌ Fail Actions:**
- If services array is empty, run init-database.php
- If active: 0, run init-check.php?fix_active=1

---

### Test 4: FAQ API
**URL:** https://ch167436.tw1.ru/api/faq.php

**Expected Response:**
```json
{
  "success": true,
  "faq": [
    {
      "id": 1,
      "question": "Какие форматы файлов вы принимаете?",
      "answer": "...",
      "active": 1,
      ...
    },
    ...
  ],
  "count": 6
}
```

**✅ Pass Criteria:**
- success: true
- faq array has 6+ items
- Each FAQ has: question, answer, active: 1

---

### Test 5: Testimonials API
**URL:** https://ch167436.tw1.ru/api/testimonials.php

**Expected Response:**
```json
{
  "success": true,
  "testimonials": [
    {
      "id": 1,
      "name": "Алексей Иванов",
      "text": "...",
      "rating": 5,
      "active": 1,
      "approved": 1,
      ...
    },
    ...
  ],
  "count": 4
}
```

**✅ Pass Criteria:**
- success: true
- testimonials array has 4+ items
- Each testimonial has: name, text, rating, active: 1, approved: 1

---

### Test 6: Portfolio API
**URL:** https://ch167436.tw1.ru/api/portfolio.php

**Expected Response:**
```json
{
  "success": true,
  "portfolio": [],
  "count": 0
}
```

**✅ Pass Criteria:**
- success: true
- portfolio array (may be empty initially)

**Note:** Portfolio items can be added later via admin panel

---

### Test 7: Settings API
**URL:** https://ch167436.tw1.ru/api/settings.php

**Expected Response:**
```json
{
  "success": true,
  "settings": {
    "telegram_chat_id": "",
    "site_name": "3D Print Pro",
    ...
  }
}
```

**✅ Pass Criteria:**
- success: true
- settings object exists (may be empty)

---

### Test 8: Orders API (GET)
**URL:** https://ch167436.tw1.ru/api/orders.php

**Expected Response:**
```json
{
  "success": true,
  "orders": [],
  "total": 0,
  "page": 1,
  "per_page": 50
}
```

**✅ Pass Criteria:**
- success: true
- orders array exists (may be empty initially)

---

## 🎨 FRONTEND TESTS

### Test 9: Homepage Load
**URL:** https://ch167436.tw1.ru/

**Steps:**
1. Open URL in browser
2. Press F12 → Console tab
3. Look for initialization messages

**Expected Console Output:**
```
✅ APIClient initialized
✅ Database initialized
✅ Database using API
🚀 Инициализация приложения...
🔄 API GET services.php
✅ API GET services.php success
🔄 API GET faq.php
✅ API GET faq.php success
🔄 API GET testimonials.php
✅ API GET testimonials.php success
✅ Приложение запущено
```

**✅ Pass Criteria:**
- All messages show green checkmarks ✅
- No red error messages ❌
- API calls show success

**❌ Fail Actions:**
- If "Database using localStorage fallback", check apiClient initialization
- If any ❌ errors, check Console for details
- If API calls fail, check network tab (F12 → Network)

---

### Test 10: Services Section
**Location:** Homepage, scroll to "Наши услуги"

**Expected Result:**
- Section shows 6 service cards
- Each card has:
  - Icon
  - Name
  - Description
  - Features list
  - Price
  - "Узнать больше" button

**✅ Pass Criteria:**
- All 6 services visible
- Data loaded from API (not hardcoded)
- Cards clickable (open modal)

**Test:** Click on a service card
- Modal window should open
- Modal shows detailed service info
- "X" button closes modal

---

### Test 11: FAQ Section
**Location:** Homepage, scroll to "Часто задаваемые вопросы"

**Expected Result:**
- Section shows 6 FAQ items
- Each item has:
  - Question (bold)
  - Answer (hidden by default)
  - Expand/collapse arrow

**✅ Pass Criteria:**
- All 6 FAQ items visible
- Data loaded from API
- Items expandable/collapsible

**Test:** Click on FAQ item
- Answer should expand/collapse
- Arrow should rotate
- Only one item expanded at a time

---

### Test 12: Testimonials Section
**Location:** Homepage, scroll to "Отзывы клиентов"

**Expected Result:**
- Section shows 4 testimonials
- Each testimonial has:
  - Avatar
  - Name
  - Position/company
  - Review text
  - Star rating (5 stars)

**✅ Pass Criteria:**
- All 4 testimonials visible
- Data loaded from API
- Slider works (if implemented)

---

### Test 13: Contact Form
**Location:** Homepage, scroll to "Контакты"

**Steps:**
1. Fill in form:
   - Name: Test User
   - Phone: +7 (999) 123-45-67
   - Email: test@example.com (optional)
   - Subject: Dropdown selection
   - Message: Test message
2. Click "Отправить"

**Expected Result:**
1. Button shows loading state (disabled + spinner)
2. Console shows:
   ```
   🔄 API POST orders.php
   ✅ API POST orders.php success
   📤 Order submitted: ORD-xxxxxxxx
   ```
3. Success notification appears:
   "Спасибо! Мы свяжемся с вами в ближайшее время"
4. Form resets (clears all fields)
5. Button returns to normal state

**✅ Pass Criteria:**
- Form submits without errors
- Order saved to database
- User receives confirmation
- Form resets after submission

**Verification:**
- Check Console for success message ✅
- Check PHPMyAdmin → orders table for new record
- Verify order_number is generated
- Verify all form data is saved correctly

---

### Test 14: Calculator Form
**Location:** Homepage, scroll to "Калькулятор стоимости"

**Steps:**
1. Select technology: FDM/SLA
2. Enter dimensions: 100 x 100 x 100 mm
3. Enter weight: 50 g
4. Select material: PLA
5. Select infill: 20%
6. Select quantity: 1
7. Select finish: None
8. Click "Рассчитать стоимость"

**Expected Result:**
1. Calculator computes total price
2. Result section appears showing:
   - Technology
   - Material
   - Weight
   - Quantity
   - Total price
3. "Заказать" button appears
4. Clicking "Заказать" scrolls to contact form
5. Contact form pre-fills with calculator data

**✅ Pass Criteria:**
- Calculator computes correctly
- All selections reflected in price
- Order button works
- Form integration works
- Calculator data saved with order

---

### Test 15: Navigation
**Location:** Header menu

**Tests:**
1. Click "О нас" → Should go to /about.html
2. Click "Услуги" → Should go to /services.html
3. Click "Портфолио" → Should go to /portfolio.html
4. Click "Контакты" → Should go to /contact.html
5. Click logo → Should go to /index.html

**✅ Pass Criteria:**
- All links work
- Pages load without errors
- Navigation menu highlights current page
- Mobile menu works (toggle hamburger icon)

---

### Test 16: Mobile Responsiveness
**Steps:**
1. Press F12 → Toggle device toolbar
2. Select "iPhone SE" (375px width)
3. Scroll through page

**Expected Result:**
- All sections fit screen width
- No horizontal scrolling
- Text is readable
- Images scale properly
- Forms are usable
- Navigation menu collapses to hamburger icon

**Test on these widths:**
- 375px (iPhone SE)
- 768px (iPad)
- 1024px (Desktop)

**✅ Pass Criteria:**
- Site looks good on all screen sizes
- No layout breaks
- All functionality works

---

### Test 17: Incognito Mode
**Steps:**
1. Open incognito/private window
2. Visit: https://ch167436.tw1.ru/
3. Check Console (F12)
4. Verify data loads from API
5. Submit a form

**Expected Result:**
- Site loads normally
- Data fetched from API (not localStorage)
- Forms work
- Orders save to MySQL (not localStorage)
- No errors in Console

**✅ Pass Criteria:**
- Everything works in incognito mode
- Data persists after closing window (in MySQL)
- Other users can see submitted orders

---

### Test 18: Online/Offline Behavior
**Steps:**
1. Open site in browser: https://ch167436.tw1.ru/
2. Open Console (F12)
3. Verify data loads from API
4. Turn off API (or go to Network tab → Throttling → Offline)
5. Refresh the page
6. Check UI status banner and console messages
7. Submit a form while offline
8. Restore connection
9. Verify data reloads

**Expected Result:**

**When API is available (online):**
- Console shows: `✅ Database using API`
- Console shows: `🌐 Status: ONLINE`
- Data loads from MySQL
- No warning banner shown
- Form submissions go to server

**When API is unavailable (offline):**
- Console shows: `⚠️ Services loaded from cache`
- Console shows: `🌐 Status: OFFLINE - Using cached data`
- Warning banner appears: "⚠️ Нет соединения с сервером. Используются сохранённые данные."
- Banner has "Повторить" button
- Data loads from localStorage
- Form submission shows: "⚠️ Нет подключения к серверу. Ваша заявка сохранена локально..."
- Orders saved to localStorage as fallback

**When connection restored:**
- Click "Повторить" button in banner
- Console shows: `🌐 Status: ONLINE - API connection restored`
- Success notification: "✅ Соединение восстановлено"
- Data reloads from API
- Banner disappears

**✅ Pass Criteria:**
- Clear UI feedback for all connectivity states
- No JavaScript errors in console
- Graceful degradation to localStorage
- Form submissions work offline (saved locally)
- Automatic status detection and recovery
- Timestamps tracked for cache freshness

**Test with different scenarios:**
1. **Slow network:** Data should retry with backoff
2. **Stale cache:** Warning shown if cache > 5 minutes old
3. **No localStorage:** Error message with phone number contact info
4. **Intermittent connection:** Automatic retry with exponential backoff

---

### Test 19: Cache Freshness Detection
**Steps:**
1. Open site and load data from API
2. Open Console and type: `db.getAllSyncInfo()`
3. Verify all tables show `source: 'api'` and recent timestamps
4. Disconnect from internet
5. Refresh page
6. Check sync info again: `db.getAllSyncInfo()`
7. Wait 6+ minutes offline
8. Reload the page
9. Check for stale data warnings

**Expected Result:**
- Sync info shows timestamp, source, age, and isStale flag
- When using API: `source: 'api'`, `isStale: false`
- When using cache: `source: 'cache'`, `isStale: false` (if < 5 min)
- When cache is old: `source: 'cache'`, `isStale: true` (if > 5 min)
- Stale data triggers warning banner

**✅ Pass Criteria:**
- Cache metadata tracked for all tables
- Age calculated correctly
- Stale flag accurate (> 5 minutes)
- UI shows appropriate warnings for stale data

---

### Test 20: API Retry Logic
**Steps:**
1. Open site with Network tab (F12)
2. Throttle network to "Slow 3G"
3. Refresh page
4. Observe API requests in Network tab
5. Check Console for retry messages

**Expected Result:**
- Console shows retry attempts: `🔄 API GET services.php (retry 1/3)`
- Exponential backoff delays: 1s, 2s, 4s
- Maximum 3 retry attempts
- After retries exhausted, falls back to cache
- User sees notification about using cached data

**✅ Pass Criteria:**
- Automatic retries for network errors
- Exponential backoff implemented
- Max retries respected (3 attempts)
- Graceful fallback after retries fail
- Clear console logging of retry attempts

---

### Test 21: Status Indicator Component
**Steps:**
1. Open site
2. Look for connectivity status banner (hidden by default when online)
3. Open Console and type: `statusIndicator.getSummary()`
4. Disconnect from internet
5. Wait 5 seconds
6. Verify banner appears

**Expected Result:**

**Console output of getSummary():**
```javascript
{
  currentStatus: "online",
  api: {
    isOnline: true,
    lastSuccessfulRequest: 1234567890,
    timeSinceLastSuccess: 1234,
    isStale: false
  },
  database: {
    services: { lastSync: 1234567890, source: "api", age: 1234, isStale: false },
    // ... other tables
  }
}
```

**When offline:**
- Banner slides down from top
- Shows warning message
- "Повторить" button visible
- "×" dismiss button visible
- Banner persists until dismissed or online

**✅ Pass Criteria:**
- Status indicator initializes without errors
- getSummary() returns detailed status
- Banner shows/hides based on connectivity
- Retry button triggers reconnection attempt
- Dismiss button hides banner for 5 minutes

---

## 🔐 SECURITY TESTS

### Test 22: Config File Protection
**URL:** https://ch167436.tw1.ru/api/config.php

**Expected Result:**
- 403 Forbidden
- OR blank page
- OR "Access Denied"

**❌ CRITICAL FAIL if:**
- File contents are visible
- Database credentials are exposed

**Fix:**
- Check api/.htaccess has `Deny from all` for config.php
- Check file permissions (should be 600 or 644)

---

### Test 23: SQL Injection Protection
**Test:** Submit form with SQL injection attempt

**Steps:**
1. Fill form with:
   - Name: `'; DROP TABLE orders; --`
   - Phone: `+7 (999) 123-45-67`
   - Message: `<script>alert('xss')</script>`
2. Submit form

**Expected Result:**
- Form submits normally
- Data saved with special characters escaped
- No SQL error
- No JavaScript execution
- Database remains intact

**✅ Pass Criteria:**
- Special characters are escaped
- No database errors
- No XSS execution
- Orders table still exists

---

### Test 20: CORS Headers
**Test:** Check CORS headers in API responses

**Steps:**
1. Open Network tab (F12)
2. Refresh page
3. Click on any API request (e.g., services.php)
4. Check Response Headers

**Expected Headers:**
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type
```

**✅ Pass Criteria:**
- All CORS headers present
- API accessible from frontend
- No CORS errors in Console

---

## 📱 MULTI-PAGE TESTS

### Test 21: About Page
**URL:** https://ch167436.tw1.ru/about.html

**Checks:**
- [ ] Page loads without errors
- [ ] Title tag is unique
- [ ] Meta description is unique
- [ ] H1 tag present
- [ ] Navigation works
- [ ] Footer present
- [ ] No Console errors

---

### Test 22: Services Page
**URL:** https://ch167436.tw1.ru/services.html

**Checks:**
- [ ] Page loads without errors
- [ ] Services loaded from API
- [ ] Service cards display correctly
- [ ] Navigation works
- [ ] Contact form works

---

### Test 23: Portfolio Page
**URL:** https://ch167436.tw1.ru/portfolio.html

**Checks:**
- [ ] Page loads without errors
- [ ] Portfolio items loaded from API (may be empty)
- [ ] Filter buttons work (if items exist)
- [ ] Navigation works

---

### Test 24: Contact Page
**URL:** https://ch167436.tw1.ru/contact.html

**Checks:**
- [ ] Page loads without errors
- [ ] Contact form works
- [ ] Map embedded (if applicable)
- [ ] Contact info displayed
- [ ] Form submits to API

---

### Test 25: Admin Panel
**URL:** https://ch167436.tw1.ru/admin.html

**Checks:**
- [ ] Page loads without errors
- [ ] Orders table displays
- [ ] Services management works
- [ ] Testimonials management works
- [ ] FAQ management works
- [ ] Settings panel works

---

## 🚀 PERFORMANCE TESTS

### Test 26: Page Load Speed
**Tool:** Chrome DevTools Lighthouse (F12 → Lighthouse tab)

**Run Lighthouse Audit:**
- Mode: Navigation
- Categories: Performance, Accessibility, SEO
- Device: Desktop & Mobile

**✅ Pass Criteria:**
- Performance: 80+
- Accessibility: 90+
- SEO: 90+

**Improvements if needed:**
- Compress images
- Minify CSS/JS
- Enable caching
- Optimize database queries

---

### Test 27: API Response Time
**Tool:** Network tab (F12 → Network)

**Check:**
- services.php load time < 500ms
- faq.php load time < 500ms
- testimonials.php load time < 500ms
- orders.php POST time < 1000ms

**✅ Pass Criteria:**
- All API calls complete in < 1 second
- No timeout errors
- No failed requests

---

## 📊 DATABASE TESTS

### Test 28: Orders Table
**Tool:** PHPMyAdmin

**Checks:**
1. Open database: ch167436_3dprint
2. Open table: orders
3. Verify structure:
   - [ ] id (INT, PRIMARY KEY, AUTO_INCREMENT)
   - [ ] order_number (VARCHAR, UNIQUE)
   - [ ] name, phone, email fields
   - [ ] calculator_data (JSON)
   - [ ] status (ENUM)
   - [ ] created_at, updated_at (TIMESTAMP)
4. Submit test form
5. Verify new record appears
6. Check all fields populated correctly
7. Verify JSON fields decode properly

---

### Test 29: Services Table
**Tool:** PHPMyAdmin

**Checks:**
1. Open table: services
2. Verify 6+ services exist
3. Check all services have active = 1
4. Verify features field is valid JSON
5. Test adding new service via API
6. Test updating service via API
7. Test deleting service via API

---

### Test 30: Testimonials Table
**Tool:** PHPMyAdmin

**Checks:**
1. Open table: testimonials
2. Verify 4+ testimonials exist
3. Check all testimonials have:
   - active = 1
   - approved = 1
4. Test adding new testimonial via API

---

## ✅ FINAL ACCEPTANCE CRITERIA

### Must Pass (Critical):
- [ ] All API endpoints return success: true
- [ ] Database connection works
- [ ] All 7 tables exist and have data
- [ ] Homepage loads without errors
- [ ] Contact form submits successfully
- [ ] Orders save to MySQL database
- [ ] Config file is protected
- [ ] No SQL injection vulnerabilities
- [ ] CORS headers configured
- [ ] Incognito mode works

### Should Pass (Important):
- [ ] Calculator works correctly
- [ ] All pages load without errors
- [ ] Mobile responsive on all devices
- [ ] Navigation works on all pages
- [ ] FAQ expands/collapses
- [ ] Testimonials display correctly
- [ ] Services display correctly
- [ ] Console shows no errors
- [ ] Lighthouse score 80+

### Nice to Have (Optional):
- [ ] Portfolio items exist
- [ ] Telegram notifications configured
- [ ] Analytics tracking setup
- [ ] Image optimization
- [ ] CDN configured

---

## 🎯 SIGN-OFF

After completing ALL tests:

**Tested by:** _______________  
**Date:** _______________  
**Result:** PASS / FAIL  

**Issues Found:** _______________________________________________

**Sign-off:** All critical tests passed, site is READY FOR PRODUCTION ✅

---

## 📞 POST-LAUNCH MONITORING

### Day 1:
- [ ] Check orders table for submissions
- [ ] Monitor Console for errors
- [ ] Verify form submissions work
- [ ] Check Telegram notifications (if configured)

### Week 1:
- [ ] Review all submitted orders
- [ ] Check database size/growth
- [ ] Monitor API response times
- [ ] Review any error logs

### Month 1:
- [ ] Analyze user behavior
- [ ] Review performance metrics
- [ ] Check database backups
- [ ] Update content as needed

---

**Total Tests:** 30  
**Estimated Time:** 1-2 hours  
**Required Expertise:** Basic web development knowledge  

**Good luck! 🚀**
