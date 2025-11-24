// ========================================
// GLOBAL CONFIGURATION
// ========================================

const CONFIG = {
    // Site settings
    siteName: '3D Print Pro',
    siteUrl: 'https://3dprint-omsk.ru',
    
    // Telegram Bot
    telegram: {
        botToken: '8241807858:AAE0JXxWO9HumqesNK6x_vvaMrxvRK9qKBI',
        chatId: '', // Заполнить после получения chat_id
        apiUrl: 'https://api.telegram.org/bot',
        contactUrl: 'https://t.me/PrintPro_Omsk'
    },
    
    // Material prices
    materialPrices: {
        pla: { name: 'PLA', price: 50, technology: 'fdm' },
        abs: { name: 'ABS', price: 60, technology: 'fdm' },
        petg: { name: 'PETG', price: 70, technology: 'fdm' },
        nylon: { name: 'Nylon', price: 120, technology: 'fdm' },
        tpu: { name: 'TPU (Flex)', price: 150, technology: 'fdm' },
        standard_resin: { name: 'Standard Resin', price: 200, technology: 'sla' },
        tough_resin: { name: 'Tough Resin', price: 250, technology: 'sla' },
        flexible_resin: { name: 'Flexible Resin', price: 280, technology: 'sla' },
        pa12: { name: 'PA12 Nylon', price: 150, technology: 'sls' },
        tpu_sls: { name: 'TPU SLS', price: 180, technology: 'sls' }
    },
    
    // Service prices
    servicePrices: {
        modeling: { name: '3D моделирование', price: 500, unit: 'час' },
        postProcessing: { name: 'Постобработка', price: 300, unit: 'шт' },
        painting: { name: 'Покраска', price: 500, unit: 'шт' },
        express: { name: 'Срочное изготовление', price: 1000, unit: 'заказ' }
    },
    
    // Quality multipliers
    qualityMultipliers: {
        draft: { name: 'Черновое', multiplier: 0.8, time: 0.7 },
        normal: { name: 'Нормальное', multiplier: 1.0, time: 1.0 },
        high: { name: 'Высокое', multiplier: 1.3, time: 1.4 },
        ultra: { name: 'Ультра', multiplier: 1.6, time: 2.0 }
    },
    
    // Discounts
    discounts: [
        { minQuantity: 10, percent: 10 },
        { minQuantity: 50, percent: 15 },
        { minQuantity: 100, percent: 20 }
    ],
    
    // Form fields configuration
formFields: {
    contact: [
        { 
            name: 'name', 
            label: 'Ваше имя', 
            type: 'text', 
            required: true, 
            enabled: true, 
            placeholder: 'Иван Петров',
            order: 1
        },
        { 
            name: 'email', 
            label: 'Email', 
            type: 'email', 
            required: true, 
            enabled: true, 
            placeholder: 'example@mail.com',
            order: 2
        },
        { 
            name: 'phone', 
            label: 'Телефон', 
            type: 'tel', 
            required: true, 
            enabled: true, 
            placeholder: '+7 (999) 123-45-67',
            order: 3
        },
        { 
            name: 'telegram', 
            label: 'Telegram', 
            type: 'text', 
            required: false, 
            enabled: true, 
            placeholder: '@username',
            order: 4
        },
        { 
            name: 'subject', 
            label: 'Тема обращения', 
            type: 'select', 
            required: false, 
            enabled: true, 
            placeholder: 'Выберите тему',
            order: 5,
            options: [
                'Расчет стоимости',
                'Консультация',
                'Партнерство',
                'Другое'
            ]
        },
        { 
            name: 'message', 
            label: 'Ваше сообщение', 
            type: 'textarea', 
            required: true, 
            enabled: true, 
            placeholder: 'Опишите ваш заказ...',
            order: 6
        }
    ],
    order: []
},
    
    // File upload
    maxFileSize: 10 * 1024 * 1024, // 10MB
    allowedFileTypes: ['.stl', '.obj', '.3mf', '.step', '.stp'],
    
    // Pagination
    itemsPerPage: 10,
    
    // Features flags
    features: {
        telegramNotifications: true,
        emailNotifications: false
    },
    
    // Метод для загрузки настроек из базы
    loadFromDatabase() {
        console.log('🔄 Загрузка CONFIG из базы данных...');
        
        if (typeof db === 'undefined') {
            console.warn('⚠️ Database не инициализирована');
            return;
        }
        
        const settings = db.getOrCreateSettings();
        
        console.log('📦 Загруженные settings:', settings);
        
        if (settings && settings.calculator) {
            if (settings.calculator.materialPrices) {
                this.materialPrices = settings.calculator.materialPrices;
                console.log('✅ Цены материалов загружены');
            }
            if (settings.calculator.servicePrices) {
                this.servicePrices = settings.calculator.servicePrices;
                console.log('✅ Цены услуг загружены');
            }
            if (settings.calculator.qualityMultipliers) {
                this.qualityMultipliers = settings.calculator.qualityMultipliers;
                console.log('✅ Множители качества загружены');
            }
            if (settings.calculator.discounts) {
                this.discounts = settings.calculator.discounts;
                console.log('✅ Скидки загружены');
            }
        }
        
        if (settings && settings.telegram) {
            this.telegram.chatId = settings.telegram.chatId || '';
            console.log('✅ Telegram Chat ID загружен:', this.telegram.chatId);
        }
        
        if (settings && settings.telegramNotifications !== undefined) {
            this.features.telegramNotifications = settings.telegramNotifications;
            console.log('✅ Telegram уведомления:', this.features.telegramNotifications);
        }
        
        if (settings && settings.formFields) {
            this.formFields = settings.formFields;
            console.log('✅ Поля форм загружены');
        }
        
        console.log('✅ CONFIG загружен из БД');
    }
};

// Загрузка настроек при старте
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        if (typeof db !== 'undefined') {
            CONFIG.loadFromDatabase();
            console.log('✅ CONFIG загружен из БД');
            
            if (typeof calculator !== 'undefined' && calculator.loadPricesFromConfig) {
                calculator.loadPricesFromConfig();
                console.log('✅ Цены калькулятора обновлены из БД');
            }
            
            // ДОБАВЛЕНО: Обновляем форму после загрузки CONFIG
            if (typeof app !== 'undefined' && app.renderDynamicFormFields) {
                app.renderDynamicFormFields();
                console.log('✅ Форма обновлена после загрузки CONFIG из БД');
            }
        }
    }, 500);
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = CONFIG;
}