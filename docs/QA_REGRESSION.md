# QA Regression Testing Checklist

**Version:** 1.0  
**Last Updated:** January 2025  
**Purpose:** Manual test cases for comprehensive quality assurance of the 3D Print Pro admin panel and features

---

## Table of Contents

1. [Admin Panel Authentication](#admin-panel-authentication)
2. [Content Management](#content-management)
3. [Forms System](#forms-system)
4. [Orders Management](#orders-management)
5. [Calculator Settings](#calculator-settings)
6. [Global Settings](#global-settings)
7. [Security Features](#security-features)
8. [Real-Time Sync](#real-time-sync)
9. [Performance & UX](#performance--ux)
10. [Browser Compatibility](#browser-compatibility)

---

## Admin Panel Authentication

### Login Functionality

- [ ] **Valid Login**
  - Navigate to `/admin/login.php`
  - Enter valid credentials
  - Verify successful redirect to dashboard
  - Check welcome message displays correct user name

- [ ] **Invalid Password**
  - Enter valid email with wrong password
  - Verify error message: "Invalid credentials"
  - Confirm user remains on login page

- [ ] **Non-Existent User**
  - Enter non-existent email
  - Verify error message displayed
  - Confirm no session created

- [ ] **Rate Limiting**
  - Attempt 5 failed logins in rapid succession
  - Verify account locked message
  - Confirm 6th attempt rejected even with correct password
  - Wait 15 minutes and verify successful login

- [ ] **Remember Me**
  - Check "Remember Me" checkbox
  - Login successfully
  - Close browser completely
  - Reopen and navigate to admin area
  - Verify still logged in (session persists 30 days)

- [ ] **Session Timeout**
  - Login without "Remember Me"
  - Wait 31 minutes of inactivity
  - Try to access admin page
  - Verify redirect to login with session expired message

### Role-Based Access Control

- [ ] **Super Admin Access**
  - Login as super_admin
  - Verify "Users" menu item visible in sidebar
  - Access `/admin/users.php` successfully
  - Verify ability to create/edit/delete users

- [ ] **Admin Access**
  - Login as regular admin
  - Verify "Users" menu item NOT visible
  - Attempt direct access to `/admin/users.php`
  - Verify 403 Forbidden or redirect

- [ ] **Inactive User**
  - Set user status to "inactive" in database
  - Attempt login
  - Verify rejection with appropriate message

### Logout

- [ ] **Normal Logout**
  - Click logout button
  - Verify redirect to login page
  - Attempt to access admin page directly
  - Confirm redirect back to login

- [ ] **Session Cleanup**
  - Logout successfully
  - Check database `admin_sessions` table
  - Verify session record removed

---

## Content Management

### Services CRUD

- [ ] **Create Service**
  - Navigate to Services management
  - Click "Add Service"
  - Fill all required fields (name, description, price)
  - Add features as JSON or comma-separated
  - Save and verify success message
  - Check service appears in list

- [ ] **Edit Service**
  - Click edit on existing service
  - Modify name and price
  - Save changes
  - Verify updates reflected in list

- [ ] **Slug Generation**
  - Create service with Cyrillic name "3D Печать"
  - Verify slug auto-generated as "3d-pechat" (transliterated)
  - Attempt to create duplicate slug
  - Verify error message

- [ ] **Delete Service**
  - Click delete on service
  - Confirm deletion in modal
  - Verify service removed from list
  - Check public API no longer returns it

- [ ] **Inactive Service**
  - Set service to inactive
  - Save changes
  - Visit public website
  - Confirm service not displayed

### Portfolio Management

- [ ] **Upload Image**
  - Create/edit portfolio item
  - Upload JPEG image (< 5MB)
  - Verify preview displayed
  - Save and check image path stored

- [ ] **Invalid Upload**
  - Attempt upload of 10MB file
  - Verify error: "File too large"
  - Attempt upload of .exe file
  - Verify error: "Invalid file type"

- [ ] **Featured Content**
  - Mark portfolio item as featured
  - Save changes
  - Visit public website
  - Verify item shows in featured section

### FAQ Management

- [ ] **Create FAQ**
  - Add question and answer
  - Assign to category (e.g., "Technical")
  - Save successfully
  - Verify appears in correct category

- [ ] **Category Filtering**
  - Create FAQs in multiple categories
  - Use category filter dropdown
  - Verify only selected category items shown

### Testimonials

- [ ] **Create Testimonial**
  - Add author, content, rating (1-5)
  - Upload avatar image
  - Set company and position
  - Save and verify in list

- [ ] **Rating Validation**
  - Attempt to enter rating of 6
  - Verify error or auto-correction to 5
  - Attempt rating of 0
  - Verify validation error

---

## Forms System

### Form Builder

- [ ] **Create New Form**
  - Navigate to Forms management
  - Click "Create Form"
  - Enter name, slug, description
  - Save form
  - Verify appears in forms list

- [ ] **Add Fields**
  - Open form in builder
  - Add text field with label "Name"
  - Set validation: required, minLength=2
  - Add email field with email validation
  - Add textarea for message
  - Save field configuration

- [ ] **Field Ordering**
  - Drag field to reorder
  - Drop in new position
  - Save form
  - Reload page
  - Verify new order persists

- [ ] **Field Types**
  - Create fields of each type: text, email, phone, number, textarea, select, radio, checkbox, hidden
  - Verify each renders correctly in preview
  - Test validation for each type

- [ ] **Conditional Logic**
  - Add select field "Service Type"
  - Add text field "Project Details"
  - Set conditional: show "Project Details" only if "Service Type" equals "3D Printing"
  - Test in preview mode
  - Verify field shows/hides correctly

### Form Submissions

- [ ] **Submit Form (Public)**
  - Navigate to public form page
  - Fill all required fields
  - Submit form
  - Verify success message displayed
  - Check admin panel for new submission

- [ ] **Validation Errors**
  - Submit form with missing required field
  - Verify inline error message
  - Submit with invalid email format
  - Verify validation error

- [ ] **View Submission**
  - Open submission in admin panel
  - Verify all field values displayed correctly
  - Check linked order created (if order form)
  - Verify IP address and timestamp logged

- [ ] **Change Status**
  - Mark submission as "Processed"
  - Save changes
  - Reload page
  - Verify status updated

- [ ] **Delete Submission**
  - Click delete on submission
  - Confirm deletion
  - Verify removed from list

### Notifications

- [ ] **Telegram Notification**
  - Configure form with Telegram enabled
  - Submit form
  - Check Telegram chat for notification
  - Verify all key fields included in message

- [ ] **Email Notification**
  - Enable email notifications for form
  - Set recipient email
  - Submit form
  - Check recipient inbox
  - Verify email received with form data

---

## Orders Management

### Order Viewing

- [ ] **Orders List**
  - Navigate to Orders
  - Verify orders displayed with key info: number, name, date, status, amount
  - Check pagination works (if > 20 orders)

- [ ] **Filter by Status**
  - Select "New" status filter
  - Verify only new orders shown
  - Clear filter
  - Verify all orders shown again

- [ ] **Filter by Type**
  - Filter by "Order" type
  - Verify only orders (not contacts) shown
  - Switch to "Contact" type
  - Verify only contact forms shown

- [ ] **Date Range Filter**
  - Set "From" date to 1 week ago
  - Set "To" date to today
  - Apply filter
  - Verify only orders in range shown

- [ ] **Search**
  - Enter customer name in search
  - Verify matching orders shown
  - Search by email
  - Verify results correct
  - Search by phone number
  - Verify results correct

### Order Details

- [ ] **View Order**
  - Click on order to view details
  - Verify all fields displayed: customer info, service, message, calculator data (if present)
  - Check status history section present
  - Check notes section present

- [ ] **Change Status**
  - Select new status from dropdown
  - Add comment (optional)
  - Save status change
  - Verify status history logged with timestamp and admin name
  - Check Telegram/email notification sent (if enabled in settings)

- [ ] **Add Note**
  - Click "Add Note" button
  - Enter internal note text
  - Save note
  - Verify note appears with your name and timestamp

- [ ] **Edit Note**
  - Click edit on existing note
  - Modify text
  - Save changes
  - Verify updated note displayed

- [ ] **Delete Note**
  - Click delete on note
  - Confirm deletion
  - Verify note removed

### Order Export

- [ ] **CSV Export**
  - Select multiple orders (or apply filter)
  - Click "Export CSV" button
  - Verify download starts
  - Open CSV in Excel
  - Check all selected fields present
  - Verify UTF-8 encoding (Cyrillic displays correctly)

- [ ] **PDF Export** (if implemented)
  - Click "Export PDF"
  - Verify PDF generated
  - Open PDF
  - Check formatting and completeness

- [ ] **Field Selection**
  - Open export options
  - Deselect some fields (e.g., message)
  - Export CSV
  - Verify only selected fields included

### Order Archiving

- [ ] **Archive Order**
  - Click archive button on completed order
  - Verify order moved to archived
  - Check active orders list
  - Confirm archived order not shown

- [ ] **View Archived**
  - Toggle "Show Archived" filter
  - Verify archived orders displayed
  - Check archived date shown

- [ ] **Unarchive Order**
  - Click unarchive button
  - Verify order returns to active list

---

## Calculator Settings

### Configuration

- [ ] **Edit Materials**
  - Navigate to Calculator Settings → Materials
  - Modify price for PLA
  - Save changes
  - Visit calculator on public site
  - Verify new price used in calculations

- [ ] **Add Material**
  - Add new material (e.g., "Nylon")
  - Set price, technology, order
  - Mark as active
  - Save and verify appears in calculator dropdown

- [ ] **Edit Services**
  - Modify service price
  - Save changes
  - Test calculator
  - Verify updated price applied

- [ ] **Quality Multipliers**
  - Change multiplier for "High" quality
  - Save settings
  - Test calculation with high quality
  - Verify multiplier correctly applied

- [ ] **Discounts**
  - Set discount: 10% for quantity ≥ 5
  - Save changes
  - Test calculator with quantity 5
  - Verify discount applied
  - Test with quantity 4
  - Verify no discount

### Formula Management

- [ ] **Edit Formula**
  - Navigate to Formulas tab
  - Modify price calculation formula
  - Test in sandbox
  - Verify calculation correct
  - Save formula

- [ ] **Invalid Formula**
  - Enter invalid formula with syntax error
  - Attempt to save
  - Verify error message with details

- [ ] **Dangerous Functions**
  - Attempt formula with `system()` or `exec()`
  - Verify rejection with security error

### Frontend Integration

- [ ] **Calculator Loads Settings**
  - Clear browser cache
  - Visit calculator page
  - Open DevTools → Network
  - Verify request to `/api/calculator-settings.php`
  - Check response contains materials, services, formulas

- [ ] **Settings Cache**
  - Modify setting in admin
  - Wait 5 minutes (cache TTL)
  - Visit calculator
  - Verify new setting applied
  - Or: Clear localStorage and verify immediate update

---

## Global Settings

### Contact Information

- [ ] **Update Phone**
  - Navigate to Settings → Contacts
  - Change phone number
  - Save settings
  - Visit public website
  - Verify phone number updated in header/footer

- [ ] **Update Email**
  - Change contact email
  - Save settings
  - Check contact page
  - Verify email displayed and linked correctly

- [ ] **Working Hours**
  - Update working hours text
  - Save settings
  - Verify updated on contact page

### Social Media

- [ ] **Update Social Links**
  - Change Telegram link
  - Update VK link
  - Save settings
  - Check footer social icons
  - Click links
  - Verify correct destinations

### SEO Settings

- [ ] **Meta Tags**
  - Update site title, description, keywords
  - Save settings
  - View page source on public site
  - Verify meta tags updated

- [ ] **OG Image**
  - Upload Open Graph image
  - Save settings
  - Use Facebook Debugger to test URL
  - Verify OG image displays

### Integrations

- [ ] **Telegram Bot**
  - Update bot token and chat ID
  - Click "Test Telegram" button
  - Verify test message received in chat

- [ ] **SMTP Email**
  - Configure SMTP settings
  - Click "Test Email" button
  - Enter test recipient
  - Check inbox for test email

### Audit History

- [ ] **View Settings Audit**
  - Click "View History" button
  - Verify list of recent changes
  - Check each entry shows: setting key, old value, new value, changed by, timestamp

---

## Security Features

### CSRF Protection

- [ ] **Valid CSRF Token**
  - Submit form with valid CSRF token
  - Verify success

- [ ] **Missing CSRF Token**
  - Remove CSRF token from form submission (via DevTools)
  - Submit form
  - Verify 403 Forbidden error

- [ ] **Invalid CSRF Token**
  - Modify CSRF token value
  - Submit form
  - Verify rejection

### Rate Limiting

- [ ] **API Rate Limit**
  - Make 100 rapid requests to public API endpoint
  - Verify 429 Too Many Requests after threshold
  - Wait 1 minute
  - Verify access restored

- [ ] **Login Rate Limit**
  - (Already tested in Authentication section)

### Audit Logging

- [ ] **View Audit Logs**
  - Navigate to Audit Logs
  - Verify all admin actions logged: login, logout, create, update, delete

- [ ] **Filter Audit Logs**
  - Filter by specific user
  - Filter by action type (e.g., "delete")
  - Filter by date range
  - Verify filters work correctly

- [ ] **Export Audit Logs**
  - Click "Export CSV" on audit page
  - Verify CSV contains all filtered logs

- [ ] **Cleanup Old Logs**
  - Click "Cleanup" button
  - Set retention to 30 days
  - Confirm cleanup
  - Verify logs older than 30 days removed

### Database Backups

- [ ] **Manual Backup**
  - Navigate to Backup section or run `php database/backup.php`
  - Verify backup file created in `storage/backups/`
  - Check MD5 checksum file present
  - Verify backup.log updated

- [ ] **Backup Verification**
  - Run backup with `--verify` flag
  - Verify checksum validation passes

- [ ] **Backup Retention**
  - Set retention to 7 days
  - Run backup
  - Verify backups older than 7 days deleted

---

## Real-Time Sync

### SSE Connection

- [ ] **SSE Stream**
  - Open public website
  - Open DevTools → Network tab
  - Filter by "updates/stream"
  - Verify SSE connection established
  - Check for periodic heartbeat events

- [ ] **Content Update Push**
  - Keep public page open
  - In admin panel, update a service
  - On public page, verify notification appears
  - Check content auto-refreshes

### IndexedDB Caching

- [ ] **Cache Storage**
  - Open DevTools → Application → IndexedDB
  - Check "contentCache" database exists
  - Verify cached resources present (services, portfolio, etc.)

- [ ] **Cache Invalidation**
  - Update content in admin
  - Check public page IndexedDB
  - Verify cache timestamp updated
  - Reload page
  - Verify fresh content loaded

### Offline Handling

- [ ] **Offline Mode**
  - Load public page
  - Open DevTools → Network → Offline
  - Try to interact with page
  - Verify offline indicator displayed
  - Verify cached content still accessible

---

## Performance & UX

### Page Load Times

- [ ] **Admin Dashboard**
  - Open DevTools → Performance
  - Record page load
  - Verify load time < 2 seconds
  - Check no JavaScript errors in console

- [ ] **Public Homepage**
  - Record page load
  - Verify load time < 3 seconds
  - Check Lighthouse score > 80

### Responsive Design

- [ ] **Mobile (375px)**
  - Toggle device toolbar
  - Set to iPhone SE
  - Navigate admin panel
  - Verify tables scroll horizontally
  - Check forms usable
  - Verify buttons not truncated

- [ ] **Tablet (768px)**
  - Set to iPad
  - Test all admin pages
  - Verify layout adapts correctly

- [ ] **Desktop (1920px)**
  - Test on large screen
  - Verify layout uses space efficiently
  - Check no excessive whitespace

### Loading States

- [ ] **Skeleton Screens**
  - Clear cache
  - Load public page
  - Verify skeleton loaders displayed while content loads

- [ ] **Button Loading States**
  - Submit form
  - Verify button shows loading spinner
  - Verify button disabled during submission

---

## Browser Compatibility

### Chrome/Edge (Chromium)

- [ ] Test all core features in latest Chrome
- [ ] Verify no console errors
- [ ] Check styling correct

### Firefox

- [ ] Test login and admin navigation
- [ ] Verify forms work correctly
- [ ] Check AJAX requests succeed

### Safari (macOS/iOS)

- [ ] Test on Safari desktop
- [ ] Test on iPhone Safari
- [ ] Verify date pickers work
- [ ] Check file uploads functional

---

## Regression Test Execution

### Before Major Release

1. **Run all automated tests:**
   ```bash
   composer test
   php scripts/admin-auth-smoke.php
   php scripts/content-api-smoke.php
   php scripts/orders-export-smoke.php
   ```

2. **Execute critical path tests (30 min):**
   - Admin login/logout
   - Create/edit service
   - Submit contact form
   - Create order
   - Change order status
   - Export orders

3. **Execute full regression suite (2 hours):**
   - All sections above
   - Document any failures
   - Log bugs in issue tracker

### After Bug Fixes

1. **Verify fix:**
   - Reproduce original bug
   - Apply fix
   - Verify bug resolved

2. **Regression check:**
   - Test related features
   - Run automated tests
   - Spot check critical paths

---

## Sign-Off

**Tester Name:** ____________________  
**Date:** ____________________  
**Build/Version:** ____________________  
**Pass/Fail:** ____________________  
**Notes:**

---

## Appendix: Test Data

### Sample Test Users

```
Super Admin:
- Email: superadmin@test.com
- Password: SuperAdmin123

Regular Admin:
- Email: admin@test.com
- Password: Admin123

Editor (Limited):
- Email: editor@test.com
- Password: Editor123
```

### Sample Test Orders

```
Order 1:
- Type: order
- Name: Test Customer
- Email: customer@test.com
- Phone: +79001234567
- Service: 3D Printing
- Amount: 1500

Order 2:
- Type: contact
- Name: Contact User
- Email: contact@test.com
- Phone: +79007654321
- Message: General inquiry
```

### Test Files for Upload

- Valid JPEG: 500KB, 800x600px
- Valid PNG: 1MB, 1024x768px
- Invalid: test.exe, 10MB file

---

**Document End**
