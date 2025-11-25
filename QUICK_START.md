# Quick Start Guide - Static PHP Templates

## What Changed?

✅ **HTML → PHP Templates** - Shared components (header, footer, meta tags)  
✅ **Centralized Content** - All text in `data/content.php` (single source of truth)  
✅ **Lightweight JavaScript** - No API calls, UI interactions only  
✅ **No Backend Required** - Works without database or API

---

## File Structure

```
project/
├── index.php              ← Homepage
├── services.php           ← Services page
├── portfolio.php          ← Portfolio page
├── contact.php            ← Contact page
├── data/
│   └── content.php        ← ALL CONTENT HERE ⭐
├── includes/
│   ├── head.php           ← Meta tags, SEO
│   ├── header.php         ← Navigation
│   └── footer.php         ← Footer, scripts
├── js/
│   ├── main.js            ← UI only (rewritten)
│   └── calculator.js      ← Uses embedded config
├── sitemap.xml            ← Updated for .php
└── robots.txt             ← Updated for .php
```

---

## How to Update Content

### 1. Edit Contact Information
Open `data/content.php`, find `'contact' =>`:
```php
'contact' => [
    'phone' => '+7 (999) 123-45-67',  // ← Change here
    'email' => 'info@3dprint-omsk.ru', // ← Change here
    // Updates everywhere automatically
]
```

### 2. Add a New Service
Open `data/content.php`, add to `'services'` array:
```php
[
    'id' => 'new-service',
    'name' => 'New Service',
    'slug' => 'new-service-slug',
    'icon' => 'fas fa-cog',
    'short_description' => 'Brief description',
    'description' => 'Full description',
    'features' => ['Feature 1', 'Feature 2'],
    'materials' => ['Material 1', 'Material 2'],
    'applications' => ['Use case 1', 'Use case 2'],
    'price_from' => 500,
    'price_unit' => '₽/hour',
    'delivery_time' => 'from 1 day',
]
```

### 3. Update Calculator Prices
Open `data/content.php`, find `'calculator' =>`:
```php
'materials' => [
    'pla' => [
        'name' => 'PLA (полилактид)',
        'price' => 3.5,  // ← Change price here (₽/g)
        'technology' => 'fdm',
    ],
    // ... other materials
]
```

### 4. Change Site Name
Open `data/content.php`, find `'site' =>`:
```php
'site' => [
    'name' => '3D Print Pro',  // ← Change here
    'tagline' => 'Профессиональная 3D печать в Омске',
    // ...
]
```

---

## How to Deploy

### Option 1: Traditional Hosting (FTP)
```bash
# Upload all files to public_html or www directory
# Make sure PHP 7.4+ is enabled
# Done!
```

### Option 2: VPS/Dedicated Server
```bash
# 1. Install web server + PHP
sudo apt install nginx php-fpm

# 2. Copy files
sudo rsync -avz ./ /var/www/html/

# 3. Configure Nginx (use template in deploy/webserver/)
sudo cp deploy/webserver/nginx.3dprint-omsk.conf /etc/nginx/sites-available/
sudo ln -s /etc/nginx/sites-available/nginx.3dprint-omsk.conf /etc/nginx/sites-enabled/

# 4. Reload
sudo nginx -t && sudo systemctl reload nginx
```

### Option 3: Test Locally
```bash
# PHP built-in server
php -S localhost:8000

# Visit http://localhost:8000
```

---

## Common Tasks

### Change Phone Number
1. Open `data/content.php`
2. Find `'phone' => '+7 (999) 123-45-67'`
3. Change to new number
4. Save and refresh - updates everywhere!

### Add Portfolio Item
1. Open `data/content.php`
2. Add to `'portfolio'` array:
```php
[
    'id' => 7,
    'title' => 'New Project',
    'slug' => 'new-project',
    'category' => 'electronics', // architecture, medical, jewelry, etc.
    'technology' => 'FDM',
    'description' => 'Project description...',
    'image' => '/images/portfolio/new-project.jpg',
    'duration' => '2 days',
    'materials' => ['PLA'],
    'client' => 'Client Name',
    'year' => 2024,
    'tags' => ['tag1', 'tag2'],
]
```

### Update Service Price
1. Open `data/content.php`
2. Find the service in `'services'` array
3. Change `'price_from' => 500` to new price
4. Save and refresh

### Modify FAQ
1. Open `data/content.php`
2. Edit/add to `'faq'` array
3. Save and refresh

---

## Troubleshooting

### PHP pages download instead of rendering?
- **Cause**: PHP not enabled on server
- **Fix**: Enable PHP in hosting panel or install php-fpm

### Calculator not working?
- **Check**: Browser console for JavaScript errors
- **Check**: Page source for `window.CALCULATOR_CONFIG`
- **Check**: `js/calculator.js` is loaded

### Styles not loading?
- **Check**: `/css/` directory exists with CSS files
- **Check**: Browser DevTools Network tab for 404 errors

### Images not showing?
- **Expected**: Placeholder images in templates
- **Solution**: Upload real images to `/images/portfolio/` and `/images/testimonials/`

---

## Features

✅ **No Database** - All content in PHP arrays  
✅ **Fast** - No queries, instant page loads  
✅ **SEO** - Structured data, meta tags, breadcrumbs  
✅ **Responsive** - Works on all devices  
✅ **Progressive** - Works without JavaScript  
✅ **Maintainable** - One file to edit (`data/content.php`)  

---

## Documentation

- **Complete Guide**: `STATIC_TEMPLATES_README.md` (13KB)
- **Implementation Details**: `IMPLEMENTATION_SUMMARY.md` (10KB)
- **This Guide**: `QUICK_START.md` (you are here)

---

## Support

Need help? Check:
1. Browser console for errors
2. Page source to verify PHP is rendering
3. `STATIC_TEMPLATES_README.md` for detailed docs
4. Contact developer if issues persist

---

**That's it!** Edit `data/content.php` to manage all site content. 🚀
