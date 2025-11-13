// ========================================
// API CLIENT - Centralized API Communication
// ========================================

class APIClient {
    constructor(baseUrl = null) {
        this.baseUrl = baseUrl || (typeof window.CONFIG !== 'undefined' && window.CONFIG.apiBaseUrl) || '/api';
        this.isOnline = true;
        this.lastSuccessfulRequest = Date.now();
        this.retryConfig = {
            maxRetries: 3,
            initialDelay: 1000,
            maxDelay: 5000,
            backoffMultiplier: 2
        };
        this.listeners = {
            online: [],
            offline: []
        };
        this._cachedCsrfToken = null;
        
        this.checkConnectivity();
    }
    
    getCsrfToken() {
        if (this._cachedCsrfToken) {
            return this._cachedCsrfToken;
        }
        
        if (window.ADMIN_SESSION && window.ADMIN_SESSION.csrfToken) {
            this._cachedCsrfToken = window.ADMIN_SESSION.csrfToken;
            return this._cachedCsrfToken;
        }
        
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            this._cachedCsrfToken = metaTag.getAttribute('content');
            return this._cachedCsrfToken;
        }
        
        return null;
    }
    
    on(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        }
    }
    
    emit(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => callback(data));
        }
    }
    
    async checkConnectivity() {
        const wasOnline = this.isOnline;
        
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);
            
            const response = await fetch(`${this.baseUrl}/test.php`, {
                method: 'HEAD',
                credentials: 'include',
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            this.isOnline = response.ok;
            
            if (this.isOnline) {
                this.lastSuccessfulRequest = Date.now();
            }
        } catch (error) {
            this.isOnline = false;
        }
        
        if (wasOnline !== this.isOnline) {
            const event = this.isOnline ? 'online' : 'offline';
            console.log(`🌐 Connectivity status changed: ${event.toUpperCase()}`);
            this.emit(event, { timestamp: Date.now() });
        }
        
        return this.isOnline;
    }
    
    async sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // ========================================
    // Generic HTTP Methods
    // ========================================
    
    async request(endpoint, method = 'GET', data = null, options = {}) {
        const url = `${this.baseUrl}/${endpoint}`;
        
        const defaultHeaders = {
            'Accept': 'application/json'
        };
        
        const csrfToken = this.getCsrfToken();
        if (csrfToken) {
            defaultHeaders['X-CSRF-Token'] = csrfToken;
        }
        
        const isFormData = data instanceof FormData;
        
        if (!isFormData && data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            defaultHeaders['Content-Type'] = 'application/json';
        }
        
        const mergedHeaders = {
            ...defaultHeaders,
            ...(options.headers || {})
        };
        
        const fetchOptions = {
            method,
            headers: mergedHeaders,
            credentials: 'include',
            ...options
        };
        
        delete fetchOptions.skipRetry;
        
        if (data) {
            if (isFormData) {
                fetchOptions.body = data;
            } else if (method === 'POST' || method === 'PUT' || method === 'DELETE') {
                fetchOptions.body = JSON.stringify(data);
            }
        }
        
        const maxRetries = options.skipRetry ? 0 : this.retryConfig.maxRetries;
        let lastError = null;
        
        for (let attempt = 0; attempt <= maxRetries; attempt++) {
            try {
                console.log(`🔄 API ${method} ${endpoint}${attempt > 0 ? ` (retry ${attempt}/${maxRetries})` : ''}`, data);
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000);
                
                const response = await fetch(url, {
                    ...fetchOptions,
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                const result = await response.json();
                
                if (!response.ok || !result.success) {
                    const errorObj = {
                        message: result.error || 'API request failed',
                        statusCode: response.status,
                        endpoint,
                        method,
                        isServerError: response.status >= 500,
                        isClientError: response.status >= 400 && response.status < 500,
                        isNetworkError: false,
                        timestamp: Date.now(),
                        retryable: response.status >= 500 || response.status === 429
                    };
                    
                    console.error(`❌ API Error ${endpoint}:`, errorObj);
                    
                    if (errorObj.retryable && attempt < maxRetries) {
                        const delay = Math.min(
                            this.retryConfig.initialDelay * Math.pow(this.retryConfig.backoffMultiplier, attempt),
                            this.retryConfig.maxDelay
                        );
                        console.log(`⏳ Retrying in ${delay}ms...`);
                        await this.sleep(delay);
                        lastError = errorObj;
                        continue;
                    }
                    
                    throw errorObj;
                }
                
                this.isOnline = true;
                this.lastSuccessfulRequest = Date.now();
                console.log(`✅ API ${method} ${endpoint} success`, result);
                return result;
                
            } catch (error) {
                const isNetworkError = error.name === 'AbortError' || error.name === 'TypeError' || !error.statusCode;
                
                const errorObj = error.statusCode ? error : {
                    message: error.message || 'Network request failed',
                    endpoint,
                    method,
                    isNetworkError,
                    isServerError: false,
                    isClientError: false,
                    timestamp: Date.now(),
                    retryable: isNetworkError
                };
                
                console.error(`❌ API ${method} ${endpoint} failed:`, errorObj);
                
                if (isNetworkError) {
                    this.isOnline = false;
                    this.emit('offline', { timestamp: Date.now() });
                }
                
                if (errorObj.retryable && attempt < maxRetries) {
                    const delay = Math.min(
                        this.retryConfig.initialDelay * Math.pow(this.retryConfig.backoffMultiplier, attempt),
                        this.retryConfig.maxDelay
                    );
                    console.log(`⏳ Retrying in ${delay}ms...`);
                    await this.sleep(delay);
                    lastError = errorObj;
                    continue;
                }
                
                throw errorObj;
            }
        }
        
        throw lastError || new Error('Request failed after all retries');
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
    
    async delete(endpoint, data = null) {
        return this.request(endpoint, 'DELETE', data);
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
    
    // ========================================
    // Connectivity Status
    // ========================================
    
    getStatus() {
        const now = Date.now();
        const timeSinceLastSuccess = now - this.lastSuccessfulRequest;
        
        return {
            isOnline: this.isOnline,
            lastSuccessfulRequest: this.lastSuccessfulRequest,
            timeSinceLastSuccess,
            isStale: timeSinceLastSuccess > 300000
        };
    }
}

// Create global instance
window.apiClient = new APIClient();
console.log('✅ APIClient initialized');
