# QA Test Evidence & Screenshots Guide

**Branch:** `qa/e2e-qa-db-api-admin-ui-telegram-offline`  
**Date:** January 2025  
**Purpose:** Guide for collecting test evidence and screenshots in production

---

## 📸 Screenshot Checklist

### 1. Database Connectivity Tests

#### Test 1.1: API Health Check
**URL:** `https://your-domain.com/api/test.php`

**Expected Screenshot Elements:**
```json
{
  "success": true,
  "message": "API is working correctly",
  "database_status": "Connected",
  "tables_info": {
    "orders": { "total": X },
    "settings": { "total": 12 },
    "services": { "total": 6, "active": 6 }
  }
}
```

**Evidence:** Save as `evidence/01-api-health-check.png`

---

#### Test 1.2: Database Audit
**Command:** `php scripts/db_audit.php`

**Expected Output:**
```
========================================
DATABASE AUDIT REPORT
========================================
CONNECTION:
  Status: ✅ Connected
TABLES:
  Expected: 7
  Found: 7
  Status: ✅ OK
SCHEMA VALIDATION:
  Status: ✅ OK
========================================
```

**Evidence:** Save as `evidence/02-database-audit.txt`

---

#### Test 1.3: Init Check Page
**URL:** `https://your-domain.com/api/init-check.php`

**Expected Screenshot Elements:**
- All 7 tables listed
- Green checkmarks (✅ OK) for each table
- Record counts > 0
- No red errors

**Evidence:** Save as `evidence/03-init-check.png`

---

### 2. API Endpoint Tests

#### Test 2.1: Services API
**URL:** `https://your-domain.com/api/services.php`

**Expected Response:**
```json
{
  "success": true,
  "services": [...],
  "count": 6
}
```

**Evidence:** Save as `evidence/04-api-services.png`

---

#### Test 2.2: Orders API (Empty)
**URL:** `https://your-domain.com/api/orders.php`

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

**Evidence:** Save as `evidence/05-api-orders-empty.png`

---

#### Test 2.3: FAQ API
**URL:** `https://your-domain.com/api/faq.php`

**Expected Response:**
```json
{
  "success": true,
  "faq": [...],
  "count": 8
}
```

**Evidence:** Save as `evidence/06-api-faq.png`

---

### 3. Frontend Tests

#### Test 3.1: Homepage Load
**URL:** `https://your-domain.com/`

**Browser Console (F12):**
```
✅ APIClient initialized
✅ Database initialized
✅ Database using API
🚀 Инициализация приложения...
🔄 API GET services.php
✅ API GET services.php success
✅ Приложение запущено
```

**Evidence:**
- `evidence/07-homepage-console.png` (Console logs)
- `evidence/08-homepage-full.png` (Full page screenshot)

---

#### Test 3.2: Services Section
**URL:** `https://your-domain.com/#services`

**Expected Elements:**
- 6 service cards visible
- Each card has icon, name, description, price
- Click opens modal

**Evidence:**
- `evidence/09-services-section.png` (Full section)
- `evidence/10-service-modal.png` (Modal open)

---

#### Test 3.3: Calculator Section
**URL:** `https://your-domain.com/#calculator`

**Test Steps:**
1. Select Technology: FDM
2. Select Material: PLA
3. Enter Weight: 50g
4. Select Infill: 20%
5. Click "Рассчитать"

**Expected Result:**
- Price calculated and displayed
- "Заказать" button appears
- No errors in console

**Evidence:**
- `evidence/11-calculator-empty.png` (Initial state)
- `evidence/12-calculator-filled.png` (With inputs)
- `evidence/13-calculator-result.png` (Price displayed)

---

#### Test 3.4: Contact Form
**URL:** `https://your-domain.com/#contact`

**Test Steps:**
1. Fill name: "Test User"
2. Fill phone: "+7 (999) 123-45-67"
3. Fill email: "test@example.com"
4. Select subject: "Расчет стоимости"
5. Fill message: "Test message"
6. Click "Отправить"

**Expected Result:**
- Loading spinner appears
- Success message shows
- Form clears
- Console shows: `✅ API POST orders.php success`

**Evidence:**
- `evidence/14-contact-form.png` (Filled form)
- `evidence/15-form-success.png` (Success message)
- `evidence/16-form-console.png` (Console logs)

---

#### Test 3.5: Mobile Responsiveness
**Device:** iPhone SE (375px)

**Test Steps:**
1. Open DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Select "iPhone SE"
4. Scroll through page

**Expected Result:**
- All content fits screen
- No horizontal scroll
- Hamburger menu visible
- Forms usable
- Text readable

**Evidence:**
- `evidence/17-mobile-hero.png` (Hero section)
- `evidence/18-mobile-services.png` (Services section)
- `evidence/19-mobile-calculator.png` (Calculator section)
- `evidence/20-mobile-menu.png` (Hamburger menu open)

---

### 4. Admin Panel Tests

#### Test 4.1: Admin Login
**URL:** `https://your-domain.com/admin.html`

**Test Steps:**
1. Enter login: "admin"
2. Enter password: "admin123"
3. Click "Войти"

**Expected Result:**
- Dashboard appears
- Statistics widgets load
- Navigation menu visible

**Evidence:**
- `evidence/21-admin-login.png` (Login screen)
- `evidence/22-admin-dashboard.png` (Dashboard)

---

#### Test 4.2: Orders Management
**Navigation:** Admin → Заказы

**Expected Elements:**
- Orders list (should show test order)
- Status dropdown
- Edit/Delete buttons
- Pagination

**Evidence:**
- `evidence/23-admin-orders-list.png` (Orders list)
- `evidence/24-admin-order-detail.png` (Order details)

---

#### Test 4.3: Services CRUD
**Navigation:** Admin → Услуги

**Test Steps:**
1. Click "Добавить услугу"
2. Fill form (name, description, price)
3. Click "Сохранить"
4. Verify service appears in list
5. Edit service
6. Delete service

**Evidence:**
- `evidence/25-admin-services-list.png` (Services list)
- `evidence/26-admin-service-create.png` (Create form)
- `evidence/27-admin-service-edit.png` (Edit form)

---

#### Test 4.4: Settings Configuration
**Navigation:** Admin → Настройки → Telegram

**Test Steps:**
1. Enter Bot Token
2. Enter Chat ID
3. Click "Сохранить"
4. Click "Отправить тестовое сообщение"

**Expected Result:**
- Settings saved
- Test message sent
- Telegram notification received

**Evidence:**
- `evidence/28-admin-telegram-settings.png` (Settings form)
- `evidence/29-telegram-notification.png` (Telegram screenshot)

---

### 5. Offline Mode Tests

#### Test 5.1: Online State
**URL:** `https://your-domain.com/`

**Browser Console:**
```javascript
// Check status
apiClient.getStatus()
// Expected: { isOnline: true, lastSuccessfulRequest: [timestamp] }

// Check sync info
db.getAllSyncInfo()
// Expected: { services: { lastSync: [timestamp], isFresh: true, source: 'api' } }
```

**Evidence:**
- `evidence/30-online-console.png` (Console commands)
- `evidence/31-online-banner.png` (No banner visible)

---

#### Test 5.2: Offline State
**Test Steps:**
1. Open DevTools → Network tab
2. Select "Offline" from throttling dropdown
3. Refresh page

**Expected Result:**
- Data loads from localStorage cache
- Yellow/red banner appears: "Нет подключения"
- Console shows: `🌐 Database using localStorage fallback`

**Evidence:**
- `evidence/32-offline-banner.png` (Offline banner)
- `evidence/33-offline-console.png` (Console logs)
- `evidence/34-network-offline.png` (Network tab)

---

#### Test 5.3: Offline Form Submission
**Test Steps:**
1. Keep network offline
2. Fill contact form
3. Click "Отправить"

**Expected Result:**
- Form saved to localStorage queue
- User message: "Сохранено локально. Отправим при подключении."
- Console shows: `💾 Order saved to offline queue`

**Evidence:**
- `evidence/35-offline-form.png` (Form submission)
- `evidence/36-offline-message.png` (User message)
- `evidence/37-offline-queue.png` (Console - queue entry)

---

#### Test 5.4: Reconnection
**Test Steps:**
1. Change network to "Online"
2. Wait 5-10 seconds

**Expected Result:**
- Green banner appears: "Подключение восстановлено"
- Offline queue auto-retries
- Data refreshes from API
- Console shows: `✅ Reconnected, retrying offline queue`

**Evidence:**
- `evidence/38-reconnect-banner.png` (Green banner)
- `evidence/39-reconnect-console.png` (Console logs)
- `evidence/40-reconnect-success.png` (Queue cleared)

---

### 6. Telegram Integration Tests

#### Test 6.1: Order Notification
**Test Steps:**
1. Submit order from public site
2. Check Telegram

**Expected Message:**
```
🎉 Новый заказ! #ORD-20250115-0001

👤 Клиент: Test User
📧 Email: test@example.com
📱 Телефон: +7 (999) 123-45-67

📝 Тема: Расчет стоимости
💬 Сообщение: Test message

💰 Сумма: 1500 ₽
```

**Evidence:**
- `evidence/41-telegram-order.png` (Telegram notification)
- `evidence/42-telegram-bot-chat.png` (Full chat view)

---

#### Test 6.2: Test Message from Admin
**Test Steps:**
1. Admin → Settings → Telegram
2. Click "Отправить тестовое сообщение"

**Expected Message:**
```
🔔 Тестовое сообщение
Telegram интеграция работает корректно!
```

**Evidence:**
- `evidence/43-telegram-test-message.png` (Telegram test)

---

### 7. Browser Compatibility Tests

#### Test 7.1: Chrome
**Browser:** Google Chrome 90+

**Test Steps:**
1. Open all pages
2. Test all forms
3. Check console for errors

**Evidence:**
- `evidence/44-chrome-homepage.png`
- `evidence/45-chrome-console.png`

---

#### Test 7.2: Firefox
**Browser:** Mozilla Firefox 88+

**Test Steps:**
1. Open all pages
2. Test all forms
3. Check console for errors

**Evidence:**
- `evidence/46-firefox-homepage.png`
- `evidence/47-firefox-console.png`

---

#### Test 7.3: Safari (if available)
**Browser:** Safari 14+

**Test Steps:**
1. Open all pages
2. Test all forms
3. Check console for errors

**Evidence:**
- `evidence/48-safari-homepage.png`
- `evidence/49-safari-console.png`

---

### 8. Performance Tests

#### Test 8.1: Page Load Time
**Tool:** Browser DevTools → Network tab

**Test Steps:**
1. Clear cache
2. Reload page
3. Check "Load" time in Network tab

**Expected:** < 2 seconds

**Evidence:**
- `evidence/50-performance-network.png` (Network waterfall)
- `evidence/51-performance-timing.png` (Timing details)

---

#### Test 8.2: API Response Time
**Tool:** Browser DevTools → Network tab

**Test Steps:**
1. Filter by "XHR"
2. Reload page
3. Click on API requests
4. Check "Time" column

**Expected:** 
- `/api/services.php` - < 200ms
- `/api/test.php` - < 100ms

**Evidence:**
- `evidence/52-api-timing.png` (API request timing)

---

## 📝 Evidence Collection Process

### Step 1: Create Evidence Directory
```bash
mkdir -p evidence
```

### Step 2: Collect Screenshots
- Use browser built-in screenshot tool (F12 → Ctrl+Shift+P → "Screenshot")
- Or use external tools like Snipping Tool, Lightshot, etc.
- Save with descriptive names matching guide above

### Step 3: Collect Console Logs
- Right-click in Console → "Save as..."
- Or copy/paste into text file
- Save with `.txt` extension

### Step 4: Collect API Responses
- Network tab → Right-click request → "Copy response"
- Save as `.json` file
- Format with JSON formatter if needed

### Step 5: Create Evidence Report
```bash
# Create evidence index
cat > evidence/README.md << 'EOF'
# QA Testing Evidence
Date: January 2025
Branch: qa/e2e-qa-db-api-admin-ui-telegram-offline

## Files
- 01-api-health-check.png - API health check response
- 02-database-audit.txt - Database audit output
- 03-init-check.png - Init check page
...
EOF
```

---

## 🔍 Validation Checklist

After collecting evidence, verify:

- [ ] All 52 evidence files collected
- [ ] Screenshots are clear and readable
- [ ] Console logs show no critical errors
- [ ] API responses include `"success": true`
- [ ] Mobile screenshots show responsive design
- [ ] Telegram notifications received
- [ ] Offline mode screenshots show banner
- [ ] Admin panel screenshots show all sections
- [ ] Browser compatibility verified in at least 2 browsers
- [ ] Performance metrics within acceptable range

---

## 📊 Evidence Summary Template

```markdown
# QA Evidence Summary

**Date:** [Date]
**Tester:** [Name]
**Environment:** [Production/Staging]

## Evidence Collected
- Database: ✅ 3 files
- API: ✅ 3 files
- Frontend: ✅ 10 files
- Admin: ✅ 7 files
- Offline: ✅ 9 files
- Telegram: ✅ 3 files
- Browsers: ✅ 6 files
- Performance: ✅ 3 files

**Total Evidence Files:** 44
**Missing Files:** 0
**Quality:** Excellent

## Key Findings
- All tests passed ✅
- No critical issues found
- Performance within targets
- Offline mode working correctly
- Telegram integration functional

## Sign-Off
Tester: [Signature]
Date: [Date]
Status: ✅ APPROVED
```

---

## 🎯 Quick Evidence Checklist

```
Database Connectivity (3 files)
├─ [✅] API health check
├─ [✅] Database audit
└─ [✅] Init check page

API Endpoints (3 files)
├─ [✅] Services API
├─ [✅] Orders API
└─ [✅] FAQ API

Frontend Tests (10 files)
├─ [✅] Homepage console
├─ [✅] Homepage full
├─ [✅] Services section
├─ [✅] Service modal
├─ [✅] Calculator empty
├─ [✅] Calculator filled
├─ [✅] Calculator result
├─ [✅] Contact form
├─ [✅] Form success
└─ [✅] Form console

Mobile Responsiveness (4 files)
├─ [✅] Mobile hero
├─ [✅] Mobile services
├─ [✅] Mobile calculator
└─ [✅] Mobile menu

Admin Panel (7 files)
├─ [✅] Login screen
├─ [✅] Dashboard
├─ [✅] Orders list
├─ [✅] Order detail
├─ [✅] Services list
├─ [✅] Service create
└─ [✅] Telegram settings

Offline Mode (9 files)
├─ [✅] Online console
├─ [✅] Online banner (none)
├─ [✅] Offline banner
├─ [✅] Offline console
├─ [✅] Network offline
├─ [✅] Offline form
├─ [✅] Offline message
├─ [✅] Offline queue
└─ [✅] Reconnect success

Telegram (3 files)
├─ [✅] Order notification
├─ [✅] Bot chat
└─ [✅] Test message

Browsers (6 files)
├─ [✅] Chrome homepage
├─ [✅] Chrome console
├─ [✅] Firefox homepage
├─ [✅] Firefox console
├─ [✅] Safari homepage (optional)
└─ [✅] Safari console (optional)

Performance (3 files)
├─ [✅] Network waterfall
├─ [✅] Timing details
└─ [✅] API timing

TOTAL: 52 evidence files ✅
```

---

## 🚀 Production Deployment Evidence

After production deployment, collect additional evidence:

### Post-Deployment Checklist
- [ ] Live site screenshot
- [ ] Live API health check
- [ ] Live database connection
- [ ] Live order submission
- [ ] Live Telegram notification
- [ ] Live admin panel access
- [ ] SSL certificate verification
- [ ] DNS propagation verification
- [ ] Performance benchmarks
- [ ] Error log check (first 24h)

**Evidence Directory:** `evidence/production/`

---

**END OF QA TEST EVIDENCE GUIDE**
