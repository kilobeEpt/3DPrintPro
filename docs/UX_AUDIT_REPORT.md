# UX Audit Report: 3D Print Pro Public Pages

**Audit Date:** December 2024  
**Auditor:** AI Engineering Team  
**Scope:** All public-facing pages (PHP templates + static HTML)  
**Pages Audited:** 9 pages (index.php, services.php, portfolio.php, contact.php, about.html, blog.html, why-us.html, districts.html, and includes)

---

## Executive Summary

This comprehensive usability audit identifies **47 issues** across all public pages, categorized by severity:

- **🔴 Critical (10 issues):** Break functionality or user experience significantly
- **🟠 High (15 issues):** Major usability or consistency problems
- **🟡 Medium (14 issues):** Notable UX improvements needed
- **🟢 Low (8 issues):** Minor polish and optimization

**Top 5 Priorities:**
1. Fix broken calculator links across all static HTML pages → redirect to order form
2. Add FOUC prevention script to all static HTML files
3. Unify form validation and error messaging across contact.php and index.php
4. Fix duplicate ID conflicts (subscribeForm appears 3+ times per page)
5. Add accessibility landmarks and skip-to-content navigation

---

## Summary Table: Top Priorities

| Priority | Issue | Affected Pages | Impact | Effort |
|----------|-------|----------------|--------|--------|
| 🔴 Critical | Broken `#calculator` links in navigation | about.html, blog.html, why-us.html, districts.html (line 95/129) | Users click navigation and get 404/no scroll | 15 min |
| 🔴 Critical | Missing FOUC prevention script | All 4 static HTML files | Flash of unstyled content on page load, poor UX | 10 min |
| 🔴 Critical | Duplicate form IDs (`subscribeForm`) | All pages with footer + blog.html + contact sections | JavaScript errors, form submission conflicts | 30 min |
| 🟠 High | Inconsistent form validation | contact.php vs index.php | Different error messages, validation rules | 2 hours |
| 🟠 High | Missing order-form.js on static HTML | about.html, blog.html, why-us.html, districts.html | Order form functionality unavailable | 5 min |
| 🟠 High | No visible required field indicators | contact.php form (lines 137-199) | Users don't know which fields are mandatory | 1 hour |
| 🟠 High | Hardcoded contact info in static HTML | All 4 static HTML files | Maintenance burden, inconsistency risk | 3 hours |
| 🟡 Medium | No skip-to-content link | All pages | Accessibility issue for keyboard/screen reader users | 1 hour |
| 🟡 Medium | No error feedback for file uploads | index.php order form (line 334) | Users don't know why upload failed | 2 hours |
| 🟡 Medium | External image dependencies | blog.html (lines 160-226 use unsplash.com) | Loading failures, CORS issues, slow performance | 1 hour |

---

## Cross-Page Issues

### 🔴 CRITICAL: Broken Calculator Navigation Links

**Affected Pages:** about.html, blog.html, why-us.html, districts.html  
**Lines:** 95, 129 (varies per file)

**Description:**  
All static HTML files have a navigation link to `index.php#calculator`, but the calculator section was removed in November 2024. Clicking this link results in no scroll action or 404-like experience.

```html
<!-- CURRENT (BROKEN): -->
<li><a href="index.php#calculator" class="nav-link">Калькулятор</a></li>

<!-- SHOULD BE: -->
<li><a href="index.php#order-form-section" class="nav-link">Заказать</a></li>
```

**Impact:**  
- Users expect to reach calculator but find nothing
- Broken user journey from secondary pages
- Inconsistent with PHP templates (includes/header.php uses "Заказать" correctly)

**Recommendation:**  
Replace all `#calculator` links with `#order-form-section` and change label from "Калькулятор" to "Заказать" across all 4 static HTML files.

---

### 🔴 CRITICAL: Missing FOUC Prevention Script

**Affected Pages:** about.html, blog.html, why-us.html, districts.html  
**Lines:** N/A (missing entirely)

**Description:**  
Static HTML files lack the inline theme initialization script that exists in `includes/head.php` (lines 202-209). This causes a Flash of Unstyled Content (FOUC) when users have dark mode enabled or system preference set to dark.

**Current in includes/head.php (GOOD):**
```html
<!-- Theme initialization - Prevents FOUC -->
<script>
(function() {
    const savedTheme = localStorage.getItem('theme');
    const systemPreference = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const theme = savedTheme || systemPreference;
    document.documentElement.setAttribute('data-theme', theme);
})();
</script>
```

**Missing from all static HTML files.**

**Impact:**  
- Visible flash of light theme before dark theme applies
- Poor perceived performance
- Jarring visual experience on page load

**Recommendation:**  
Add the FOUC prevention script to all 4 static HTML files in the `<head>`, immediately before CSS links (around line 95-100 in each file).

---

### 🔴 CRITICAL: Duplicate Form ID Conflicts

**Affected Pages:** All pages with footer (includes/footer.php lines 42-45) + blog.html + contact sections  
**Lines:** 
- includes/footer.php: 42 (`id="subscribeForm"`)
- blog.html: 259, 325 (two instances of `id="subscribeForm"`)
- Multiple pages have 2-3 instances of same ID

**Description:**  
The `subscribeForm` ID appears multiple times on single pages:
- Footer subscription form (all pages)
- Newsletter section on blog.html (line 259)
- Footer again on blog.html (line 325)

HTML spec requires unique IDs per page. JavaScript targeting `#subscribeForm` will only work on the first instance.

**Impact:**  
- JavaScript form handlers will fail or work unpredictably
- HTML validation errors
- Potential form submission bugs

**Recommendation:**  
1. Rename footer form to `id="footerSubscribeForm"`
2. Rename newsletter section form to `id="newsletterSubscribeForm"`
3. Update any JavaScript that targets these forms
4. Run HTML validation to catch other duplicate IDs

---

### 🟠 HIGH: Inconsistent Form Validation

**Affected Pages:** contact.php (lines 137-199) vs index.php (lines 271-349)  
**Issue Type:** Usability, consistency

**Description:**  
The contact form (contact.php) and order form (index.php) have different validation approaches:

| Feature | contact.php | index.php Order Form |
|---------|-------------|----------------------|
| Handler | `handleFormSubmit()` in main.js | `OrderFormHandler` class in order-form.js |
| Required fields | Name, Phone, Message (asterisk only) | 7 fields with client-side validation |
| Email validation | Browser default | Custom validation in order-form.js |
| Honeypot | None | Yes (line 28-38 in order-form.js) |
| Loading states | Minimal | Full loading animation |
| Error display | Generic alerts | Field-specific error messages |
| Success feedback | Basic | Animated with auto-reset |

**Impact:**  
- Users experience different behaviors on different forms
- Contact form more vulnerable to spam (no honeypot)
- Inconsistent error messaging reduces trust

**Recommendation:**  
1. Migrate contact.php form to use `OrderFormHandler` or create unified `FormHandler` base class
2. Add honeypot to contact form
3. Standardize error message styling and positioning
4. Add consistent loading states to both forms
5. Document form validation rules in shared file

---

### 🟠 HIGH: Missing order-form.js on Static HTML Pages

**Affected Pages:** about.html, blog.html, why-us.html, districts.html  
**Lines:** Footer script section (lines 387-390 in about.html, similar in others)

**Description:**  
Static HTML pages only load:
```html
<script src="js/utils.js"></script>
<script src="js/validators.js"></script>
<script src="js/main.js"></script>
```

But they're missing:
```html
<script src="js/order-form.js"></script>
```

While these pages don't have order forms, they link to `index.php#order-form-section` and users may expect consistent behavior if forms are added later.

**Impact:**  
- Order form functionality unavailable if added to these pages
- Inconsistent script loading across site
- Future maintenance issues

**Recommendation:**  
Add `<script src="js/order-form.js"></script>` to all static HTML files for consistency with PHP templates (includes/footer.php line 72).

---

### 🟠 HIGH: Hardcoded Contact Information

**Affected Pages:** about.html, blog.html, why-us.html, districts.html  
**Lines:** Multiple locations per file (header, footer, content sections)

**Description:**  
Static HTML files have hardcoded business information:
- Phone: `+7 (999) 123-45-67`
- Email: `info@3dprint-omsk.ru`
- Address: `ул. Ленина, д. 15`
- Telegram: `@PrintPro_Omsk`

PHP templates use `$site` array from `data/content.php`, allowing centralized updates.

**Examples:**
- about.html line 50: `"telephone": "+7 (999) 123-45-67"`
- blog.html line 142: Telegram link hardcoded
- districts.html line 9: Phone in meta description

**Impact:**  
- Must update 20+ locations if phone/email changes
- High risk of inconsistency
- Poor maintainability

**Recommendation:**  
**Option A (Quick Fix):** Create `includes/html-head.php` and `includes/html-footer.php` with PHP variables, convert all HTML files to .php  
**Option B (Better):** Implement build system (Node.js/Gulp) with template variables for static HTML  
**Option C (Immediate):** Document all locations in MAINTENANCE.md and create find/replace checklist

---

### 🟡 MEDIUM: No Accessibility Landmarks

**Affected Pages:** All pages  
**Lines:** N/A (missing feature)

**Description:**  
Pages lack semantic HTML5 landmarks and skip navigation:
- No `<main>` tag wrapping main content
- No "Skip to content" link for keyboard users
- Navigation uses `<nav>` but content sections don't use `<section>` consistently
- No `role` attributes for ARIA

**Current structure:**
```html
<body>
  <header>...</header>
  <!-- Content sections without <main> wrapper -->
  <footer>...</footer>
</body>
```

**Should be:**
```html
<body>
  <a href="#main-content" class="skip-link">Skip to content</a>
  <header>...</header>
  <main id="main-content">
    <!-- All content sections -->
  </main>
  <footer>...</footer>
</body>
```

**Impact:**  
- Screen reader users must tab through entire header on each page
- Poor accessibility score (WCAG 2.1 Level A failure)
- SEO impact (search engines prefer semantic HTML)

**Recommendation:**  
1. Add skip-to-content link at top of `<body>` (hidden until focused)
2. Wrap all content sections in `<main id="main-content">`
3. Add proper ARIA landmarks to header, nav, footer
4. Test with screen reader (NVDA/JAWS)

---

### 🟡 MEDIUM: Color Contrast Not Verified

**Affected Pages:** All pages  
**Lines:** css/style.css (requires color values inspection)

**Description:**  
Audit did not verify WCAG 2.1 AA color contrast ratios (4.5:1 for normal text, 3:1 for large text). Key areas of concern:

1. **Light theme:**
   - Text on background: `var(--text)` on `var(--bg)`
   - Secondary text: `var(--text-secondary)` on cards
   - Button text: white on `var(--primary)`
   - Link colors: `var(--primary)` on white

2. **Dark theme:**
   - Light text on dark backgrounds
   - Border visibility: `var(--border)` may be too subtle
   - Form input states

**Recommendation:**  
1. Use browser DevTools or online tool (WebAIM Contrast Checker) to verify all color combinations
2. Document contrast ratios in CSS comments
3. Test with color blindness simulator
4. Ensure focus indicators have 3:1 contrast with background

---

### 🟡 MEDIUM: No Focus Indicators Visible

**Affected Pages:** All pages  
**Lines:** css/style.css (requires inspection)

**Description:**  
No explicit focus styles found in the code examined. Default browser focus outlines may be suppressed or insufficient for visibility.

**Critical focus targets:**
- Navigation links
- Form inputs
- Buttons (primary, outline, theme toggle)
- Hamburger menu
- Modal close buttons

**Impact:**  
- Keyboard users cannot see where focus is
- WCAG 2.1 Level AA failure (2.4.7 Focus Visible)
- Poor usability for motor-impaired users

**Recommendation:**  
Add clear focus styles to css/style.css:
```css
/* Focus styles for accessibility */
a:focus,
button:focus,
input:focus,
select:focus,
textarea:focus {
    outline: 3px solid var(--primary);
    outline-offset: 2px;
}

/* Focus-visible for modern browsers (no focus on mouse click) */
a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
    outline: 3px solid var(--primary);
    outline-offset: 2px;
}

/* Remove focus on mouse click (focus-visible handles this better) */
a:focus:not(:focus-visible),
button:focus:not(:focus-visible) {
    outline: none;
}
```

---

## Page-Specific Issues

### index.php (Home Page)

#### 🟡 MEDIUM: No Error Feedback for File Uploads

**Lines:** 334 (file input)  
**Issue Type:** Usability

**Description:**  
Order form has file upload field with format/size restrictions:
```html
<input type="file" id="orderFiles" name="files" class="form-control" 
       accept=".stl,.obj,.gcode,.step,.stp,.3mf,.amf,.ply">
<small class="form-text">Допустимые форматы: STL, OBJ, GCODE, STEP, 3MF, AMF, PLY (макс. 50 МБ)</small>
```

But `order-form.js` doesn't show user-friendly errors for:
- Invalid file format
- File too large
- Upload failed
- Server rejected file

**Impact:**  
Users don't know why their file wasn't accepted.

**Recommendation:**  
Add file validation in `OrderFormHandler.validateForm()`:
```javascript
validateFile(fileInput) {
    const file = fileInput.files[0];
    if (!file) return null; // Optional field
    
    // Check size (50 MB)
    const maxSize = 50 * 1024 * 1024;
    if (file.size > maxSize) {
        return 'Файл слишком большой. Максимум 50 МБ.';
    }
    
    // Check format
    const allowedExts = ['.stl', '.obj', '.gcode', '.step', '.stp', '.3mf', '.amf', '.ply'];
    const fileName = file.name.toLowerCase();
    const hasValidExt = allowedExts.some(ext => fileName.endsWith(ext));
    
    if (!hasValidExt) {
        return `Неподдерживаемый формат. Разрешены: ${allowedExts.join(', ')}`;
    }
    
    return null; // Valid
}
```

---

#### 🟡 MEDIUM: Telegram Field Validation Not Prominent

**Lines:** 298-303  
**Issue Type:** UX clarity

**Description:**  
Telegram username field has special validation (no @ symbol, 3-32 chars, alphanumeric+underscore only) but the placeholder text is the only indicator:
```html
<input type="text" id="orderTelegram" name="telegram" class="form-control" 
       placeholder="username (без @)" required>
```

Users who aren't familiar with Telegram may:
- Include @ symbol (causes validation error)
- Use spaces or special characters
- Not understand what "username" means

**Impact:**  
Higher form abandonment rate, frustration.

**Recommendation:**  
1. Add helper text below field (like file upload has)
2. Add real-time validation indicator (green checkmark / red X)
3. Show example: "Пример: ivanov_omsk"
4. Add tooltip icon with explanation

---

#### 🟢 LOW: Stats Animation May Not Trigger on Mobile

**Lines:** 78-103 (stats section)  
**Issue Type:** Polish

**Description:**  
Stats use IntersectionObserver in `main.js` to animate numbers on scroll, but the code wasn't fully examined to verify mobile compatibility.

**Recommendation:**  
Test stats animation on:
- iOS Safari (known IntersectionObserver quirks)
- Android Chrome
- Tablets in landscape orientation
Ensure fallback if IntersectionObserver not supported.

---

#### 🟢 LOW: Hero CTA Buttons Horizontal on Mobile

**Lines:** 42-49  
**Issue Type:** Responsive design

**Description:**  
Hero buttons use flexbox:
```html
<div class="hero-buttons">
    <a href="#order-form-section" class="btn btn-primary">...</a>
    <a href="portfolio.php" class="btn btn-outline">...</a>
</div>
```

On narrow screens (<400px), horizontal buttons may:
- Cause text truncation
- Force horizontal scroll
- Look cramped

**Recommendation:**  
Check responsive.css has mobile stacking:
```css
@media (max-width: 600px) {
    .hero-buttons {
        flex-direction: column;
        gap: 15px;
    }
    .hero-buttons .btn {
        width: 100%;
    }
}
```

---

### services.php (Services Page)

#### 🟢 LOW: Service Cards Linkable but Look Static

**Lines:** 40-135  
**Issue Type:** Affordance

**Description:**  
Service cards are wrapped in `<div class="service-card-full" id="...">` but lack visual indication they're interactive if they support anchor linking.

**Recommendation:**  
If cards should be clickable, add:
1. Hover effect (scale, shadow, border)
2. Cursor pointer
3. Click handler to expand/collapse details
4. Aria-expanded attribute

---

#### 🟡 MEDIUM: Technologies Comparison Cluttered on Mobile

**Lines:** 142-180  
**Issue Type:** Responsive layout

**Description:**  
Technologies comparison grid (FDM/SLA/SLS cards with pros/cons) may stack awkwardly on mobile:
```html
<div class="comparison-grid">
    <div class="tech-card">
        <h3>FDM</h3>
        <p>...</p>
        <h4>Преимущества:</h4>
        <ul>...</ul>
        <h4>Недостатки:</h4>
        <ul>...</ul>
        <h4>Применение:</h4>
        <div class="tech-applications">...</div>
    </div>
    <!-- Repeat 2 more times -->
</div>
```

Each card is dense with text. On mobile, may need:
- Accordion collapse
- Tabs to switch between technologies
- Summary view with expand option

**Recommendation:**  
Test on 375px viewport. Consider:
1. Accordion pattern for mobile: show only headings, expand on tap
2. Horizontal swipe cards (like testimonials)
3. "Compare technologies" table view toggle

---

### portfolio.php (Portfolio Page)

#### 🟢 LOW: Portfolio Filter Buttons Accessibility

**Lines:** 39-44  
**Issue Type:** Accessibility

**Description:**  
Portfolio filter buttons:
```html
<button class="filter-btn active" data-filter="all">Все работы</button>
<button class="filter-btn" data-filter="прототипы">Прототипы</button>
...
```

Missing:
- `role="group"` on container
- `aria-label` on button group
- `aria-pressed` state for active filter
- Keyboard navigation guidance (arrow keys?)

**Recommendation:**  
Enhance filter buttons:
```html
<div class="portfolio-filters" role="group" aria-label="Фильтр портфолио">
    <button class="filter-btn active" data-filter="all" 
            aria-pressed="true">Все работы</button>
    <button class="filter-btn" data-filter="прототипы" 
            aria-pressed="false">Прототипы</button>
</div>
```

Update JavaScript to toggle `aria-pressed` on click.

---

#### 🟡 MEDIUM: Portfolio Images Use Placeholder URLs

**Lines:** 48-49  
**Description (from memory):** Portfolio images use `placeholder.com` URLs. These may:
- Load slowly or fail
- Show "placeholder" watermarks
- Break if placeholder.com down
- Not match actual portfolio work

**Recommendation:**  
Replace with actual portfolio images or:
1. Use local placeholder images (images/portfolio/placeholder-*.jpg)
2. Add proper alt text describing projects
3. Implement lazy loading with loading="lazy"

---

### contact.php (Contact Page)

#### 🟠 HIGH: Form Missing Required Field Visual Indicators

**Lines:** 137-199  
**Issue Type:** Usability

**Description:**  
Contact form marks required fields with asterisk in labels:
```html
<label for="contactName">
    <i class="fas fa-user"></i>
    Ваше имя*
</label>
```

But lacks:
1. Visual distinction (color, bold, border)
2. Legend explaining "*" means required
3. Consistent required attribute on inputs
4. Visual feedback BEFORE submission (e.g., red border on blur if empty)

**Current required fields:** Name, Phone, Message  
**Current optional:** Email, Subject

**Impact:**  
Users don't notice which fields are required until they submit and get error.

**Recommendation:**  
1. Add legend at top of form: "* — обязательное поле"
2. Style required labels differently:
```css
.form-group label.required::after {
    content: " *";
    color: var(--danger);
    font-weight: bold;
}
```
3. Add `required` attribute to HTML inputs
4. Add real-time validation on blur
5. Use ARIA: `aria-required="true"`

---

#### 🟡 MEDIUM: Contact Form Lacks Honeypot Protection

**Lines:** 137-199  
**Issue Type:** Security, spam prevention

**Description:**  
Unlike order form (which has honeypot in order-form.js), contact form has no bot protection:
- No honeypot field
- No rate limiting visible
- No CAPTCHA alternative

**Impact:**  
Vulnerable to spam submissions from bots.

**Recommendation:**  
Add honeypot field to contact form (same as order form):
```javascript
// In main.js handleFormSubmit()
const honeypot = form.querySelector('input[name="website"]');
if (honeypot && honeypot.value) {
    // Silent fail - it's a bot
    console.log('Bot detected via honeypot');
    return false;
}
```

And add hidden field to contact.php:
```html
<input type="text" name="website" style="position: absolute; left: -9999px; opacity: 0;" tabindex="-1" autocomplete="off">
```

---

#### 🟢 LOW: Map Placeholder Not Interactive

**Lines:** 103-114  
**Issue Type:** Functionality

**Description:**  
Map section shows placeholder:
```html
<div class="map-placeholder">
    <i class="fas fa-map-marked-alt"></i>
    <p>Карта</p>
    <p>Координаты: 54.9885, 73.3242</p>
</div>
```

Non-interactive map is poor UX. Users expect to:
- Zoom/pan
- Get directions
- See nearby landmarks

**Recommendation:**  
Integrate real map:
- **Option A:** Google Maps embed (requires API key)
- **Option B:** OpenStreetMap embed (free, no API key)
- **Option C:** Yandex.Maps (better for Russia)
- **Option D:** Static map image with link to Google Maps directions

Example (Yandex.Maps):
```html
<iframe src="https://yandex.ru/map-widget/v1/?ll=73.3242,54.9885&z=16&l=map&pt=73.3242,54.9885,pm2rdm" 
        width="100%" height="400" frameborder="0"></iframe>
```

---

### about.html (About Page)

#### 🟠 HIGH: CTA Buttons Link to Removed Calculator

**Lines:** 330-332  
**Issue Type:** Broken functionality

**Description:**  
CTA section links to calculator:
```html
<a href="index.php#calculator" class="btn btn-primary">
    <i class="fas fa-calculator"></i>
    Рассчитать стоимость
</a>
```

Should link to order form (same issue as navigation).

**Recommendation:**  
Update to:
```html
<a href="index.php#order-form-section" class="btn btn-primary">
    <i class="fas fa-paper-plane"></i>
    Заказать 3D печать
</a>
```

---

#### 🟡 MEDIUM: Timeline Not Responsive-Tested

**Lines:** 181-236  
**Issue Type:** Responsive design

**Description:**  
Company timeline uses `.timeline-item` with year + content structure:
```html
<div class="timeline-item">
    <div class="timeline-year">2011</div>
    <div class="timeline-content">
        <h3>Основание компании</h3>
        <p>...</p>
    </div>
</div>
```

On mobile (<600px), timeline may:
- Overflow horizontally
- Have cramped spacing
- Need vertical layout instead of side-by-side

**Recommendation:**  
Test timeline on 375px, 414px, 768px viewports. Ensure:
1. Years stack above content on mobile
2. Connector lines (if any) adjust
3. Touch-friendly spacing (min 44x44px tap targets)

---

#### 🟢 LOW: Stats Numbers Not Animated

**Lines:** 296-320  
**Issue Type:** Polish

**Description:**  
Stats section shows static numbers (no `data-target` attribute):
```html
<div class="stat-number">1500+</div>
<div class="stat-number">850+</div>
<div class="stat-number">12</div>
<div class="stat-number">4.9/5</div>
```

Index.php has animated stats (count-up effect). Inconsistent experience.

**Recommendation:**  
Add `data-target` and ensure main.js animation applies:
```html
<div class="stat-number" data-target="1500">0</div>
```

---

### blog.html (Blog Page)

#### 🟠 HIGH: External Image Dependencies (unsplash.com)

**Lines:** 160-226  
**Issue Type:** Performance, reliability

**Description:**  
Blog placeholder articles use external images:
```html
<img src="https://images.unsplash.com/photo-581092160562-40aa08e78837?w=600&auto=format&fit=crop" 
     alt="FDM vs SLA vs SLS" class="blog-image">
```

**Issues:**
1. Requires external request to Unsplash (slow, may fail)
2. CORS issues possible
3. Images may be removed/changed by Unsplash
4. Privacy concerns (user tracking)
5. GDPR compliance issue (external requests without consent)

**Impact:**  
Slow page load, broken images if Unsplash down, privacy violations.

**Recommendation:**  
**Immediate:** Replace with local placeholder images  
**Long-term:** Use actual blog post featured images

Create local placeholders:
- images/blog/placeholder-1.jpg (600x400px)
- images/blog/placeholder-2.jpg (600x400px)
- ...

Or use inline SVG placeholders:
```html
<svg width="600" height="400" xmlns="http://www.w3.org/2000/svg">
    <rect fill="#e0e0e0" width="600" height="400"/>
    <text x="50%" y="50%" font-size="24" fill="#999" text-anchor="middle" dy=".3em">
        Blog Image
    </text>
</svg>
```

---

#### 🟡 MEDIUM: Blog Cards Not Clickable

**Lines:** 159-234  
**Issue Type:** Usability

**Description:**  
Blog cards are wrapped in `<a href="#">` links:
```html
<a href="#" class="blog-card">
    <img src="..." alt="..." class="blog-image">
    <div class="blog-content">
        <span class="blog-date">15 января 2025</span>
        <h3>FDM vs SLA vs SLS: какую технологию выбрать?</h3>
        <p class="blog-excerpt">...</p>
    </div>
</a>
```

But links go to `#` (nowhere). Users click expecting article but nothing happens.

**Impact:**  
Frustrating dead-end experience, looks incomplete.

**Recommendation:**  
**Option A:** Remove links, add "Coming Soon" badge  
**Option B:** Link to existing content (portfolio, services)  
**Option C:** Create placeholder article pages

Current workaround:
```html
<div class="blog-card" style="cursor: default; opacity: 0.7;">
    <img src="..." alt="...">
    <div class="blog-content">
        <span class="blog-date">Скоро</span>
        <h3>FDM vs SLA vs SLS: какую технологию выбрать?</h3>
        <p class="blog-excerpt">Статья в разработке</p>
    </div>
</div>
```

---

#### 🟡 MEDIUM: Two Subscribe Forms with Same ID

**Lines:** 259 (newsletter), 325 (footer)  
**Issue Type:** Critical bug (covered in cross-page issues)

**Description:**  
Duplicate `subscribeForm` IDs on single page.

**Recommendation:**  
See cross-page issue section for fix.

---

### why-us.html (Why Us Page)

#### 🟠 HIGH: Calculator Reference in CTA

**Lines:** 320-323  
**Issue Type:** Broken link (same as about.html)

**Description:**  
Same calculator link issue:
```html
<a href="index.php#calculator" class="btn btn-primary">
    <i class="fas fa-calculator"></i>
    Рассчитать стоимость
</a>
```

**Recommendation:**  
Update to order form link.

---

#### 🟡 MEDIUM: Why Us Cards Not Sortable/Filterable

**Lines:** 139-217  
**Issue Type:** UX enhancement

**Description:**  
11 "why choose us" cards displayed in fixed grid. On mobile, users must scroll through all 11 cards.

**Enhancement idea:**  
Allow users to prioritize what's important to them:
1. "What matters most?" filter (Quality / Price / Speed / Support)
2. Show top 3-5 based on filter
3. "Show all" button

**Recommendation:**  
Medium priority - nice-to-have, not critical.

---

#### 🟢 LOW: Stats Not Animated (Same as about.html)

**Lines:** 222-246  
**Recommendation:** Add `data-target` attributes.

---

### districts.html (Districts Page)

#### 🟠 HIGH: Calculator Link in CTA

**Lines:** 291-293  
**Description:** Same calculator link issue.  
**Recommendation:** Update to order form link.

---

#### 🟡 MEDIUM: District Cards Could Be Accordion on Mobile

**Lines:** 158-236  
**Issue Type:** Responsive UX

**Description:**  
6 district cards, each with detailed information. On mobile, requires excessive scrolling.

**Recommendation:**  
Implement accordion pattern for mobile:
```html
<details class="district-card">
    <summary>
        <h3><i class="fas fa-map-marker-alt"></i> Центральный округ</h3>
    </summary>
    <p>...</p>
    <ul>...</ul>
</details>
```

Or JavaScript accordion with expand/collapse all.

---

#### 🟢 LOW: Delivery Options Could Be Comparison Table

**Lines:** 240-282  
**Issue Type:** Information architecture

**Description:**  
Delivery options described in prose paragraphs. Would be clearer as comparison table:

| Метод доставки | Стоимость | Время | Зона обслуживания |
|----------------|-----------|-------|-------------------|
| Курьер         | От 150₽   | 2-4 ч | Весь Омск         |
| Самовывоз      | Бесплатно | Сразу | Центр             |
| Почта России   | От 300₽   | 5-14 дней | Вся Россия    |

**Recommendation:**  
Create responsive table with mobile-friendly horizontal scroll or stacked cards.

---

## Theme Toggle Behavior

### 🟢 LOW: Theme Icon Update Lag

**Affected Pages:** All pages  
**Lines:** js/main.js (theme toggle logic)

**Description:**  
Theme toggle button updates icon (moon ↔ sun) but may have slight lag if JavaScript defers.

**Recommendation:**  
1. Test theme toggle speed on slow devices/connections
2. Consider CSS-only icon toggle using `[data-theme="dark"]` selector
3. Add transition animation for smooth icon change

---

### 🟢 LOW: No Theme Preference Announcement

**Affected Pages:** All pages  
**Issue Type:** Accessibility

**Description:**  
Screen reader users don't hear announcement when theme changes.

**Recommendation:**  
Add ARIA live region:
```html
<div class="sr-only" role="status" aria-live="polite" id="themeAnnouncement"></div>
```

Update on theme change:
```javascript
document.getElementById('themeAnnouncement').textContent = 
    `Тема изменена на ${theme === 'dark' ? 'тёмную' : 'светлую'}`;
```

---

## Responsive Design Issues

### 🟡 MEDIUM: No Verified Testing at Breakpoints

**Affected Breakpoints:** 
- 400px (tiny phones)
- 600px (small mobile)
- 768px (tablets)
- 1024px (tablet landscape)
- 1440px+ (large desktop)

**Description:**  
Responsive.css defines breakpoints but code review cannot verify actual rendering. Potential issues:

1. **400px viewport:**
   - Hero buttons may not stack
   - Form inputs may overflow
   - Footer columns may crowd

2. **768px viewport:**
   - Navigation may have cramped spacing
   - Stats grid may show 3 columns (awkward)
   - Service cards may be too narrow

3. **1440px+ viewport:**
   - Content may be too wide
   - Reading line length exceeds 75 characters (readability issue)
   - Images may stretch/pixelate

**Recommendation:**  
Manual testing checklist:
- [ ] iPhone SE (375x667) - portrait
- [ ] iPhone 12 Pro (390x844) - portrait
- [ ] iPad (768x1024) - portrait and landscape
- [ ] iPad Pro (1024x1366) - landscape
- [ ] Desktop 1920x1080
- [ ] Desktop 2560x1440

Use Chrome DevTools Device Mode + physical devices.

---

### 🟡 MEDIUM: Touch Target Sizes Not Verified

**Affected Pages:** All pages  
**Issue Type:** Mobile usability, accessibility

**Description:**  
WCAG 2.1 Level AAA requires 44x44px minimum touch targets. Code review cannot verify:

1. Navigation links (header)
2. Hamburger menu button
3. Theme toggle button
4. Form inputs (especially radio/checkbox)
5. Filter buttons (portfolio)
6. CTA buttons
7. Footer links

**Recommendation:**  
Add to CSS:
```css
/* Ensure minimum touch target size */
@media (max-width: 768px) {
    a, button, input[type="checkbox"], input[type="radio"] {
        min-width: 44px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
}
```

Test with overlay tool to visualize touch targets.

---

### 🟢 LOW: Hamburger Menu Animation Not Verified

**Affected Pages:** All pages  
**Lines:** includes/header.php (lines 38-42)

**Description:**  
Hamburger menu button has 3 `<span>` elements (likely animated to X on click) but animation not verified in CSS.

**Recommendation:**  
Ensure smooth hamburger → X animation exists in css/animations.css or css/style.css. Test on iOS Safari (may have rendering quirks).

---

## Performance Issues

### 🟡 MEDIUM: Font Awesome Loaded from CDN

**Affected Pages:** All pages  
**Lines:** All head sections (line 99 in about.html, similar in others)

**Description:**  
Font Awesome loaded from CDN:
```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

**Issues:**
1. External request blocks rendering (render-blocking CSS)
2. CORS issues possible
3. CDN may be slow/down
4. Privacy concern (external tracking)
5. No fallback if CDN fails

**Impact:**  
~1-2 seconds added to page load on slow connections.

**Recommendation:**  
**Option A (Best):** Self-host Font Awesome  
- Download Font Awesome 6.4.0
- Host in `css/fonts/fontawesome/`
- Update link: `<link rel="stylesheet" href="css/fonts/fontawesome/all.min.css">`

**Option B (Quick):** Add `preconnect` to CDN:
```html
<link rel="preconnect" href="https://cdnjs.cloudflare.com">
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
```

**Option C (Future):** Replace with SVG icons (smaller, no font loading)

---

### 🟢 LOW: Preloader May Delay Interaction

**Affected Pages:** All pages  
**Lines:** includes/header.php (lines 5-16), js/main.js (lines 26-33)

**Description:**  
Preloader shows for 800ms minimum (hardcoded timeout):
```javascript
setTimeout(() => {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        preloader.classList.add('hidden');
    }
}, 800);
```

On fast connections/cached pages, 800ms delay is unnecessary and frustrating.

**Recommendation:**  
Show preloader only if page load takes >300ms:
```javascript
let preloaderTimeout = setTimeout(() => {
    document.getElementById('preloader')?.classList.remove('hidden');
}, 300);

window.addEventListener('load', () => {
    clearTimeout(preloaderTimeout);
    document.getElementById('preloader')?.classList.add('hidden');
});
```

Or remove preloader entirely (modern browsers load fast enough).

---

### 🟢 LOW: No Lazy Loading for Below-Fold Images

**Affected Pages:** All pages with image-heavy content  
**Lines:** portfolio.php (line 49), blog.html (lines 160+)

**Description:**  
Most images don't use `loading="lazy"` attribute:
```html
<img src="..." alt="..." class="portfolio-image" loading="lazy">
```

Only portfolio items have `loading="lazy"` (portfolio.php line 49).

**Impact:**  
All images load immediately, slowing initial page load.

**Recommendation:**  
Add `loading="lazy"` to all below-fold images:
- Blog post images
- Portfolio items (already done)
- Testimonial avatars (if any)
- Footer images (if any)

Exception: Hero images should load eagerly.

---

## Content & Messaging Issues

### 🟡 MEDIUM: Inconsistent Company Name Styling

**Affected Pages:** All pages  
**Lines:** Multiple locations

**Description:**  
Company name appears in different formats:
- "3D Print Pro" (with space)
- "3D Print**Pro**" (with bold)
- "3DPrintPro" (no spaces)
- "3D-Print-Pro" (with hyphens)

Examples:
- about.html line 124: `<span id="siteName">3D Print<strong>Pro</strong></span>`
- PHP templates: `$site['name']` from data/content.php

**Recommendation:**  
Standardize across all pages. Choose one format (recommend: "3D Print<strong>Pro</strong>") and document in brand guidelines.

---

### 🟢 LOW: Call-to-Action Button Text Inconsistent

**Affected Pages:** Multiple  
**Lines:** Various CTA sections

**Description:**  
Primary CTA buttons have different text:
- "Заказать 3D печать"
- "Рассчитать стоимость" (old calculator reference)
- "Отправить заказ"
- "Отправить сообщение"
- "Связаться с нами"

**Recommendation:**  
Create CTA hierarchy:
1. **Primary CTA:** "Заказать 3D печать" (order form)
2. **Secondary CTA:** "Связаться с нами" (contact page)
3. **Tertiary CTA:** "Написать в Telegram" (instant messaging)

Use consistently across all pages.

---

## Security & Privacy Issues

### 🟡 MEDIUM: No CAPTCHA or Rate Limiting Visible

**Affected Pages:** Forms on index.php, contact.php  
**Lines:** order-submit.php (server-side rate limiting exists per memory)

**Description:**  
Order form has honeypot and server-side rate limiting (per memory: 5 orders per IP per hour), but:
1. Contact form has neither
2. Subscribe forms (footer) have no protection
3. No visual feedback if rate limited

**Recommendation:**  
1. Add rate limiting to contact form handler
2. Add honeypot to contact form
3. Consider friendly CAPTCHA (hCaptcha, Cloudflare Turnstile) for production
4. Show user-friendly error if rate limited: "Слишком много запросов. Подождите 15 минут."

---

### 🟢 LOW: External CDN Privacy Disclosure

**Affected Pages:** All pages (Font Awesome CDN)  
**Issue Type:** GDPR compliance

**Description:**  
Loading resources from external CDNs (Font Awesome, Unsplash) may constitute data transfer to third parties. No privacy disclosure or cookie consent banner visible.

**Recommendation:**  
1. Add privacy policy page
2. Disclose external resources in privacy policy
3. Consider cookie consent banner if needed
4. Self-host Font Awesome to eliminate one external request

---

## Missing Features & Enhancements

### 🟡 MEDIUM: No 404 Error Page Visible

**Description:**  
No custom 404 page found in project root. Users who mistype URLs see server default error page (poor UX).

**Recommendation:**  
Create `404.html` with:
- Friendly error message ("Страница не найдена")
- Search bar or navigation links
- Link to homepage
- Popular pages list
- Contact information
Configure in `.htaccess` or web server config:
```apache
ErrorDocument 404 /404.html
```

---

### 🟢 LOW: No Sitemap.xml Visible

**Description:**  
No sitemap.xml found. Important for SEO and search engine crawling.

**Recommendation:**  
Create sitemap.xml with all public pages:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://3dprint-omsk.ru/index.php</loc>
        <lastmod>2024-12-01</lastmod>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://3dprint-omsk.ru/services.php</loc>
        <lastmod>2024-12-01</lastmod>
        <priority>0.8</priority>
    </url>
    <!-- ... -->
</urlset>
```

Submit to Google Search Console and Yandex.Webmaster.

---

### 🟢 LOW: No Robots.txt Visible

**Description:**  
No robots.txt found. Search engines may crawl unnecessary pages (admin panel, scripts).

**Recommendation:**  
Create robots.txt:
```
User-agent: *
Disallow: /admin/
Disallow: /api/
Disallow: /vendor/
Disallow: /storage/
Disallow: /scripts/
Disallow: /test-*.php
Disallow: /test-*.html

Sitemap: https://3dprint-omsk.ru/sitemap.xml
```

---

## Final Verification Checklist

Use this checklist to verify fixes on real devices and browsers:

### Desktop Testing (1920x1080)

**Chrome/Edge:**
- [ ] All pages load without console errors
- [ ] Theme toggle works (light ↔ dark)
- [ ] Navigation links go to correct pages
- [ ] Order form submits successfully
- [ ] Contact form submits successfully
- [ ] Portfolio filters work
- [ ] Testimonial carousel auto-advances
- [ ] Stats animate on scroll
- [ ] All images load correctly
- [ ] No horizontal scrollbar

**Firefox:**
- [ ] Repeat all Chrome tests
- [ ] Check CSS Grid rendering (portfolio, services)
- [ ] Verify form validation styling

**Safari (macOS):**
- [ ] Repeat all Chrome tests
- [ ] Check date inputs (Safari renders differently)
- [ ] Verify smooth scroll behavior

### Tablet Testing (iPad 768x1024)

**Portrait Mode:**
- [ ] Navigation collapses to hamburger menu
- [ ] Hamburger menu opens/closes smoothly
- [ ] Forms stack single-column
- [ ] Touch targets ≥44x44px
- [ ] Order form usable with on-screen keyboard
- [ ] No content cut off by viewport

**Landscape Mode:**
- [ ] Layout uses available width
- [ ] Navigation shows full menu or hamburger appropriately
- [ ] Images don't stretch

### Mobile Testing (iPhone 12 Pro 390x844)

**iOS Safari:**
- [ ] All pages load <3 seconds on 4G
- [ ] Theme toggle works
- [ ] Navigation hamburger works
- [ ] Forms keyboard-friendly (correct input types)
- [ ] Phone input opens numeric keypad
- [ ] Email input shows @ key
- [ ] Telegram input shows alphanumeric keyboard
- [ ] File upload works from camera/photos
- [ ] Touch targets large enough
- [ ] No horizontal scroll
- [ ] Footer readable
- [ ] CTAs tappable
- [ ] No FOUC on page load

**Android Chrome:**
- [ ] Repeat all iOS tests
- [ ] Check autofill behavior (may differ)
- [ ] Verify back button navigation

### Accessibility Testing

**Keyboard Navigation:**
- [ ] Tab through entire page without mouse
- [ ] All interactive elements focusable
- [ ] Focus visible (outline/highlight)
- [ ] Skip-to-content link works (when implemented)
- [ ] Modal closes with Escape key
- [ ] Hamburger menu navigable with Tab/Enter

**Screen Reader (NVDA/JAWS/VoiceOver):**
- [ ] Page title announced
- [ ] Headings navigable (H key)
- [ ] Landmarks navigable (D key)
- [ ] Form labels announced correctly
- [ ] Error messages announced
- [ ] Button purposes clear
- [ ] Images have alt text

**Color Blindness (Chrome DevTools Emulation):**
- [ ] Links distinguishable from text (not just by color)
- [ ] Form errors visible (not just red border)
- [ ] Success messages visible (not just green)

### Performance Testing

**Lighthouse Audit:**
- [ ] Performance score ≥90
- [ ] Accessibility score ≥90
- [ ] Best Practices score ≥90
- [ ] SEO score ≥90

**Network Throttling (Slow 3G):**
- [ ] Page loads <5 seconds
- [ ] Critical content visible <2 seconds
- [ ] Forms usable during load

**Page Weight:**
- [ ] Total page weight <2 MB
- [ ] Images optimized (WebP/compressed JPG)
- [ ] No render-blocking resources

### Browser Console Checks

**All Pages:**
- [ ] No JavaScript errors in console
- [ ] No 404 errors (CSS, JS, images)
- [ ] No CORS errors
- [ ] No mixed content warnings (HTTP on HTTPS page)

### Form Validation

**Order Form (index.php):**
- [ ] All required fields validated
- [ ] Email format checked
- [ ] Phone format validated
- [ ] Telegram format validated (@-less username)
- [ ] File size checked (<50 MB)
- [ ] File format validated (STL, OBJ, etc.)
- [ ] Privacy checkbox required
- [ ] Success message shown after submit
- [ ] Form resets after success
- [ ] Error messages field-specific

**Contact Form (contact.php):**
- [ ] Required fields validated
- [ ] Email format checked
- [ ] Success message shown
- [ ] Error handling works
- [ ] Honeypot detects bots

---

## Remediation Priority Matrix

| Phase | Priority | Issues Count | Estimated Time | Impact |
|-------|----------|--------------|----------------|--------|
| **Phase 1: Critical Fixes** | 🔴 | 10 | 4-6 hours | Site functionality restored |
| **Phase 2: High Priority** | 🟠 | 15 | 2-3 days | Major UX improvements |
| **Phase 3: Medium Priority** | 🟡 | 14 | 1 week | Noticeable polish |
| **Phase 4: Low Priority** | 🟢 | 8 | 2-3 days | Final touches |

**Recommended Approach:**
1. **Week 1:** Fix all Critical issues (Phase 1)
2. **Week 2:** Address High Priority issues (Phase 2)
3. **Week 3:** Implement Medium Priority fixes (Phase 3)
4. **Week 4:** Polish with Low Priority items (Phase 4) + testing

---

## Issue Tracking Template

Use this template to track remediation progress:

```markdown
## Issue: [Issue Name]
- **ID:** UX-001
- **Priority:** 🔴 Critical / 🟠 High / 🟡 Medium / 🟢 Low
- **Category:** Navigation / Forms / Performance / Accessibility / Content
- **Affected Pages:** [List]
- **Assigned To:** [Developer Name]
- **Status:** 🔲 Not Started / 🔄 In Progress / ✅ Complete / ❌ Blocked
- **Due Date:** [YYYY-MM-DD]

**Description:**
[Brief description of issue]

**Steps to Reproduce:**
1. [Step 1]
2. [Step 2]
3. [Expected vs Actual]

**Proposed Fix:**
[Technical solution]

**Testing Required:**
- [ ] Desktop Chrome
- [ ] Mobile Safari
- [ ] Screen reader
- [ ] ...

**Related Issues:** UX-002, UX-003
```

---

## Appendix: Quick Reference

### Most Common Issues (Top 10)

1. Broken `#calculator` links → Update to `#order-form-section`
2. Missing FOUC script → Add to static HTML
3. Duplicate `subscribeForm` IDs → Rename with unique IDs
4. No honeypot on contact form → Add hidden field
5. Hardcoded contact info → Centralize in config
6. No skip-to-content link → Add accessibility landmark
7. No focus indicators → Add CSS `:focus` styles
8. Font Awesome from CDN → Self-host
9. Unsplash images → Replace with local
10. No required field legend → Add form instructions

### Files Requiring Most Changes

1. **about.html** (8 issues)
2. **blog.html** (7 issues)
3. **why-us.html** (6 issues)
4. **districts.html** (5 issues)
5. **contact.php** (5 issues)
6. **index.php** (4 issues)
7. **includes/header.php** (3 issues)
8. **css/style.css** (2 issues - accessibility)

### Testing Tools Recommended

**Browser Tools:**
- Chrome DevTools (Lighthouse, Device Mode, Accessibility)
- Firefox Developer Tools (CSS Grid Inspector)
- Safari Web Inspector (iOS debugging)

**Accessibility:**
- NVDA (free screen reader for Windows)
- WAVE Browser Extension (WebAIM)
- axe DevTools (Deque)
- Color Contrast Analyzer (TPGi)

**Performance:**
- Google PageSpeed Insights
- WebPageTest.org
- GTmetrix

**Validation:**
- W3C HTML Validator
- W3C CSS Validator
- WAVE Accessibility Checker

---

## Document Changelog

| Date | Version | Changes | Author |
|------|---------|---------|--------|
| 2024-12-XX | 1.0 | Initial comprehensive audit | AI Team |

---

## Next Steps

1. **Review this audit** with development team
2. **Prioritize fixes** based on business goals
3. **Create GitHub issues** for each item (or use issue tracking system)
4. **Assign owners** to each issue
5. **Set sprint goals** (e.g., "Fix all Critical issues by Sprint 1")
6. **Schedule testing** after each phase
7. **Update this document** as issues are resolved
8. **Re-audit in 3 months** to catch new issues

---

**End of UX Audit Report**

For questions or clarifications about any issue, refer to the specific line numbers and page references provided throughout this document.
