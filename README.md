# 3D Print Pro - Омск

Профессиональный статический веб-сайт для сервиса 3D печати с интерактивным калькулятором и интеграцией с Telegram.

---

## 🚀 Quick Start

This is a **static website** that can be hosted on any web server (Apache, Nginx, etc.) or even deployed to static hosting platforms like GitHub Pages, Netlify, or Vercel.

### For Local Development

1. Use a web server with PHP support (PHP 7.4+):
   ```bash
   php -S localhost:8000
   ```
2. Navigate to `http://localhost:8000/index.php`

**Note:** The site uses PHP templates for shared includes and static content data. PHP 7.4+ is required.

### For Production Deployment

1. Upload all files to your web server's public directory
2. Configure your web server (see `deploy/webserver/` for configuration templates)
3. Ensure `.htaccess` or server config is properly configured for clean URLs (if desired)
4. Test the site is accessible via your domain

**Deployment time:** ~5 minutes

---

## ✨ Features

### For Customers
- ✅ **Interactive Price Calculator** - Real-time cost estimation for 3D printing projects
- ✅ **Responsive Design** - Works seamlessly on mobile, tablet, and desktop
- ✅ **Contact Forms** - Easy inquiry submission with validation
- ✅ **Service Catalog** - Detailed descriptions of all 3D printing services
- ✅ **Portfolio Showcase** - Gallery of completed projects
- ✅ **Customer Testimonials** - Reviews and feedback from satisfied clients
- ✅ **FAQ Section** - Answers to common questions
- ✅ **Telegram Integration** - Direct messaging link for instant communication
- ✅ **SEO Optimized** - Structured data, meta tags, and semantic HTML
- ✅ **Multiple Pages** - Services, Portfolio, About, Contact, Blog, Districts

### For Business Owners
- ✅ **Static PHP Templates** - No database required, content in PHP arrays for easy management
- ✅ **Easy Maintenance** - Centralized content in `data/content.php`, shared includes for headers/footers
- ✅ **Telegram Bot Ready** - Prepared for integration with Telegram notifications
- ✅ **Lightweight** - Fast loading times and minimal hosting requirements (PHP 7.4+)
- ✅ **Future-Ready** - Prepared structure for adding backend endpoints if needed

---

## 🏗️ Architecture

### Technology Stack

**Frontend:**
- HTML5, CSS3, Vanilla JavaScript (ES6+)
- Responsive design with mobile-first approach
- Font Awesome icons
- No build tools or dependencies required

**Backend (Optional):**
- Lightweight PHP endpoints for Telegram integration (to be added)
- No database required for core functionality
- Forms can be processed via PHP or third-party services (Formspree, Netlify Forms, etc.)

### Project Structure

```
/
├── index.php           # Homepage (PHP template)
├── services.php        # Services catalog (PHP template)
├── portfolio.php       # Portfolio showcase (PHP template)
├── contact.php         # Contact page (PHP template)
├── about.html          # About page (static)
├── blog.html           # Blog page (static)
├── districts.html      # Delivery districts (static)
├── why-us.html         # Why choose us (static)
│
├── includes/           # PHP template includes
│   ├── head.php        # Head tags, meta, structured data
│   ├── header.php      # Header and navigation
│   └── footer.php      # Footer and scripts
│
├── data/               # Static content data
│   └── content.php     # PHP arrays with all content
│
├── css/                # Stylesheets
│   ├── style.css       # Main styles
│   ├── responsive.css  # Mobile responsive styles
│   └── animations.css  # Animation effects
│
├── js/                 # JavaScript files
│   ├── main.js         # Core UI interactions only
│   ├── calculator.js   # Price calculator logic
│   ├── telegram.js     # Telegram integration
│   ├── utils.js        # Utility functions
│   └── validators.js   # Form validation
│
├── deploy/             # Web server configuration templates
│   └── webserver/
│       ├── nginx.3dprint-omsk.conf      # Nginx config
│       ├── apache.3dprint-omsk.conf     # Apache config
│       └── .htaccess.example            # Shared hosting config
│
├── storage/            # Reserved for future backend storage
├── logs/               # Reserved for future logging
│
├── robots.txt          # Search engine directives
├── sitemap.xml         # XML sitemap
└── README.md           # This file
```

---

## 🎨 Customization

### Updating Content

All content is centralized in `data/content.php`. To update:

1. **Site Information**: Edit the `site` array (name, phone, email, address, etc.)
2. **Services**: Edit the `services` array with service details, prices, features
3. **Portfolio Items**: Edit the `portfolio` array with project details
4. **FAQ Entries**: Edit the `faq` array with questions and answers
5. **Testimonials**: Edit the `testimonials` array with customer reviews
6. **Technologies & Materials**: Edit `technologies` and `materials` arrays
7. **SEO Meta Tags**: Edit the `meta` array for each page

Example from `data/content.php`:

```php
'services' => [
    [
        'id' => 'fdm-printing',
        'name' => 'FDM 3D печать',
        'icon' => 'fa-print',
        'description' => 'Печать методом послойного наплавления...',
        'price' => 'от 150 ₽/час',
        'features' => [
            'Широкий выбор материалов',
            'Размеры печати до 300×300×400 мм',
            // ...
        ],
        'featured' => true
    ],
    // ... more services
]
```

### Changing Styles

- **Colors**: Edit CSS variables in `css/style.css`
- **Layout**: Modify the PHP templates in `*.php` files
- **Shared Components**: Edit `includes/head.php`, `includes/header.php`, `includes/footer.php`
- **Responsive Breakpoints**: Adjust `css/responsive.css`

### Calculator Configuration

The calculator prices and settings are configured in `js/calculator.js`:

```javascript
const CONFIG = {
    materials: {
        pla: { name: 'PLA', price: 150, technology: 'fdm' },
        abs: { name: 'ABS', price: 180, technology: 'fdm' },
        // ... more materials
    },
    services: {
        modeling: { name: '3D моделирование', price: 500, unit: 'час' },
        // ... more services
    },
    // ... other config
};
```

---

## 🌐 Deployment

### Static Hosting Platforms

#### GitHub Pages
```bash
# Push to GitHub repository
git add .
git commit -m "Initial commit"
git push origin main

# Enable GitHub Pages in repository settings
# Choose "main" branch and "/" (root) as source
```

#### Netlify
1. Drag and drop the project folder to Netlify
2. Or connect your Git repository
3. Deploy settings: Build command: (none), Publish directory: /

#### Vercel
```bash
npm install -g vercel
vercel
```

### Traditional Web Hosting

1. **Upload Files**: Use FTP/SFTP to upload all files to your web server's public directory
2. **Configure Web Server**: 
   - For Nginx: Use `deploy/webserver/nginx.3dprint-omsk.conf` as template
   - For Apache: Use `deploy/webserver/apache.3dprint-omsk.conf` as template
   - For Shared Hosting: Copy `deploy/webserver/.htaccess.example` to `.htaccess`
3. **Test**: Visit your domain and verify all pages load correctly

### Web Server Configuration

See the `deploy/webserver/` directory for:
- **Nginx configuration template** with HTTPS, caching, and security headers
- **Apache configuration template** with similar features
- **.htaccess example** for shared hosting environments
- **README** with detailed setup instructions

---

## 📧 Contact Form Integration

The contact form (`contact.html`) is ready for integration with:

### Option 1: PHP Handler (requires PHP on server)
Create a simple PHP endpoint to handle form submissions and send via Telegram or email.

### Option 2: Third-Party Services
- **Formspree**: `<form action="https://formspree.io/f/YOUR_ID" method="POST">`
- **Netlify Forms**: Add `data-netlify="true"` attribute to form
- **Google Forms**: Embed or redirect to Google Form

### Option 3: Telegram Bot
Forms can send messages directly to a Telegram bot (requires backend endpoint).

---

## 🔧 Future Enhancements

This static site is prepared for easy addition of backend features:

- **Telegram Bot Integration**: Add lightweight PHP endpoints for order notifications
- **Form Processing**: Add PHP handlers for contact/order forms
- **Analytics**: Google Analytics, Yandex Metrica (already structured data ready)
- **Dynamic Content**: Add API endpoints for managing content (optional)

---

## 📱 Browser Support

- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

---

## 📄 License

Proprietary - © 2025 3D Print Pro, Омск, Россия

---

## 📞 Support

For questions or support:
- **Email**: info@3dprint-omsk.ru
- **Phone**: +7 (999) 123-45-67
- **Telegram**: [@PrintPro_Omsk](https://t.me/PrintPro_Omsk)

---

## 🎯 Performance

This static site is optimized for performance:
- ⚡ **Fast Loading**: No database queries or backend processing
- 🔒 **Secure**: No server-side vulnerabilities
- 📦 **Small Size**: Minimal JavaScript, optimized assets
- 🌍 **CDN-Ready**: Can be served from CDN for global performance
- 📱 **Mobile-First**: Optimized for mobile devices

---

**Built with ❤️ in Омск, Россия**
