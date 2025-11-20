// ========================================
// CONTENT LOADER - Bootstrap initial data from API
// ========================================

class ContentLoader {
    constructor() {
        this.db = typeof db !== 'undefined' ? db : null;
        this.loading = {};
        this.loaded = {};
        this.errors = {};
        
        // Listen for invalidation events
        if (typeof window !== 'undefined') {
            window.addEventListener('content-invalidated', (event) => {
                this.handleInvalidation(event.detail);
            });
        }
    }

    async loadResource(resourceType, forceRefresh = false) {
        if (!this.db) {
            console.error('❌ Database not initialized');
            return null;
        }

        if (this.loading[resourceType]) {
            console.log(`⏳ Already loading ${resourceType}, waiting...`);
            return this.loading[resourceType];
        }

        if (!forceRefresh && this.loaded[resourceType]) {
            console.log(`✅ Using already loaded ${resourceType}`);
            return this.loaded[resourceType];
        }

        try {
            this.loading[resourceType] = this.fetchResource(resourceType);
            const data = await this.loading[resourceType];
            this.loaded[resourceType] = data;
            delete this.errors[resourceType];
            return data;
        } catch (error) {
            console.error(`❌ Failed to load ${resourceType}:`, error);
            this.errors[resourceType] = error;
            return null;
        } finally {
            delete this.loading[resourceType];
        }
    }

    async fetchResource(resourceType) {
        switch (resourceType) {
            case 'services':
                return await this.db.getServices();
            case 'portfolio':
                return await this.db.getPortfolio();
            case 'testimonials':
                return await this.db.getTestimonials();
            case 'faq':
                return await this.db.getFAQ();
            case 'settings':
                return await this.db.getOrCreateSettings();
            default:
                throw new Error(`Unknown resource type: ${resourceType}`);
        }
    }

    async loadAll(resources = ['services', 'portfolio', 'testimonials', 'faq']) {
        console.log('🔄 Loading all content resources:', resources);
        
        const promises = resources.map(resource => 
            this.loadResource(resource).catch(error => {
                console.error(`❌ Failed to load ${resource}:`, error);
                return null;
            })
        );

        const results = await Promise.all(promises);
        
        const data = {};
        resources.forEach((resource, index) => {
            data[resource] = results[index];
        });

        return data;
    }

    handleInvalidation(detail) {
        const { resource, timestamp } = detail;
        console.log(`🔄 Invalidation received for ${resource} at ${timestamp}`);
        
        // Clear loaded cache
        delete this.loaded[resource];
        
        // Emit custom event for UI components to reload
        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('content-reload-needed', {
                detail: { resource, timestamp }
            }));
        }
    }

    getLoadedData(resourceType) {
        return this.loaded[resourceType] || null;
    }

    getError(resourceType) {
        return this.errors[resourceType] || null;
    }

    isLoading(resourceType) {
        return !!this.loading[resourceType];
    }

    isLoaded(resourceType) {
        return !!this.loaded[resourceType];
    }

    hasError(resourceType) {
        return !!this.errors[resourceType];
    }

    clearCache(resourceType = null) {
        if (resourceType) {
            delete this.loaded[resourceType];
            delete this.errors[resourceType];
        } else {
            this.loaded = {};
            this.errors = {};
        }
    }

    async bootstrapPage(resources = []) {
        console.log('🚀 Bootstrapping page with resources:', resources);
        
        // Show loading state
        this.showSkeleton(resources);
        
        try {
            // Load all resources
            const data = await this.loadAll(resources);
            
            // Set global initial data
            if (typeof window !== 'undefined') {
                window.__INITIAL_DATA__ = data;
            }
            
            // Hide skeleton
            this.hideSkeleton();
            
            // Emit ready event
            if (typeof window !== 'undefined') {
                window.dispatchEvent(new CustomEvent('content-ready', {
                    detail: { data, resources }
                }));
            }
            
            return data;
        } catch (error) {
            console.error('❌ Bootstrap failed:', error);
            this.showError(resources);
            throw error;
        }
    }

    showSkeleton(resources) {
        resources.forEach(resource => {
            const containers = document.querySelectorAll(`[data-content="${resource}"]`);
            containers.forEach(container => {
                container.classList.add('loading-skeleton');
                container.setAttribute('aria-busy', 'true');
            });
        });
    }

    hideSkeleton() {
        const skeletons = document.querySelectorAll('.loading-skeleton');
        skeletons.forEach(skeleton => {
            skeleton.classList.remove('loading-skeleton');
            skeleton.removeAttribute('aria-busy');
        });
    }

    showError(resources) {
        resources.forEach(resource => {
            const containers = document.querySelectorAll(`[data-content="${resource}"]`);
            containers.forEach(container => {
                container.classList.remove('loading-skeleton');
                container.classList.add('loading-error');
                container.innerHTML = `
                    <div class="error-message">
                        <p>Не удалось загрузить данные. Проверьте подключение к интернету.</p>
                        <button onclick="contentLoader.loadResource('${resource}', true).then(() => location.reload())">
                            Повторить
                        </button>
                    </div>
                `;
            });
        });
    }

    async reloadResource(resourceType) {
        console.log(`🔄 Reloading ${resourceType}...`);
        
        const data = await this.loadResource(resourceType, true);
        
        if (data && typeof window !== 'undefined') {
            if (window.__INITIAL_DATA__) {
                window.__INITIAL_DATA__[resourceType] = data;
            }
            
            window.dispatchEvent(new CustomEvent('content-reloaded', {
                detail: { resource: resourceType, data }
            }));
        }
        
        return data;
    }
}

// Create global instance
if (typeof window !== 'undefined') {
    window.contentLoader = new ContentLoader();
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ContentLoader;
}
