// ========================================
// DATABASE MANAGEMENT (LocalStorage)
// ========================================

class Database {
    constructor() {
        this.storageKey = '3dprintpro_data';
        this.init();
    }
    
    init() {
        if (!localStorage.getItem(this.storageKey)) {
            this.resetToDefault();
        }
    }
    
    resetToDefault() {
        const defaultData = {
            orders: [],
            portfolio: [],
            services: this.getDefaultServices(),
            testimonials: this.getDefaultTestimonials(),
            faq: this.getDefaultFAQ(),
            settings: [this.getDefaultSettings()], // ИСПРАВЛЕНО: массив с одним объектом
            content: [this.getDefaultContent()],   // ИСПРАВЛЕНО: массив с одним объектом
            stats: this.getDefaultStats()
        };
        
        this.saveAll(defaultData);
    }
    
    // CRUD Operations
    getData(table) {
        const data = JSON.parse(localStorage.getItem(this.storageKey));
        return data ? data[table] || [] : [];
    }
    
    getAllData() {
        return JSON.parse(localStorage.getItem(this.storageKey)) || {};
    }
    
    saveData(table, data) {
        const allData = this.getAllData();
        allData[table] = data;
        this.saveAll(allData);
    }
    
    saveAll(data) {
        localStorage.setItem(this.storageKey, JSON.stringify(data));
    }
    
    addItem(table, item) {
        const data = this.getData(table);
        item.id = this.generateId();
        item.createdAt = new Date().toISOString();
        item.updatedAt = new Date().toISOString();
        data.push(item);
        this.saveData(table, data);
        return item;
    }
    
    updateItem(table, id, updates) {
        const data = this.getData(table);
        const index = data.findIndex(item => item.id === id);
        
        if (index !== -1) {
            data[index] = {
                ...data[index],
                ...updates,
                updatedAt: new Date().toISOString()
            };
            this.saveData(table, data);
            return data[index];
        }
        return null;
    }
    
    deleteItem(table, id) {
        const data = this.getData(table);
        const filtered = data.filter(item => item.id !== id);
        this.saveData(table, filtered);
        return filtered.length < data.length;
    }
    
    getItem(table, id) {
        const data = this.getData(table);
        return data.find(item => item.id === id);
    }
    
    generateId() {
        return Date.now() + Math.random().toString(36).substr(2, 9);
    }
    
    // НОВОЕ: Безопасное получение/создание settings
    getOrCreateSettings() {
        let settings = this.getData('settings');
        
        if (!settings || settings.length === 0) {
            const defaultSettings = this.getDefaultSettings();
            defaultSettings.id = this.generateId();
            defaultSettings.createdAt = new Date().toISOString();
            defaultSettings.updatedAt = new Date().toISOString();
            this.saveData('settings', [defaultSettings]);
            return defaultSettings;
        }
        
        return settings[0];
    }
    
    // НОВОЕ: Обновление settings
    updateSettings(updates) {
        let settings = this.getOrCreateSettings();
        
        const updated = {
            ...settings,
            ...updates,
            updatedAt: new Date().toISOString()
        };
        
        this.saveData('settings', [updated]);
        return updated;
    }
    
    // НОВОЕ: Безопасное получение/создание content
    getOrCreateContent() {
        let content = this.getData('content');
        
        if (!content || content.length === 0) {
            const defaultContent = this.getDefaultContent();
            defaultContent.id = this.generateId();
            defaultContent.createdAt = new Date().toISOString();
            defaultContent.updatedAt = new Date().toISOString();
            this.saveData('content', [defaultContent]);
            return defaultContent;
        }
        
        return content[0];
    }
    
    // НОВОЕ: Обновление content
    updateContent(updates) {
        let content = this.getOrCreateContent();
        
        const updated = {
            ...content,
            ...updates,
            updatedAt: new Date().toISOString()
        };
        
        this.saveData('content', [updated]);
        return updated;
    }
    
    // Default data generators
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
                order: 1
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
                order: 2
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
                order: 3
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
                order: 4
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
                order: 5
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
                order: 6
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
                order: 1
            },
            {
                id: 't2',
                name: 'Мария Петрова',
                position: 'Дизайнер',
                avatar: 'https://i.pravatar.cc/150?img=2',
                rating: 5,
                text: 'Работаю с этой компанией уже год. Печатают мои художественные проекты с невероятной детализацией. Рекомендую!',
                approved: true,
                order: 2
            },
            {
                id: 't3',
                name: 'Дмитрий Сидоров',
                position: 'Инженер-конструктор',
                avatar: 'https://i.pravatar.cc/150?img=3',
                rating: 5,
                text: 'Профессиональный подход к каждому заказу. Помогли с оптимизацией моделей, что сэкономило время и деньги.',
                approved: true,
                order: 3
            },
            {
                id: 't4',
                name: 'Елена Смирнова',
                position: 'Владелец бизнеса',
                avatar: 'https://i.pravatar.cc/150?img=4',
                rating: 5,
                text: 'Заказывала мелкую серию деталей - все изготовлено качественно, упаковано аккуратно. Очень довольна сотрудничеством!',
                approved: true,
                order: 4
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
                order: 1
            },
            {
                id: 'faq2',
                question: 'Сколько времени занимает изготовление?',
                answer: 'Стандартный срок - 3-5 рабочих дней. Для небольших деталей возможна печать за 1 день. Есть услуга срочного изготовления (24 часа).',
                active: true,
                order: 2
            },
            {
                id: 'faq3',
                question: 'Какая минимальная толщина стенок?',
                answer: 'Для FDM печати минимальная толщина - 1мм, для SLA/SLS - 0.5мм. Рекомендуем консультироваться перед печатью тонкостенных деталей.',
                active: true,
                order: 3
            },
            {
                id: 'faq4',
                question: 'Можно ли заказать постобработку?',
                answer: 'Да, мы предлагаем шлифовку, покраску, химическую обработку, сборку. Все услуги можно выбрать в калькуляторе.',
                active: true,
                order: 4
            },
            {
                id: 'faq5',
                question: 'Есть ли скидки на большие объемы?',
                answer: 'Да! При заказе от 10 деталей скидка 10%, от 50 деталей - 15%, от 100 деталей - индивидуальные условия.',
                active: true,
                order: 5
            },
            {
                id: 'faq6',
                question: 'Как происходит оплата?',
                answer: 'Принимаем оплату по безналичному расчету, банковским картам, электронным кошелькам. Для юр.лиц работаем по договору с отсрочкой.',
                active: true,
                order: 6
            }
        ];
    }
    
getDefaultSettings() {
    return {
        siteName: '3D Print Pro',
        siteDescription: 'Профессиональная 3D печать любой сложности',
        contactEmail: 'info@3dprintpro.ru',
        contactPhone: '+7 (999) 123-45-67',
        address: 'г. Москва, ул. Примерная, д. 123',
        workingHours: 'Пн-Пт: 9:00 - 18:00\nСб-Вс: Выходной',
        timezone: 'Europe/Moscow',
        socialLinks: {
            vk: '',
            telegram: 'https://t.me/PrintPro_Omsk',
            whatsapp: '',
            youtube: ''
        },
        theme: 'light',
        colorPrimary: '#6366f1',
        colorSecondary: '#ec4899',
        notifications: {
            newOrders: true,
            newReviews: true,
            newMessages: true
        },
        telegram: {
            chatId: ''
        },
        telegramNotifications: true,
        formFields: CONFIG.formFields,
        calculator: {
            materialPrices: CONFIG.materialPrices,
            servicePrices: CONFIG.servicePrices,
            qualityMultipliers: CONFIG.qualityMultipliers,
            discounts: CONFIG.discounts
        }
    };
}
    
    getDefaultContent() {
        return {
            hero: {
                title: 'идеи в реальность',
                subtitle: 'Профессиональная 3D печать любой сложности. Быстро, качественно, доступно.',
                features: [
                    'Печать от 1 часа',
                    '15+ материалов',
                    'Гарантия качества'
                ]
            },
            about: {
                title: 'Лидеры в области 3D печати',
                description: 'Мы - команда профессионалов с более чем 12-летним опытом в области аддитивных технологий. Наша миссия - делать 3D печать доступной и качественной для каждого.',
                features: [
                    {
                        title: 'Современное оборудование',
                        description: 'Работаем на принтерах последнего поколения'
                    },
                    {
                        title: 'Опытная команда',
                        description: '15 специалистов с профильным образованием'
                    },
                    {
                        title: 'Гарантия качества',
                        description: 'Все изделия проходят контроль качества'
                    }
                ]
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
    
    // Search functionality
    search(table, query) {
        const data = this.getData(table);
        const lowerQuery = query.toLowerCase();
        
        return data.filter(item => {
            return Object.values(item).some(value => {
                if (typeof value === 'string') {
                    return value.toLowerCase().includes(lowerQuery);
                }
                return false;
            });
        });
    }
    
    // Export/Import
    exportData() {
        const data = this.getAllData();
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
            this.saveAll(data);
            return true;
        } catch (error) {
            console.error('Import error:', error);
            return false;
        }
    }
}

// Create global instance
const db = new Database();