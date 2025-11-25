# 3D Print Pro - Омск

Профессиональный статический веб-сайт для сервиса 3D печати с интерактивным калькулятором и интеграцией с Telegram.

---

## 🚀 Quick Start

This is a **static website** that can be hosted on any web server (Apache, Nginx, etc.) or even deployed to static hosting platforms like GitHub Pages, Netlify, or Vercel.

### For Local Development

1. Open `index.html` in a web browser
2. Or use a simple HTTP server:
   ```bash
   python3 -m http.server 8000
   # OR
   php -S localhost:8000
   ```
3. Navigate to `http://localhost:8000`

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
- ✅ **Static HTML** - No database or backend required, fast and secure
- ✅ **Easy Maintenance** - Simple HTML/CSS/JS files that can be edited directly
- ✅ **Telegram Bot Ready** - Prepared for integration with Telegram notifications
- ✅ **Lightweight** - Fast loading times and minimal hosting requirements
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
├── index.html          # Homepage
├── about.html          # About page
├── services.html       # Services catalog
├── portfolio.html      # Portfolio showcase
├── contact.html        # Contact page
├── blog.html           # Blog page
├── districts.html      # Delivery districts
├── why-us.html         # Why choose us
│
├── css/                # Stylesheets
│   ├── style.css       # Main styles
│   ├── responsive.css  # Mobile responsive styles
│   └── animations.css  # Animation effects
│
├── js/                 # JavaScript files
│   ├── main.js         # Core site functionality
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

All content is directly in the HTML files. To update:

1. **Contact Information**: Edit the contact section in each HTML file
2. **Services**: Edit `services.html` and the services section in `index.html`
3. **Portfolio Items**: Edit `portfolio.html`
4. **Calculator Prices**: Edit the `CONFIG` object in `js/calculator.js`
5. **Telegram Link**: Update the Telegram link in all HTML files (search for `t.me/PrintPro_Omsk`)

### Changing Styles

- **Colors**: Edit CSS variables in `css/style.css`
- **Layout**: Modify the HTML structure in individual pages
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
