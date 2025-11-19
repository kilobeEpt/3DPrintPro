/**
 * Calculator API Loader
 * 
 * Fetches calculator configuration from API and caches it locally.
 * Provides fallback to hardcoded CONFIG if API is unavailable.
 */

class CalculatorConfigLoader {
    constructor() {
        this.config = null;
        this.cacheKey = 'calculator_config';
        this.cacheTTL = 300000; // 5 minutes in milliseconds
        this.apiUrl = '/api/calculator-settings.php';
        this.loading = false;
        this.loadPromise = null;
    }
    
    /**
     * Load configuration from API or cache
     * 
     * @returns {Promise<Object>} Configuration object
     */
    async loadConfig() {
        // If already loading, return the existing promise
        if (this.loading) {
            return this.loadPromise;
        }
        
        // Check cache first
        const cached = this.getFromCache();
        if (cached) {
            console.log('✅ Calculator config loaded from cache');
            this.config = cached;
            return this.config;
        }
        
        // Set loading flag
        this.loading = true;
        
        // Create load promise
        this.loadPromise = this.fetchFromApi()
            .then(config => {
                this.config = config;
                this.saveToCache(config);
                console.log('✅ Calculator config loaded from API');
                return config;
            })
            .catch(error => {
                console.warn('⚠️ Failed to load config from API, using fallback:', error);
                this.config = this.getFallbackConfig();
                return this.config;
            })
            .finally(() => {
                this.loading = false;
            });
        
        return this.loadPromise;
    }
    
    /**
     * Fetch configuration from API
     * 
     * @returns {Promise<Object>}
     */
    async fetchFromApi() {
        const response = await fetch(this.apiUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success || !data.data) {
            throw new Error('Invalid API response format');
        }
        
        return data.data;
    }
    
    /**
     * Get configuration from localStorage cache
     * 
     * @returns {Object|null}
     */
    getFromCache() {
        try {
            const cached = localStorage.getItem(this.cacheKey);
            if (!cached) return null;
            
            const parsed = JSON.parse(cached);
            const now = Date.now();
            
            // Check if cache is expired
            if (now - parsed.timestamp > this.cacheTTL) {
                localStorage.removeItem(this.cacheKey);
                return null;
            }
            
            return parsed.config;
        } catch (error) {
            console.warn('Failed to read cache:', error);
            return null;
        }
    }
    
    /**
     * Save configuration to localStorage cache
     * 
     * @param {Object} config
     */
    saveToCache(config) {
        try {
            const data = {
                config: config,
                timestamp: Date.now()
            };
            localStorage.setItem(this.cacheKey, JSON.stringify(data));
        } catch (error) {
            console.warn('Failed to save cache:', error);
        }
    }
    
    /**
     * Clear cached configuration
     */
    clearCache() {
        localStorage.removeItem(this.cacheKey);
    }
    
    /**
     * Get fallback configuration from hardcoded CONFIG
     * 
     * @returns {Object}
     */
    getFallbackConfig() {
        // If CONFIG is available, convert it to API format
        if (typeof CONFIG !== 'undefined') {
            return {
                materials: this.convertMaterialsToArray(CONFIG.materialPrices),
                services: this.convertServicesToArray(CONFIG.servicePrices),
                quality_multipliers: CONFIG.qualityMultipliers,
                discounts: CONFIG.discounts,
                validation: {
                    weight: { min: 1, max: 10000, label: 'Вес (г)' },
                    quantity: { min: 1, max: 1000, label: 'Количество (шт)' },
                    infill: { min: 0, max: 100, label: 'Заполнение (%)' }
                }
            };
        }
        
        // If CONFIG not available, return minimal config
        return {
            materials: [],
            services: [],
            quality_multipliers: {},
            discounts: [],
            validation: {}
        };
    }
    
    /**
     * Convert old CONFIG.materialPrices format to array
     */
    convertMaterialsToArray(materialPrices) {
        return Object.entries(materialPrices).map(([key, mat], index) => ({
            key: key,
            name: mat.name,
            price: mat.price,
            technology: mat.technology,
            active: true,
            order: index + 1
        }));
    }
    
    /**
     * Convert old CONFIG.servicePrices format to array
     */
    convertServicesToArray(servicePrices) {
        return Object.entries(servicePrices).map(([key, service], index) => ({
            key: key,
            name: service.name,
            price: service.price,
            unit: service.unit,
            active: true,
            order: index + 1
        }));
    }
    
    /**
     * Get current configuration (load if not loaded)
     * 
     * @returns {Promise<Object>}
     */
    async getConfig() {
        if (!this.config) {
            await this.loadConfig();
        }
        return this.config;
    }
    
    /**
     * Force reload configuration from API
     * 
     * @returns {Promise<Object>}
     */
    async reloadConfig() {
        this.clearCache();
        this.config = null;
        return this.loadConfig();
    }
}

// Create global instance
window.calculatorConfigLoader = new CalculatorConfigLoader();

// Auto-load configuration on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.calculatorConfigLoader.loadConfig();
    });
} else {
    window.calculatorConfigLoader.loadConfig();
}
