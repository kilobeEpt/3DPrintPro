// ========================================
// Admin API Client - Wrapper around public apiClient
// Normalizes paths, extracts data, and ensures CSRF token presence
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
    
    // ========================================
    // Helper Methods - Wrap base client methods with CSRF refresh
    // ========================================
    
    /**
     * Refresh CSRF token from current session before requests
     * Accommodates token regeneration after login/logout
     */
    refreshCsrfToken() {
        if (window.ADMIN_SESSION && window.ADMIN_SESSION.csrfToken) {
            this.client._cachedCsrfToken = window.ADMIN_SESSION.csrfToken;
        } else {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                this.client._cachedCsrfToken = metaTag.getAttribute('content');
            }
        }
    }
    
    /**
     * Generic request wrapper that ensures CSRF token is current
     */
    async request(endpoint, method = 'GET', data = null, options = {}) {
        this.refreshCsrfToken();
        return this.client.request(endpoint, method, data, options);
    }
    
    async get(endpoint) {
        this.refreshCsrfToken();
        return this.client.get(endpoint);
    }
    
    async post(endpoint, data) {
        this.refreshCsrfToken();
        return this.client.post(endpoint, data);
    }
    
    async put(endpoint, data) {
        this.refreshCsrfToken();
        return this.client.put(endpoint, data);
    }
    
    async delete(endpoint, data = null) {
        this.refreshCsrfToken();
        return this.client.delete(endpoint, data);
    }
    
    // ========================================
    // Orders API - Returns arrays/objects expected by modules
    // ========================================
    
    async getOrders(params = {}) {
        const result = await this.client.getOrders(params);
        // Return just the orders array (modules expect array, not {orders, total})
        return result.orders || [];
    }
    
    async getOrder(id) {
        const result = await this.client.getOrder(id);
        // Return the order object
        return result;
    }
    
    async updateOrder(id, data) {
        const result = await this.client.updateOrder(id, data);
        // Return full response for status checking
        return result;
    }
    
    async deleteOrder(id) {
        const result = await this.client.deleteOrder(id);
        // Return full response for status checking
        return result;
    }
    
    // ========================================
    // Services API - Returns arrays/objects expected by modules
    // ========================================
    
    async getServices(params = {}) {
        const result = await this.client.getServices(params);
        // Return just the services array
        return result.services || [];
    }
    
    async getService(id) {
        const result = await this.client.getService(id);
        // Return the service object
        return result;
    }
    
    async createService(data) {
        const result = await this.client.createService(data);
        // Return full response for status checking
        return result;
    }
    
    async updateService(id, data) {
        const result = await this.client.updateService(id, data);
        // Return full response for status checking
        return result;
    }
    
    async deleteService(id) {
        const result = await this.client.deleteService(id);
        // Return full response for status checking
        return result;
    }
    
    // ========================================
    // Portfolio API - Returns arrays/objects expected by modules
    // ========================================
    
    async getPortfolio(params = {}) {
        const result = await this.client.getPortfolio(params);
        // Return just the items array
        return result.items || [];
    }
    
    async getPortfolioItem(id) {
        const result = await this.client.getPortfolioItem(id);
        // Return the item object
        return result;
    }
    
    async createPortfolioItem(data) {
        const result = await this.client.createPortfolioItem(data);
        // Return full response for status checking
        return result;
    }
    
    async updatePortfolioItem(id, data) {
        const result = await this.client.updatePortfolioItem(id, data);
        // Return full response for status checking
        return result;
    }
    
    async deletePortfolioItem(id) {
        const result = await this.client.deletePortfolioItem(id);
        // Return full response for status checking
        return result;
    }
    
    // ========================================
    // Testimonials API - Returns arrays/objects expected by modules
    // ========================================
    
    async getTestimonials(params = {}) {
        const result = await this.client.getTestimonials(params);
        // Return just the testimonials array
        return result.testimonials || [];
    }
    
    async getTestimonial(id) {
        const result = await this.client.getTestimonial(id);
        // Return the testimonial object
        return result;
    }
    
    async createTestimonial(data) {
        const result = await this.client.createTestimonial(data);
        // Return full response for status checking
        return result;
    }
    
    async updateTestimonial(id, data) {
        const result = await this.client.updateTestimonial(id, data);
        // Return full response for status checking
        return result;
    }
    
    async deleteTestimonial(id) {
        const result = await this.client.deleteTestimonial(id);
        // Return full response for status checking
        return result;
    }
    
    // ========================================
    // FAQ API - Returns arrays/objects expected by modules
    // ========================================
    
    async getFAQ(params = {}) {
        const result = await this.client.getFAQ(params);
        // Return just the items array
        return result.items || [];
    }
    
    async getFAQItem(id) {
        const result = await this.client.getFAQItem(id);
        // Return the item object
        return result;
    }
    
    async createFAQItem(data) {
        const result = await this.client.createFAQItem(data);
        // Return full response for status checking
        return result;
    }
    
    async updateFAQItem(id, data) {
        const result = await this.client.updateFAQItem(id, data);
        // Return full response for status checking
        return result;
    }
    
    async deleteFAQItem(id) {
        const result = await this.client.deleteFAQItem(id);
        // Return full response for status checking
        return result;
    }
    
    // ========================================
    // Content Blocks API - Returns arrays/objects expected by modules
    // ========================================
    
    async getContentBlocks(params = {}) {
        const result = await this.client.getContentBlocks(params);
        // Return just the blocks array
        return result.blocks || [];
    }
    
    async getContentBlock(id) {
        const result = await this.client.getContentBlock(id);
        // Return the block object
        return result;
    }
    
    async createContentBlock(data) {
        const result = await this.client.createContentBlock(data);
        // Return full response for status checking
        return result;
    }
    
    async updateContentBlock(id, data) {
        const result = await this.client.updateContentBlock(id, data);
        // Return full response for status checking
        return result;
    }
    
    async deleteContentBlock(id) {
        const result = await this.client.deleteContentBlock(id);
        // Return full response for status checking
        return result;
    }
    
    // ========================================
    // Settings API - Returns object expected by modules
    // ========================================
    
    async getSettings() {
        const result = await this.client.getAllSettings();
        // Return the settings object
        return result;
    }
    
    async getSetting(key) {
        const result = await this.client.getSetting(key);
        // Return the value
        return result;
    }
    
    async updateSetting(key, value) {
        const result = await this.client.saveSetting(key, value);
        // Return full response for status checking
        return result;
    }
    
    async updateSettings(settings) {
        const result = await this.client.saveSettings(settings);
        // Return full response for status checking
        return result;
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
