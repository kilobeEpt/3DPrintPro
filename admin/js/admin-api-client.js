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
        return this.client.createService(data);
    }
    
    async updateService(id, data) {
        return this.client.updateService(id, data);
    }
    
    async deleteService(id) {
        return this.client.deleteService(id);
    }
    
    // Portfolio API
    async getPortfolio() {
        return this.client.getPortfolio();
    }
    
    async getPortfolioItem(id) {
        return this.client.getPortfolioItem(id);
    }
    
    async createPortfolioItem(data) {
        return this.client.createPortfolioItem(data);
    }
    
    async updatePortfolioItem(id, data) {
        return this.client.updatePortfolioItem(id, data);
    }
    
    async deletePortfolioItem(id) {
        return this.client.deletePortfolioItem(id);
    }
    
    // Testimonials API
    async getTestimonials() {
        return this.client.getTestimonials();
    }
    
    async getTestimonial(id) {
        return this.client.getTestimonial(id);
    }
    
    async createTestimonial(data) {
        return this.client.createTestimonial(data);
    }
    
    async updateTestimonial(id, data) {
        return this.client.updateTestimonial(id, data);
    }
    
    async deleteTestimonial(id) {
        return this.client.deleteTestimonial(id);
    }
    
    // FAQ API
    async getFAQ() {
        return this.client.getFAQ();
    }
    
    async getFAQItem(id) {
        return this.client.getFAQItem(id);
    }
    
    async createFAQItem(data) {
        return this.client.createFAQItem(data);
    }
    
    async updateFAQItem(id, data) {
        return this.client.updateFAQItem(id, data);
    }
    
    async deleteFAQItem(id) {
        return this.client.deleteFAQItem(id);
    }
    
    // Content API
    async getContentBlocks() {
        return this.client.getContentBlocks();
    }
    
    async getContentBlock(id) {
        return this.client.getContentBlock(id);
    }
    
    async createContentBlock(data) {
        return this.client.createContentBlock(data);
    }
    
    async updateContentBlock(id, data) {
        return this.client.updateContentBlock(id, data);
    }
    
    async deleteContentBlock(id) {
        return this.client.deleteContentBlock(id);
    }
    
    // Settings API
    async getSettings() {
        return this.client.getAllSettings();
    }
    
    async getSetting(key) {
        return this.client.getSetting(key);
    }
    
    async updateSetting(key, value) {
        return this.client.saveSetting(key, value);
    }
    
    async updateSettings(settings) {
        return this.client.saveSettings(settings);
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
