// ========================================
// Admin API Client - Thin wrapper around public apiClient
// Adds automatic CSRF token handling and admin-specific error handling
// ========================================

class AdminApiClient {
    constructor() {
        if (!window.apiClient) {
            throw new Error('Public apiClient must be loaded before AdminApiClient');
        }
        
        if (!window.ADMIN_SESSION || !window.ADMIN_SESSION.csrfToken) {
            console.warn('⚠️ ADMIN_SESSION not found - CSRF protection may fail');
        }
        
        this.client = window.apiClient;
        console.log('✅ AdminApiClient initialized with CSRF token');
    }
    
    // Orders API
    async getOrders() {
        return this.client.getOrders();
    }
    
    async getOrder(id) {
        return this.client.getOrder(id);
    }
    
    async updateOrder(id, data) {
        return this.client.updateOrder(id, data);
    }
    
    async deleteOrder(id) {
        return this.client.deleteOrder(id);
    }
    
    // Services API
    async getServices() {
        return this.client.getServices();
    }
    
    async getService(id) {
        return this.client.getService(id);
    }
    
    async createService(data) {
        return this.client.request('/api/services.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async updateService(id, data) {
        return this.client.request(`/api/services.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    async deleteService(id) {
        return this.client.request(`/api/services.php?id=${id}`, {
            method: 'DELETE'
        });
    }
    
    // Portfolio API
    async getPortfolio() {
        return this.client.getPortfolio();
    }
    
    async getPortfolioItem(id) {
        return this.client.getPortfolioItem(id);
    }
    
    async createPortfolioItem(data) {
        return this.client.request('/api/portfolio.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async updatePortfolioItem(id, data) {
        return this.client.request(`/api/portfolio.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    async deletePortfolioItem(id) {
        return this.client.request(`/api/portfolio.php?id=${id}`, {
            method: 'DELETE'
        });
    }
    
    // Testimonials API
    async getTestimonials() {
        return this.client.getTestimonials();
    }
    
    async getTestimonial(id) {
        return this.client.getTestimonial(id);
    }
    
    async createTestimonial(data) {
        return this.client.request('/api/testimonials.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async updateTestimonial(id, data) {
        return this.client.request(`/api/testimonials.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    async deleteTestimonial(id) {
        return this.client.request(`/api/testimonials.php?id=${id}`, {
            method: 'DELETE'
        });
    }
    
    // FAQ API
    async getFAQ() {
        return this.client.getFAQ();
    }
    
    async getFAQItem(id) {
        return this.client.getFAQItem(id);
    }
    
    async createFAQItem(data) {
        return this.client.request('/api/faq.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async updateFAQItem(id, data) {
        return this.client.request(`/api/faq.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    async deleteFAQItem(id) {
        return this.client.request(`/api/faq.php?id=${id}`, {
            method: 'DELETE'
        });
    }
    
    // Content API
    async getContentBlocks() {
        return this.client.getContentBlocks();
    }
    
    async getContentBlock(id) {
        return this.client.getContentBlock(id);
    }
    
    async createContentBlock(data) {
        return this.client.request('/api/content.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async updateContentBlock(id, data) {
        return this.client.request(`/api/content.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    async deleteContentBlock(id) {
        return this.client.request(`/api/content.php?id=${id}`, {
            method: 'DELETE'
        });
    }
    
    // Settings API
    async getSettings() {
        return this.client.getSettings();
    }
    
    async getSetting(key) {
        return this.client.getSetting(key);
    }
    
    async updateSetting(key, value) {
        return this.client.updateSetting(key, value);
    }
    
    async updateSettings(settings) {
        return this.client.request('/api/settings.php', {
            method: 'POST',
            body: JSON.stringify(settings)
        });
    }
}

// Initialize global admin API client after apiClient is ready
function initAdminApiClient() {
    if (!window.apiClient) {
        console.warn('⚠️ Waiting for apiClient to be ready...');
        setTimeout(initAdminApiClient, 50);
        return;
    }
    
    window.adminApi = new AdminApiClient();
    console.log('🔐 Admin API Client ready');
}

// Start initialization
initAdminApiClient();
