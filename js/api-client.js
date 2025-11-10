// ========================================
// API CLIENT - Centralized API Communication
// ========================================

class APIClient {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }
    
    // ========================================
    // Generic HTTP Methods
    // ========================================
    
    async request(endpoint, method = 'GET', data = null) {
        const url = `${this.baseUrl}/${endpoint}`;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        try {
            console.log(`🔄 API ${method} ${endpoint}`, data);
            const response = await fetch(url, options);
            const result = await response.json();
            
            if (!response.ok || !result.success) {
                console.error(`❌ API Error ${endpoint}:`, result.error);
                throw new Error(result.error || 'API request failed');
            }
            
            console.log(`✅ API ${method} ${endpoint} success`, result);
            return result;
        } catch (error) {
            console.error(`❌ API ${method} ${endpoint} failed:`, error);
            throw error;
        }
    }
    
    async get(endpoint) {
        return this.request(endpoint, 'GET');
    }
    
    async post(endpoint, data) {
        return this.request(endpoint, 'POST', data);
    }
    
    async put(endpoint, data) {
        return this.request(endpoint, 'PUT', data);
    }
    
    async delete(endpoint) {
        return this.request(endpoint, 'DELETE');
    }
    
    // ========================================
    // Settings API
    // ========================================
    
    async getAllSettings() {
        const result = await this.get('settings.php');
        return result.settings || {};
    }
    
    async getSetting(key) {
        const result = await this.get(`settings.php?key=${encodeURIComponent(key)}`);
        return result.value;
    }
    
    async saveSettings(settings) {
        return this.post('settings.php', settings);
    }
    
    async saveSetting(key, value) {
        return this.post('settings.php', { key, value });
    }
    
    async deleteSetting(key) {
        return this.delete(`settings.php`, { key });
    }
    
    // ========================================
    // Orders API
    // ========================================
    
    async getOrders(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `orders.php?${queryString}` : 'orders.php';
        const result = await this.get(endpoint);
        return {
            orders: result.orders || [],
            total: result.total || 0,
            limit: result.limit,
            offset: result.offset
        };
    }
    
    async getOrder(id) {
        const result = await this.get(`orders.php?id=${id}`);
        return result.order;
    }
    
    async submitOrder(data) {
        return this.post('orders.php', data);
    }
    
    async updateOrder(id, data) {
        return this.put('orders.php', { id, ...data });
    }
    
    async deleteOrder(id) {
        return this.delete(`orders.php?id=${id}`);
    }
    
    // ========================================
    // Services API
    // ========================================
    
    async getServices(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `services.php?${queryString}` : 'services.php';
        const result = await this.get(endpoint);
        return {
            services: result.services || [],
            total: result.total || 0
        };
    }
    
    async getService(id) {
        const result = await this.get(`services.php?id=${id}`);
        return result.service;
    }
    
    async createService(data) {
        return this.post('services.php', data);
    }
    
    async updateService(id, data) {
        return this.put('services.php', { id, ...data });
    }
    
    async deleteService(id) {
        return this.delete(`services.php?id=${id}`);
    }
    
    // ========================================
    // Portfolio API
    // ========================================
    
    async getPortfolio(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `portfolio.php?${queryString}` : 'portfolio.php';
        const result = await this.get(endpoint);
        return {
            items: result.items || [],
            total: result.total || 0
        };
    }
    
    async getPortfolioItem(id) {
        const result = await this.get(`portfolio.php?id=${id}`);
        return result.item;
    }
    
    async createPortfolioItem(data) {
        return this.post('portfolio.php', data);
    }
    
    async updatePortfolioItem(id, data) {
        return this.put('portfolio.php', { id, ...data });
    }
    
    async deletePortfolioItem(id) {
        return this.delete(`portfolio.php?id=${id}`);
    }
    
    // ========================================
    // Testimonials API
    // ========================================
    
    async getTestimonials(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `testimonials.php?${queryString}` : 'testimonials.php';
        const result = await this.get(endpoint);
        return {
            testimonials: result.testimonials || [],
            total: result.total || 0
        };
    }
    
    async getTestimonial(id) {
        const result = await this.get(`testimonials.php?id=${id}`);
        return result.testimonial;
    }
    
    async createTestimonial(data) {
        return this.post('testimonials.php', data);
    }
    
    async updateTestimonial(id, data) {
        return this.put('testimonials.php', { id, ...data });
    }
    
    async deleteTestimonial(id) {
        return this.delete(`testimonials.php?id=${id}`);
    }
    
    // ========================================
    // FAQ API
    // ========================================
    
    async getFAQ(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `faq.php?${queryString}` : 'faq.php';
        const result = await this.get(endpoint);
        return {
            items: result.items || [],
            total: result.total || 0
        };
    }
    
    async getFAQItem(id) {
        const result = await this.get(`faq.php?id=${id}`);
        return result.item;
    }
    
    async createFAQItem(data) {
        return this.post('faq.php', data);
    }
    
    async updateFAQItem(id, data) {
        return this.put('faq.php', { id, ...data });
    }
    
    async deleteFAQItem(id) {
        return this.delete(`faq.php?id=${id}`);
    }
    
    // ========================================
    // Content Blocks API
    // ========================================
    
    async getContentBlocks(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const endpoint = queryString ? `content.php?${queryString}` : 'content.php';
        const result = await this.get(endpoint);
        return {
            blocks: result.blocks || [],
            total: result.total || 0
        };
    }
    
    async getContentBlock(id) {
        const result = await this.get(`content.php?id=${id}`);
        return result.block;
    }
    
    async getContentBlockByName(name) {
        const result = await this.get(`content.php?name=${encodeURIComponent(name)}`);
        return result.block;
    }
    
    async createContentBlock(data) {
        return this.post('content.php', data);
    }
    
    async updateContentBlock(id, data) {
        return this.put('content.php', { id, ...data });
    }
    
    async deleteContentBlock(id) {
        return this.delete(`content.php?id=${id}`);
    }
}

// Create global instance
const apiClient = new APIClient();
console.log('✅ APIClient initialized');
