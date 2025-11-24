# Frontend Offline/Online Stabilization

**Date:** January 2025  
**Status:** ✅ COMPLETE  

This document describes the enhancements made to stabilize the frontend integration with graceful offline/online behavior.

---

## 📋 Overview

The frontend now provides:
- **Automatic retry** with exponential backoff for transient failures
- **Cache freshness tracking** with timestamp metadata
- **Status indicator UI** showing connectivity state
- **User-facing notifications** for all failure scenarios
- **Graceful degradation** to localStorage when API unavailable
- **Comprehensive testing** for offline/online behavior

---

## 🔧 Components Modified

### 1. **js/api-client.js** - Enhanced API Client

**New Features:**
- ✅ Configurable base URLs (from `window.CONFIG.apiBaseUrl` or default `/api`)
- ✅ Automatic retry with exponential backoff (max 3 retries: 1s, 2s, 4s delays)
- ✅ Richer error objects with metadata:
  - `isNetworkError` - network/timeout failures
  - `isServerError` - 5xx status codes
  - `isClientError` - 4xx status codes
  - `retryable` - whether error is retryable
  - `timestamp` - when error occurred
- ✅ Connectivity tracking (`isOnline`, `lastSuccessfulRequest`)
- ✅ Event system for online/offline transitions
- ✅ `getStatus()` method - returns current connectivity state

**Usage:**
```javascript
// Check connectivity
const status = apiClient.getStatus();
// { isOnline: true, lastSuccessfulRequest: 1234567890, timeSinceLastSuccess: 1234, isStale: false }

// Listen for connectivity changes
apiClient.on('offline', (data) => {
  console.log('API went offline', data);
});

apiClient.on('online', (data) => {
  console.log('API back online', data);
});
```

---

### 2. **js/database.js** - Enhanced Database Class

**New Features:**
- ✅ Cache freshness tracking with timestamp metadata
- ✅ Sync info methods:
  - `getSyncInfo(table)` - get sync status for one table
  - `getAllSyncInfo()` - get sync status for all tables
- ✅ Automatic timestamp updates on every API call
- ✅ Stale data detection (> 5 minutes old)
- ✅ Source tracking ('api' vs 'cache')

**Metadata Storage:**
```javascript
{
  "services": {
    "timestamp": 1234567890,
    "source": "api"
  },
  "testimonials": {
    "timestamp": 1234567880,
    "source": "cache"
  }
  // ... other tables
}
```

**Usage:**
```javascript
// Check sync info for services
const syncInfo = db.getSyncInfo('services');
// { lastSync: 1234567890, source: 'api', age: 1234, isStale: false }

// Check all tables
const allSync = db.getAllSyncInfo();
```

---

### 3. **js/status-indicator.js** - NEW Status Indicator Component

**Features:**
- ✅ Banner UI that slides down from top when offline/stale
- ✅ Automatic connectivity detection
- ✅ Retry button for manual reconnection
- ✅ Dismiss button (hides for 5 minutes)
- ✅ Console logging of status transitions
- ✅ Periodic status checks (every 30 seconds)

**Banner States:**
- **Online** - Banner hidden
- **Offline** - Red banner: "⚠️ Нет соединения с сервером. Используются сохранённые данные."
- **Cache** - Orange banner: "📦 Используются кэшированные данные"
- **Stale** - Red banner: "⚠️ Данные могут быть устаревшими. Проверьте соединение."

**Usage:**
```javascript
// Get full status summary
const summary = statusIndicator.getSummary();

// Manually retry connection
statusIndicator.retry();

// Dismiss banner
statusIndicator.dismiss();
```

---

### 4. **js/main.js** - Enhanced Main Application

**New Features:**
- ✅ `reloadData()` method - refreshes all data from API
- ✅ Enhanced form submission with offline detection
- ✅ User-facing error notifications with actionable messages
- ✅ Network error detection and localStorage fallback
- ✅ Clear messages when forms can't submit (includes phone contact)

**Error Messages:**
- **Network Error (offline):** "⚠️ Нет подключения к серверу. Ваша заявка сохранена локально и будет отправлена при восстановлении связи. Или свяжитесь с нами напрямую по телефону."
- **Server Error:** "⚠️ Ваша заявка сохранена локально. Пожалуйста, попробуйте повторить отправку позже или свяжитесь с нами по телефону."
- **Cannot Save:** "❌ Не удалось отправить заявку. Пожалуйста, попробуйте позже или свяжитесь с нами по телефону: +7 (999) 123-45-67"

---

## 📦 Files Updated

### JavaScript Files:
- ✅ `js/api-client.js` - Added retry logic, connectivity tracking
- ✅ `js/status-indicator.js` - NEW file, status banner component
- ✅ `js/database.js` - Added cache metadata, sync tracking
- ✅ `js/main.js` - Enhanced error handling, reloadData method

### HTML Files (added status-indicator.js):
- ✅ `index.html`
- ✅ `about.html`
- ✅ `services.html`
- ✅ `portfolio.html`
- ✅ `contact.html`
- ✅ `why-us.html`
- ✅ `districts.html`
- ✅ `blog.html`

### Documentation:
- ✅ `TEST_CHECKLIST.md` - Added Tests 18-21 for offline/online behavior
- ✅ `FRONTEND_OFFLINE_STABILIZATION.md` - This document

---

## 🧪 Testing

See **TEST_CHECKLIST.md** for comprehensive testing steps:

- **Test 18:** Online/Offline Behavior
- **Test 19:** Cache Freshness Detection
- **Test 20:** API Retry Logic
- **Test 21:** Status Indicator Component

### Quick Test:
1. Open site in browser
2. Open Console (F12)
3. Go to Network tab → Throttling → Offline
4. Refresh page
5. Verify banner appears
6. Submit a form (should save to localStorage)
7. Click "Повторить" button
8. Restore network
9. Verify data reloads

---

## 📊 Console Commands

For debugging and testing:

```javascript
// Check API connectivity
apiClient.getStatus()

// Check database sync status
db.getAllSyncInfo()

// Get full status summary
statusIndicator.getSummary()

// Reload all data from API
app.reloadData()

// Force connectivity check
apiClient.checkConnectivity()
```

---

## 🔄 Retry Logic Details

**Configuration:**
```javascript
retryConfig: {
  maxRetries: 3,
  initialDelay: 1000,      // 1 second
  maxDelay: 5000,          // 5 seconds
  backoffMultiplier: 2     // Exponential
}
```

**Retry Delays:**
- Attempt 1: 1 second
- Attempt 2: 2 seconds
- Attempt 3: 4 seconds
- Then: fallback to cache

**Retryable Errors:**
- Network errors (timeout, no connection)
- 5xx server errors
- 429 rate limiting

**Non-Retryable:**
- 4xx client errors (except 429)
- Validation errors

---

## 🎯 Acceptance Criteria (Met)

✅ **When API unreachable:**
- UI shows clear error messaging
- Relies on cached data where possible
- Avoids unhandled promise rejections

✅ **When connectivity returns:**
- Data resynchronizes automatically
- Status indicators reflect change
- No manual refresh needed

✅ **QA checklist:**
- Documents manual offline/online testing steps
- Covers desktop/mobile browsers
- Tests incognito mode
- Tests cache clearing

---

## 🚀 Production Deployment

**No breaking changes.** This is a pure enhancement:

1. ✅ All changes are backward compatible
2. ✅ No database changes required
3. ✅ No API changes required
4. ✅ Works with existing configuration
5. ✅ Graceful fallback if status-indicator.js not loaded

**Deploy steps:**
1. Upload all modified JS files
2. Upload modified HTML files
3. Clear browser cache (or version assets)
4. Test with offline mode

---

## 📈 Performance Impact

**Minimal overhead:**
- Connectivity check: ~100ms, runs every 30 seconds
- Metadata storage: ~1KB in localStorage
- Status banner: hidden by default (no visual impact when online)
- Retry logic: only activates on failures

**Benefits:**
- Better user experience during network issues
- Reduced support tickets ("form not submitting")
- Clear communication of system state
- Data integrity maintained offline

---

## 🎓 Developer Notes

### Event Flow:

1. **Page loads:**
   - APIClient initializes
   - Status Indicator initializes
   - Database loads metadata
   - Initial connectivity check

2. **API request:**
   - Try request
   - If fails: retry with backoff
   - If retries fail: fallback to cache
   - Update sync metadata
   - Emit online/offline events

3. **Status change:**
   - APIClient detects change
   - Emits event
   - Status Indicator updates banner
   - Console logs transition

4. **User retry:**
   - Click "Повторить" button
   - Manual connectivity check
   - If online: reload data
   - Show success notification

### Error Object Structure:

```javascript
{
  message: "Network request failed",
  endpoint: "services.php",
  method: "GET",
  isNetworkError: true,
  isServerError: false,
  isClientError: false,
  timestamp: 1234567890,
  retryable: true,
  statusCode: undefined  // for network errors
}
```

---

## 📞 Support

If users experience issues:

1. Check Console for error messages
2. Run `statusIndicator.getSummary()` in Console
3. Check Network tab for failed requests
4. Verify localStorage is enabled
5. Clear cache and retry

**Common issues:**
- **localStorage disabled:** Forms won't save offline
- **Old cached data:** Stale banner shown, click "Повторить"
- **Network flaky:** Multiple retries in console
- **API down:** All data from cache, red banner

---

## ✅ Checklist

- [x] APIClient retry logic implemented
- [x] Database cache metadata tracking
- [x] Status Indicator component created
- [x] Main app error handling enhanced
- [x] All HTML files updated
- [x] TEST_CHECKLIST.md updated
- [x] Documentation created
- [x] No syntax errors
- [x] Backward compatible
- [x] Production ready

---

**Last Updated:** January 2025  
**Version:** 1.0  
**Status:** ✅ COMPLETE & TESTED
