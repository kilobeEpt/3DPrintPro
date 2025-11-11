// ========================================
// DATABASE MANAGEMENT (API-backed with LocalStorage fallback)
// ========================================

class Database {
    constructor() {
        this.storageKey = '3dprintpro_data';
        this.metadataKey = '3dprintpro_metadata';
        this.api = typeof apiClient !== 'undefined' ? apiClient : null;
        this.useAPI = this.api !== null;
        this.lastSync = {};
        this.loadMetadata();
        console.log(this.useAPI ? '✅ Database using API' : '⚠️ Database using localStorage fallback');
    }
    
    loadMetadata() {
        try {
            const metadata = localStorage.getItem(this.metadataKey);
            if (metadata) {
                this.lastSync = JSON.parse(metadata);
            }
        } catch (error) {
            console.error('❌ Failed to load metadata:', error);
            this.lastSync = {};
        }
    }
    
    saveMetadata() {
        try {
            localStorage.setItem(this.metadataKey, JSON.stringify(this.lastSync));
        } catch (error) {
            console.error('❌ Failed to save metadata:', error);
        }
    }
    
    updateSyncTimestamp(table, source = 'api') {
        this.lastSync[table] = {
            timestamp: Date.now(),
            source
        };
        this.saveMetadata();
    }
    
    getSyncInfo(table) {
        const syncInfo = this.lastSync[table];
        if (!syncInfo) {
            return {
                lastSync: null,
                source: 'never',
                age: null,
                isStale: true
            };
        }
        
        const now = Date.now();
        const age = now - syncInfo.timestamp;
        const isStale = age > 300000;
        
        return {
            lastSync: syncInfo.timestamp,
            source: syncInfo.source,
            age,
            isStale
        };
    }
    
    getAllSyncInfo() {
        const tables = ['services', 'portfolio', 'testimonials', 'faq', 'settings', 'orders'];
        const info = {};
        
        tables.forEach(table => {
            info[table] = this.getSyncInfo(table);
        });
        
        return info;
    }
    
    // ========================================
    // Settings Methods (API-first)
    // ========================================
    
    async getOrCreateSettings() {
        if (this.useAPI) {
            try {
                const settings = await this.api.getAllSettings();
                this.cacheToLocalStorage('settings', [settings]);
                this.updateSyncTimestamp('settings', 'api');
                return settings;
            } catch (error) {
                console.error('❌ Failed to fetch settings from API, using localStorage fallback', error);
                const cached = this.getFromLocalStorage('settings');
                this.updateSyncTimestamp('settings', 'cache');
                return cached;
            }
        } else {
            this.updateSyncTimestamp('settings', 'cache');
            return this.getFromLocalStorage('settings');
        }
    }
    
    async updateSettings(updates) {
        if (this.useAPI) {
            try {
                await this.api.saveSettings(updates);
                // Update cache
                this.cacheToLocalStorage('settings', [updates]);
                console.log('✅ Settings updated in database');
                return updates;
            } catch (error) {
                console.error('❌ Failed to update settings via API', error);
                throw error;
            }
        } else {
            return this.updateLocalStorage('settings', updates);
        }
    }
    
    // ========================================
    // Services Methods (API-first)
    // ========================================
    
    async getServices(filters = {}) {
        if (this.useAPI) {
            try {
                const result = await this.api.getServices({ active: true, ...filters });
                this.cacheToLocalStorage('services', result.services);
                this.updateSyncTimestamp('services', 'api');
                return result.services;
            } catch (error) {
                console.error('❌ Failed to fetch services from API, using localStorage fallback', error);
                const cached = this.getFromLocalStorage('services') || this.getDefaultServices();
                this.updateSyncTimestamp('services', 'cache');
                return cached;
            }
        } else {
            this.updateSyncTimestamp('services', 'cache');
            return this.getFromLocalStorage('services') || this.getDefaultServices();
        }
    }
    
    async addService(service) {
        if (this.useAPI) {
            try {
                const result = await this.api.createService(service);
                console.log('✅ Service created:', result);
                return { ...service, id: result.id };
            } catch (error) {
                console.error('❌ Failed to create service via API', error);
                throw error;
            }
        } else {
            return this.addToLocalStorage('services', service);
        }
    }
    
    async updateService(id, updates) {
        if (this.useAPI) {
            try {
                await this.api.updateService(id, updates);
                console.log('✅ Service updated');
                return { id, ...updates };
            } catch (error) {
                console.error('❌ Failed to update service via API', error);
                throw error;
            }
        } else {
            return this.updateInLocalStorage('services', id, updates);
        }
    }
    
    async deleteService(id) {
        if (this.useAPI) {
            try {
                await this.api.deleteService(id);
                console.log('✅ Service deleted');
                return true;
            } catch (error) {
                console.error('❌ Failed to delete service via API', error);
                throw error;
            }
        } else {
            return this.deleteFromLocalStorage('services', id);
        }
    }
    
    // ========================================
    // Portfolio Methods (API-first)
    // ========================================
    
    async getPortfolio(filters = {}) {
        if (this.useAPI) {
            try {
                const result = await this.api.getPortfolio({ active: true, ...filters });
                this.cacheToLocalStorage('portfolio', result.items);
                this.updateSyncTimestamp('portfolio', 'api');
                return result.items;
            } catch (error) {
                console.error('❌ Failed to fetch portfolio from API, using localStorage fallback', error);
                const cached = this.getFromLocalStorage('portfolio') || [];
                this.updateSyncTimestamp('portfolio', 'cache');
                return cached;
            }
        } else {
            this.updateSyncTimestamp('portfolio', 'cache');
            return this.getFromLocalStorage('portfolio') || [];
        }
    }
    
    async addPortfolioItem(item) {
        if (this.useAPI) {
            try {
                const result = await this.api.createPortfolioItem(item);
                console.log('✅ Portfolio item created:', result);
                return { ...item, id: result.id };
            } catch (error) {
                console.error('❌ Failed to create portfolio item via API', error);
                throw error;
            }
        } else {
            return this.addToLocalStorage('portfolio', item);
        }
    }
    
    async updatePortfolioItem(id, updates) {
        if (this.useAPI) {
            try {
                await this.api.updatePortfolioItem(id, updates);
                console.log('✅ Portfolio item updated');
                return { id, ...updates };
            } catch (error) {
                console.error('❌ Failed to update portfolio item via API', error);
                throw error;
            }
        } else {
            return this.updateInLocalStorage('portfolio', id, updates);
        }
    }
    
    async deletePortfolioItem(id) {
        if (this.useAPI) {
            try {
                await this.api.deletePortfolioItem(id);
                console.log('✅ Portfolio item deleted');
                return true;
            } catch (error) {
                console.error('❌ Failed to delete portfolio item via API', error);
                throw error;
            }
        } else {
            return this.deleteFromLocalStorage('portfolio', id);
        }
    }
    
    // ========================================
    // Testimonials Methods (API-first)
    // ========================================
    
    async getTestimonials(filters = {}) {
        if (this.useAPI) {
            try {
                const result = await this.api.getTestimonials({ active: true, approved: true, ...filters });
                this.cacheToLocalStorage('testimonials', result.testimonials);
                this.updateSyncTimestamp('testimonials', 'api');
                return result.testimonials;
            } catch (error) {
                console.error('❌ Failed to fetch testimonials from API, using localStorage fallback', error);
                const cached = this.getFromLocalStorage('testimonials') || this.getDefaultTestimonials();
                this.updateSyncTimestamp('testimonials', 'cache');
                return cached;
            }
        } else {
            this.updateSyncTimestamp('testimonials', 'cache');
            return this.getFromLocalStorage('testimonials') || this.getDefaultTestimonials();
        }
    }
    
    async addTestimonial(testimonial) {
        if (this.useAPI) {
            try {
                const result = await this.api.createTestimonial(testimonial);
                console.log('✅ Testimonial created:', result);
                return { ...testimonial, id: result.id };
            } catch (error) {
                console.error('❌ Failed to create testimonial via API', error);
                throw error;
            }
        } else {
            return this.addToLocalStorage('testimonials', testimonial);
        }
    }
    
    async updateTestimonial(id, updates) {
        if (this.useAPI) {
            try {
                await this.api.updateTestimonial(id, updates);
                console.log('✅ Testimonial updated');
                return { id, ...updates };
            } catch (error) {
                console.error('❌ Failed to update testimonial via API', error);
                throw error;
            }
        } else {
            return this.updateInLocalStorage('testimonials', id, updates);
        }
    }
    
    async deleteTestimonial(id) {
        if (this.useAPI) {
            try {
                await this.api.deleteTestimonial(id);
                console.log('✅ Testimonial deleted');
                return true;
            } catch (error) {
                console.error('❌ Failed to delete testimonial via API', error);
                throw error;
            }
        } else {
            return this.deleteFromLocalStorage('testimonials', id);
        }
    }
    
    // ========================================
    // FAQ Methods (API-first)
    // ========================================
    
    async getFAQ(filters = {}) {
        if (this.useAPI) {
            try {
                const result = await this.api.getFAQ({ active: true, ...filters });
                this.cacheToLocalStorage('faq', result.items);
                this.updateSyncTimestamp('faq', 'api');
                return result.items;
            } catch (error) {
                console.error('❌ Failed to fetch FAQ from API, using localStorage fallback', error);
                const cached = this.getFromLocalStorage('faq') || this.getDefaultFAQ();
                this.updateSyncTimestamp('faq', 'cache');
                return cached;
            }
        } else {
            this.updateSyncTimestamp('faq', 'cache');
            return this.getFromLocalStorage('faq') || this.getDefaultFAQ();
        }
    }
    
    async addFAQItem(item) {
        if (this.useAPI) {
            try {
                const result = await this.api.createFAQItem(item);
                console.log('✅ FAQ item created:', result);
                return { ...item, id: result.id };
            } catch (error) {
                console.error('❌ Failed to create FAQ item via API', error);
                throw error;
            }
        } else {
            return this.addToLocalStorage('faq', item);
        }
    }
    
    async updateFAQItem(id, updates) {
        if (this.useAPI) {
            try {
                await this.api.updateFAQItem(id, updates);
                console.log('✅ FAQ item updated');
                return { id, ...updates };
            } catch (error) {
                console.error('❌ Failed to update FAQ item via API', error);
                throw error;
            }
        } else {
            return this.updateInLocalStorage('faq', id, updates);
        }
    }
    
    async deleteFAQItem(id) {
        if (this.useAPI) {
            try {
                await this.api.deleteFAQItem(id);
                console.log('✅ FAQ item deleted');
                return true;
            } catch (error) {
                console.error('❌ Failed to delete FAQ item via API', error);
                throw error;
            }
        } else {
            return this.deleteFromLocalStorage('faq', id);
        }
    }
    
    // ========================================
    // Orders Methods (API-first)
    // ========================================
    
    async getOrders(filters = {}) {
        if (this.useAPI) {
            try {
                const result = await this.api.getOrders(filters);
                this.cacheToLocalStorage('orders', result.orders);
                this.updateSyncTimestamp('orders', 'api');
                return result.orders;
            } catch (error) {
                console.error('❌ Failed to fetch orders from API, using localStorage fallback', error);
                const cached = this.getFromLocalStorage('orders') || [];
                this.updateSyncTimestamp('orders', 'cache');
                return cached;
            }
        } else {
            this.updateSyncTimestamp('orders', 'cache');
            return this.getFromLocalStorage('orders') || [];
        }
    }
    
    // ========================================
    // LocalStorage Helper Methods (Fallback)
    // ========================================
    
    getFromLocalStorage(table) {
        try {
            const data = JSON.parse(localStorage.getItem(this.storageKey)) || {};
            return data[table] || [];
        } catch (error) {
            console.error('❌ LocalStorage read error:', error);
            return [];
        }
    }
    
    cacheToLocalStorage(table, data) {
        try {
            const allData = JSON.parse(localStorage.getItem(this.storageKey)) || {};
            allData[table] = data;
            localStorage.setItem(this.storageKey, JSON.stringify(allData));
        } catch (error) {
            console.error('❌ LocalStorage write error:', error);
        }
    }
    
    updateLocalStorage(table, updates) {
        try {
            const allData = JSON.parse(localStorage.getItem(this.storageKey)) || {};
            const current = allData[table] || [{}];
            allData[table] = [{ ...current[0], ...updates }];
            localStorage.setItem(this.storageKey, JSON.stringify(allData));
            return allData[table][0];
        } catch (error) {
            console.error('❌ LocalStorage update error:', error);
            return null;
        }
    }
    
    addToLocalStorage(table, item) {
        try {
            const allData = JSON.parse(localStorage.getItem(this.storageKey)) || {};
            const data = allData[table] || [];
            item.id = Date.now() + Math.random().toString(36).substr(2, 9);
            item.createdAt = new Date().toISOString();
            item.updatedAt = new Date().toISOString();
            data.push(item);
            allData[table] = data;
            localStorage.setItem(this.storageKey, JSON.stringify(allData));
            return item;
        } catch (error) {
            console.error('❌ LocalStorage add error:', error);
            return null;
        }
    }
    
    updateInLocalStorage(table, id, updates) {
        try {
            const allData = JSON.parse(localStorage.getItem(this.storageKey)) || {};
            const data = allData[table] || [];
            const index = data.findIndex(item => item.id === id);
            
            if (index !== -1) {
                data[index] = {
                    ...data[index],
                    ...updates,
                    updatedAt: new Date().toISOString()
                };
                allData[table] = data;
                localStorage.setItem(this.storageKey, JSON.stringify(allData));
                return data[index];
            }
            return null;
        } catch (error) {
            console.error('❌ LocalStorage update error:', error);
            return null;
        }
    }
    
    deleteFromLocalStorage(table, id) {
        try {
            const allData = JSON.parse(localStorage.getItem(this.storageKey)) || {};
            const data = allData[table] || [];
            allData[table] = data.filter(item => item.id !== id);
            localStorage.setItem(this.storageKey, JSON.stringify(allData));
            return true;
        } catch (error) {
            console.error('❌ LocalStorage delete error:', error);
            return false;
        }
    }
    
    // ========================================
    // Default Data Generators
    // ========================================
    
    getDefaultServices() {
        return [
            {
                id: 's1',
                name: 'FDM печать',
                slug: 'fdm',
                icon: 'fa-cube',
                description: 'Печать методом послойного наплавления. Идеально для прототипов и функциональных деталей.',
                features: [
                    'Быстрое изготовление',
                    'Низкая стоимость',
                    'Прочные детали',
                    'Широкий выбор материалов'
                ],
                price: 'от 50₽/г',
                active: true,
                featured: false,
                sort_order: 1
            },
            {
                id: 's2',
                name: 'SLA/SLS печать',
                slug: 'sla',
                icon: 'fa-gem',
                description: 'Высокоточная печать с невероятной детализацией для самых требовательных проектов.',
                features: [
                    'Высокая точность',
                    'Гладкая поверхность',
                    'Сложная геометрия',
                    'Идеально для ювелирки'
                ],
                price: 'от 200₽/г',
                active: true,
                featured: true,
                sort_order: 2
            },
            {
                id: 's3',
                name: 'Post-обработка',
                slug: 'post',
                icon: 'fa-cogs',
                description: 'Шлифовка, покраска, сборка. Доводим изделия до идеального состояния.',
                features: [
                    'Профессиональная покраска',
                    'Химическая обработка',
                    'Сборка узлов',
                    'Гарантия качества'
                ],
                price: 'от 300₽',
                active: true,
                featured: false,
                sort_order: 3
            },
            {
                id: 's4',
                name: '3D моделирование',
                slug: 'modeling',
                icon: 'fa-drafting-compass',
                description: 'Создание 3D моделей по вашим эскизам, чертежам или идеям.',
                features: [
                    'Опытные дизайнеры',
                    'Любая сложность',
                    'Быстрые правки',
                    'Оптимизация для печати'
                ],
                price: 'от 500₽/час',
                active: true,
                featured: false,
                sort_order: 4
            },
            {
                id: 's5',
                name: '3D сканирование',
                slug: 'scanning',
                icon: 'fa-scanner',
                description: 'Создание точных цифровых копий физических объектов.',
                features: [
                    'Точность до 0.05мм',
                    'Объекты любого размера',
                    'Обработка моделей',
                    'Быстрое выполнение'
                ],
                price: 'от 1000₽',
                active: true,
                featured: false,
                sort_order: 5
            },
            {
                id: 's6',
                name: 'Мелкосерийное производство',
                slug: 'production',
                icon: 'fa-industry',
                description: 'Изготовление партий деталей от 10 до 10000 штук.',
                features: [
                    'Скидки на объем',
                    'Контроль качества',
                    'Быстрые сроки',
                    'Упаковка и доставка'
                ],
                price: 'Индивидуально',
                active: true,
                featured: false,
                sort_order: 6
            }
        ];
    }
    
    getDefaultTestimonials() {
        return [
            {
                id: 't1',
                name: 'Алексей Иванов',
                position: 'Директор, Tech Solutions',
                avatar: 'https://i.pravatar.cc/150?img=1',
                rating: 5,
                text: 'Отличное качество печати! Заказывали прототипы корпусов для нашего устройства. Все выполнено точно в срок, консультации на высшем уровне.',
                approved: true,
                sort_order: 1
            },
            {
                id: 't2',
                name: 'Мария Петрова',
                position: 'Дизайнер',
                avatar: 'https://i.pravatar.cc/150?img=2',
                rating: 5,
                text: 'Работаю с этой компанией уже год. Печатают мои художественные проекты с невероятной детализацией. Рекомендую!',
                approved: true,
                sort_order: 2
            },
            {
                id: 't3',
                name: 'Дмитрий Сидоров',
                position: 'Инженер-конструктор',
                avatar: 'https://i.pravatar.cc/150?img=3',
                rating: 5,
                text: 'Профессиональный подход к каждому заказу. Помогли с оптимизацией моделей, что сэкономило время и деньги.',
                approved: true,
                sort_order: 3
            },
            {
                id: 't4',
                name: 'Елена Смирнова',
                position: 'Владелец бизнеса',
                avatar: 'https://i.pravatar.cc/150?img=4',
                rating: 5,
                text: 'Заказывала мелкую серию деталей - все изготовлено качественно, упаковано аккуратно. Очень довольна сотрудничеством!',
                approved: true,
                sort_order: 4
            }
        ];
    }
    
    getDefaultFAQ() {
        return [
            {
                id: 'faq1',
                question: 'Какие форматы файлов вы принимаете?',
                answer: 'Мы работаем с форматами STL, OBJ, 3MF, STEP. Если у вас файл в другом формате, свяжитесь с нами - мы найдем решение.',
                active: true,
                sort_order: 1
            },
            {
                id: 'faq2',
                question: 'Сколько времени занимает изготовление?',
                answer: 'Стандартный срок - 3-5 рабочих дней. Для небольших деталей возможна печать за 1 день. Есть услуга срочного изготовления (24 часа).',
                active: true,
                sort_order: 2
            },
            {
                id: 'faq3',
                question: 'Какая минимальная толщина стенок?',
                answer: 'Для FDM печати минимальная толщина - 1мм, для SLA/SLS - 0.5мм. Рекомендуем консультироваться перед печатью тонкостенных деталей.',
                active: true,
                sort_order: 3
            },
            {
                id: 'faq4',
                question: 'Можно ли заказать постобработку?',
                answer: 'Да, мы предлагаем шлифовку, покраску, химическую обработку, сборку. Все услуги можно выбрать в калькуляторе.',
                active: true,
                sort_order: 4
            },
            {
                id: 'faq5',
                question: 'Есть ли скидки на большие объемы?',
                answer: 'Да! При заказе от 10 деталей скидка 10%, от 50 деталей - 15%, от 100 деталей - индивидуальные условия.',
                active: true,
                sort_order: 5
            },
            {
                id: 'faq6',
                question: 'Как происходит оплата?',
                answer: 'Принимаем оплату по безналичному расчету, банковским картам, электронным кошелькам. Для юр.лиц работаем по договору с отсрочкой.',
                active: true,
                sort_order: 6
            }
        ];
    }
    
    getDefaultSettings() {
        return {
            siteName: '3D Print Pro',
            address: 'г. Омск',
            contactPhone: '+7 (XXX) XXX-XX-XX',
            contactEmail: 'info@3dprintpro.ru',
            workingHours: 'Пн-Пт: 10:00-18:00\nСб-Вс: 10:00-16:00',
            socialLinks: {
                telegram: CONFIG.telegram?.contactUrl || '',
                vk: '',
                whatsapp: '',
                youtube: ''
            },
            telegram_chat_id: ''
        };
    }
    
    getDefaultContent() {
        return {
            hero: {
                title: 'идеи в реальность',
                subtitle: 'Профессиональные услуги 3D печати в Омске'
            }
        };
    }
    
    getDefaultStats() {
        return {
            totalProjects: 1500,
            happyClients: 850,
            yearsExperience: 12,
            awards: 25
        };
    }
    
    // ========================================
    // Export/Import (LocalStorage based)
    // ========================================
    
    exportData() {
        const data = JSON.parse(localStorage.getItem(this.storageKey)) || {};
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `3dprintpro_backup_${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);
    }
    
    importData(jsonData) {
        try {
            const data = JSON.parse(jsonData);
            localStorage.setItem(this.storageKey, JSON.stringify(data));
            return true;
        } catch (error) {
            console.error('Import error:', error);
            return false;
        }
    }
}

// Create global instance
const db = new Database();
console.log('✅ Database initialized');
