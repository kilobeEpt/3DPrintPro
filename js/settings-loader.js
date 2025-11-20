// Settings Loader for Public Site
// Loads settings from API and caches them in localStorage

class SettingsLoader {
    constructor() {
        this.settings = {
            contact: {},
            social: {},
            seo: {}
        };
        this.cacheKey = '3dprint_settings';
        this.cacheTTL = 300000; // 5 minutes in milliseconds
    }
    
    async init() {
        await this.loadSettings();
        this.applySettings();
    }
    
    async loadSettings() {
        try {
            // Try to load from cache first
            const cached = this.loadFromCache();
            if (cached) {
                this.settings = cached;
                console.log('✅ Settings loaded from cache');
                return;
            }
            
            // Load from API
            await this.loadFromAPI();
            
        } catch (error) {
            console.error('❌ Failed to load settings:', error);
        }
    }
    
    loadFromCache() {
        try {
            const cached = localStorage.getItem(this.cacheKey);
            if (!cached) return null;
            
            const data = JSON.parse(cached);
            const now = Date.now();
            
            // Check if cache is expired
            if (now - data.timestamp > this.cacheTTL) {
                localStorage.removeItem(this.cacheKey);
                return null;
            }
            
            return data.settings;
        } catch (error) {
            console.error('Error loading from cache:', error);
            return null;
        }
    }
    
    saveToCache(settings) {
        try {
            const data = {
                settings: settings,
                timestamp: Date.now()
            };
            localStorage.setItem(this.cacheKey, JSON.stringify(data));
        } catch (error) {
            console.error('Error saving to cache:', error);
        }
    }
    
    async loadFromAPI() {
        try {
            // Load all three groups in parallel
            const [contactData, socialData, seoData] = await Promise.all([
                fetch('/api/settings.php?group=contact').then(r => r.json()),
                fetch('/api/settings.php?group=social').then(r => r.json()),
                fetch('/api/settings.php?group=seo').then(r => r.json())
            ]);
            
            this.settings.contact = contactData.settings || {};
            this.settings.social = socialData.settings || {};
            this.settings.seo = seoData.settings || {};
            
            // Save to cache
            this.saveToCache(this.settings);
            
            console.log('✅ Settings loaded from API');
        } catch (error) {
            console.error('Error loading from API:', error);
            throw error;
        }
    }
    
    applySettings() {
        this.applySEOSettings();
        this.applyContactSettings();
        this.applySocialSettings();
    }
    
    applySEOSettings() {
        const seo = this.settings.seo;
        
        // Update title
        if (seo.seo_title) {
            const titleEl = document.querySelector('title');
            if (titleEl && !titleEl.dataset.customTitle) {
                titleEl.textContent = seo.seo_title;
            }
        }
        
        // Update meta description
        if (seo.seo_description) {
            this.updateMetaTag('name', 'description', seo.seo_description);
        }
        
        // Update meta keywords
        if (seo.seo_keywords) {
            this.updateMetaTag('name', 'keywords', seo.seo_keywords);
        }
        
        // Update Open Graph tags
        if (seo.seo_site_name) {
            this.updateMetaTag('property', 'og:site_name', seo.seo_site_name);
        }
        
        if (seo.seo_og_image) {
            this.updateMetaTag('property', 'og:image', seo.seo_og_image);
        }
        
        if (seo.seo_og_type) {
            this.updateMetaTag('property', 'og:type', seo.seo_og_type);
        }
        
        // Update canonical URL
        if (seo.seo_canonical_url) {
            let canonical = document.querySelector('link[rel="canonical"]');
            if (!canonical) {
                canonical = document.createElement('link');
                canonical.rel = 'canonical';
                document.head.appendChild(canonical);
            }
            canonical.href = seo.seo_canonical_url;
        }
    }
    
    applyContactSettings() {
        const contact = this.settings.contact;
        
        // Update phone links
        if (contact.contact_phone) {
            document.querySelectorAll('[data-contact="phone"]').forEach(el => {
                if (el.tagName === 'A') {
                    el.href = `tel:${contact.contact_phone.replace(/[^\d+]/g, '')}`;
                }
                el.textContent = contact.contact_phone;
            });
        }
        
        // Update email links
        if (contact.contact_email) {
            document.querySelectorAll('[data-contact="email"]').forEach(el => {
                if (el.tagName === 'A') {
                    el.href = `mailto:${contact.contact_email}`;
                }
                el.textContent = contact.contact_email;
            });
        }
        
        // Update address
        if (contact.contact_address) {
            document.querySelectorAll('[data-contact="address"]').forEach(el => {
                el.textContent = contact.contact_address;
            });
        }
        
        // Update working hours
        if (contact.contact_working_hours) {
            document.querySelectorAll('[data-contact="working-hours"]').forEach(el => {
                el.textContent = contact.contact_working_hours;
            });
        }
        
        // Update JSON-LD structured data
        this.updateStructuredData(contact);
    }
    
    applySocialSettings() {
        const social = this.settings.social;
        
        // Update Telegram links
        if (social.social_telegram) {
            document.querySelectorAll('[data-social="telegram"]').forEach(el => {
                if (el.tagName === 'A') {
                    el.href = social.social_telegram;
                }
            });
        }
        
        // Update VK links
        if (social.social_vk) {
            document.querySelectorAll('[data-social="vk"]').forEach(el => {
                if (el.tagName === 'A') {
                    el.href = social.social_vk;
                    el.style.display = '';
                }
            });
        }
        
        // Update Instagram links
        if (social.social_instagram) {
            document.querySelectorAll('[data-social="instagram"]').forEach(el => {
                if (el.tagName === 'A') {
                    el.href = social.social_instagram;
                    el.style.display = '';
                }
            });
        }
        
        // Update Facebook links
        if (social.social_facebook) {
            document.querySelectorAll('[data-social="facebook"]').forEach(el => {
                if (el.tagName === 'A') {
                    el.href = social.social_facebook;
                    el.style.display = '';
                }
            });
        }
        
        // Update YouTube links
        if (social.social_youtube) {
            document.querySelectorAll('[data-social="youtube"]').forEach(el => {
                if (el.tagName === 'A') {
                    el.href = social.social_youtube;
                    el.style.display = '';
                }
            });
        }
    }
    
    updateMetaTag(attr, name, content) {
        let meta = document.querySelector(`meta[${attr}="${name}"]`);
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute(attr, name);
            document.head.appendChild(meta);
        }
        meta.content = content;
    }
    
    updateStructuredData(contact) {
        try {
            // Find LocalBusiness structured data script
            const scripts = document.querySelectorAll('script[type="application/ld+json"]');
            scripts.forEach(script => {
                const data = JSON.parse(script.textContent);
                if (data['@type'] === 'LocalBusiness') {
                    // Update contact information
                    if (contact.contact_phone) {
                        data.telephone = contact.contact_phone;
                    }
                    if (contact.contact_email) {
                        data.email = contact.contact_email;
                    }
                    if (contact.contact_address && data.address) {
                        data.address.streetAddress = contact.contact_address;
                    }
                    if (contact.contact_city && data.address) {
                        data.address.addressLocality = contact.contact_city;
                    }
                    if (contact.contact_postal_code && data.address) {
                        data.address.postalCode = contact.contact_postal_code;
                    }
                    if (contact.contact_latitude && contact.contact_longitude && data.geo) {
                        data.geo.latitude = parseFloat(contact.contact_latitude);
                        data.geo.longitude = parseFloat(contact.contact_longitude);
                    }
                    
                    // Update script content
                    script.textContent = JSON.stringify(data, null, 2);
                }
            });
        } catch (error) {
            console.error('Error updating structured data:', error);
        }
    }
    
    // Public getter methods
    get(group, key) {
        if (!this.settings[group]) return null;
        return this.settings[group][`${group}_${key}`] || null;
    }
    
    getContact(key) {
        return this.get('contact', key);
    }
    
    getSocial(key) {
        return this.get('social', key);
    }
    
    getSEO(key) {
        return this.get('seo', key);
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', async () => {
        window.siteSettings = new SettingsLoader();
        await window.siteSettings.init();
    });
} else {
    window.siteSettings = new SettingsLoader();
    window.siteSettings.init();
}
