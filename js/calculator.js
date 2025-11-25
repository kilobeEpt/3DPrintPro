// ========================================
// CALCULATOR CLASS
// ========================================

class Calculator {
    constructor() {
        this.data = {
            technology: 'fdm',
            material: 'pla',
            weight: 100,
            quantity: 1,
            infill: 20,
            quality: 'normal',
            additionalServices: {
                modeling: false,
                postProcessing: false,
                painting: false,
                express: false
            },
            file: null
        };
        
        this.calculation = null;
        this.apiConfig = null;
    }

    async init() {
        this.initInputs();
        this.loadConfigFromEmbedded();
        this.loadPricesFromConfig();
    }
    
    loadConfigFromEmbedded() {
        // Load from embedded window.CALCULATOR_CONFIG (set by PHP)
        if (window.CALCULATOR_CONFIG) {
            this.apiConfig = window.CALCULATOR_CONFIG;
            console.log('✅ Calculator config loaded from embedded data');
        } else {
            console.warn('⚠️ No calculator config found');
        }
    }

    // ========================================
    // INITIALIZATION
    // ========================================

    initInputs() {
        // Technology change
        const techSelect = document.getElementById('printTechnology');
        if (techSelect) {
            techSelect.addEventListener('change', (e) => {
                this.data.technology = e.target.value;
                this.updateMaterialOptions();
            });
        }
        
        // Material change
        const materialSelect = document.getElementById('material');
        if (materialSelect) {
            materialSelect.addEventListener('change', (e) => {
                this.data.material = e.target.value;
            });
        }
        
        // Weight
        const weightInput = document.getElementById('weight');
        if (weightInput) {
            weightInput.addEventListener('input', (e) => {
                const value = e.target.value.trim();
                if (value === '') {
                    this.data.weight = '';
                } else {
                    const numValue = parseFloat(value);
                    this.data.weight = Number.isFinite(numValue) ? numValue : '';
                    this.validateWeight(false);
                }
            });
            
            weightInput.addEventListener('blur', (e) => {
                this.validateWeight(true);
            });
        }
        
        // Quantity
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.addEventListener('input', (e) => {
                const value = e.target.value.trim();
                if (value === '') {
                    this.data.quantity = '';
                } else {
                    const numValue = parseInt(value);
                    this.data.quantity = Number.isFinite(numValue) ? numValue : '';
                    this.validateQuantity(false);
                }
            });
            
            quantityInput.addEventListener('blur', (e) => {
                this.validateQuantity(true);
            });
        }
        
        // Infill
        const infillSlider = document.getElementById('infill');
        const infillValue = document.getElementById('infillValue');
        if (infillSlider && infillValue) {
            infillSlider.addEventListener('input', (e) => {
                const value = e.target.value;
                infillValue.textContent = value;
                this.data.infill = parseInt(value);
            });
        }
        
        // Quality
        const qualitySelect = document.getElementById('quality');
        if (qualitySelect) {
            qualitySelect.addEventListener('change', (e) => {
                this.data.quality = e.target.value;
            });
        }
        
        // Additional services
        ['modeling', 'postProcessing', 'painting', 'express'].forEach(service => {
            const checkbox = document.getElementById(service);
            if (checkbox) {
                checkbox.addEventListener('change', (e) => {
                    this.data.additionalServices[service] = e.target.checked;
                });
            }
        });
    }

    validateWeight(applyMinClamp = true) {
        const input = document.getElementById('weight');
        const value = this.data.weight;
        
        // Allow empty during input
        if (value === '' || !Number.isFinite(value)) {
            if (applyMinClamp) {
                input.value = 1;
                this.data.weight = 1;
            }
            return;
        }
        
        // Clamp to max always
        if (value > 10000) {
            input.value = 10000;
            this.data.weight = 10000;
            app.showNotification('Максимальный вес - 10000г. Для больших заказов свяжитесь с нами.', 'warning');
        } else if (value < 1 && applyMinClamp) {
            input.value = 1;
            this.data.weight = 1;
        }
    }

    validateQuantity(applyMinClamp = true) {
        const input = document.getElementById('quantity');
        const value = this.data.quantity;
        
        // Allow empty during input
        if (value === '' || !Number.isFinite(value)) {
            if (applyMinClamp) {
                input.value = 1;
                this.data.quantity = 1;
            }
            return;
        }
        
        // Clamp to max always
        if (value > 1000) {
            input.value = 1000;
            this.data.quantity = 1000;
            app.showNotification('Для заказов более 1000 шт свяжитесь с нами напрямую.', 'warning');
        } else if (value < 1 && applyMinClamp) {
            input.value = 1;
            this.data.quantity = 1;
        }
    }

    // ========================================
    // LOAD PRICES (ИСПРАВЛЕНО #9)
    // ========================================

    loadPricesFromConfig() {
        // Загрузка цен из CONFIG (который уже загружен из БД)
        this.updateMaterialOptions();
        this.updateServicePrices();
    }

    updateMaterialOptions() {
        const materialSelect = document.getElementById('material');
        if (!materialSelect) return;
        
        let materials = [];
        
        if (this.apiConfig && this.apiConfig.materials) {
            // Use embedded config (from PHP) - materials is an object
            materials = Object.entries(this.apiConfig.materials)
                .filter(([key, mat]) => mat.technology === this.data.technology);
        }
        
        if (materials.length === 0) {
            materialSelect.innerHTML = '<option>Нет доступных материалов</option>';
            return;
        }
        
        materialSelect.innerHTML = materials.map(([key, mat]) => 
            `<option value="${key}" data-price="${mat.price}">${mat.name} (${mat.price}₽/г)</option>`
        ).join('');
        
        // Set first material as selected
        this.data.material = materials[0][0];
    }

    updateServicePrices() {
        const priceElements = document.querySelectorAll('.service-price');
        priceElements.forEach(el => {
            const serviceKey = el.getAttribute('data-service');
            let price = null;
            
            if (this.apiConfig && this.apiConfig.services && this.apiConfig.services[serviceKey]) {
                // Use embedded config (from PHP) - services is an object
                price = this.apiConfig.services[serviceKey].price;
            }
            
            if (price !== null) {
                el.textContent = price;
            }
        });
        
        console.log('✅ Цены услуг обновлены');
    }
    // Метод для обновления цен после изменения в админке
    reloadPrices() {
        console.log('🔄 Перезагрузка цен калькулятора...');
        CONFIG.loadFromDatabase();
        this.loadPricesFromConfig();
    
    // ДОБАВЛЕНО: принудительное обновление UI
        this.updateMaterialOptions();
        this.updateServicePrices();
    
        console.log('✅ Цены калькулятора обновлены');
    }
    // ========================================
    // CALCULATION
    // ========================================

    calculate() {
        const { weight, quantity, infill, quality } = this.data;
        
        // Validate inputs - check for empty or invalid values
        if (!Number.isFinite(weight) || weight <= 0 || weight === '') {
            app.showNotification('Пожалуйста, введите корректный вес модели', 'error');
            return null;
        }
        
        if (!Number.isFinite(quantity) || quantity <= 0 || quantity === '') {
            app.showNotification('Пожалуйста, введите корректное количество', 'error');
            return null;
        }
        
        // Get material price from embedded config
        let materialInfo = null;
        if (this.apiConfig && this.apiConfig.materials && this.apiConfig.materials[this.data.material]) {
            materialInfo = this.apiConfig.materials[this.data.material];
        }
        
        if (!materialInfo) {
            app.showNotification('Материал не найден', 'error');
            return null;
        }
        
        const materialPricePerGram = materialInfo.price;
        
        // Calculate material cost
        const infillFactor = 0.3 + (infill / 100 * 0.7); // 30% base + up to 70% variable
        let materialCost = weight * materialPricePerGram * infillFactor;
        
        // Labor cost
        let laborCost = 500; // Base cost
        laborCost += weight * 2; // Additional for larger parts
        
        // Quality multiplier
        let qualityInfo = null;
        if (this.apiConfig && this.apiConfig.quality && this.apiConfig.quality[quality]) {
            qualityInfo = this.apiConfig.quality[quality];
        }
        const qualityMultiplier = qualityInfo ? qualityInfo.multiplier : 1;
        laborCost = laborCost * qualityMultiplier;
        
        // Multiply by quantity
        const subtotal = (materialCost + laborCost) * quantity;
        
        // Additional services
        let additionalCost = 0;
        Object.entries(this.data.additionalServices).forEach(([serviceKey, enabled]) => {
            if (!enabled) return;
            
            let serviceInfo = null;
            if (this.apiConfig && this.apiConfig.services && this.apiConfig.services[serviceKey]) {
                serviceInfo = this.apiConfig.services[serviceKey];
            }
            
            if (serviceInfo) {
                const price = serviceInfo.price;
                const unit = serviceInfo.unit;
                
                // Count per item
                additionalCost += price * quantity;
            }
        });
        
        // Discounts
        let discount = 0;
        const discountInfo = this.getDiscount(quantity);
        if (discountInfo) {
            discount = subtotal * (discountInfo.percent / 100);
        }
        
        // Total
        const total = Math.round(subtotal + additionalCost - discount);
        
        // Estimate time
        const timeInfo = qualityInfo ? qualityInfo.time : 1;
        let hours = (weight / 10) * timeInfo * quantity;
        
        if (this.data.additionalServices.express) {
            hours = Math.min(hours, 24);
        }
        
        const days = Math.ceil(hours / 8);
        let timeEstimate = days === 1 ? '1 день' : `${days} дня`;
        
        if (this.data.additionalServices.express) {
            timeEstimate = '24 часа';
        }
        
        // Save calculation
        this.calculation = {
            materialCost: Math.round(materialCost * quantity),
            laborCost: Math.round(laborCost * quantity),
            additionalCost: Math.round(additionalCost),
            discount: Math.round(discount),
            discountPercent: discountInfo ? discountInfo.percent : 0,
            total,
            timeEstimate,
            service: this.getServiceName(),
            details: this.getCalculationDetails(),
            // Сохраняем исходные данные для отправки в заказе
            technology: this.data.technology,
            material: materialInfo.name,
            weight: this.data.weight,
            quantity: this.data.quantity,
            infill: this.data.infill,
            quality: qualityInfo.name
        };
        
        return this.calculation;
    }

    getDiscount(quantity) {
        let discounts = [];
        if (this.apiConfig && this.apiConfig.discounts) {
            discounts = this.apiConfig.discounts.filter(d => d.active !== false);
        } else if (typeof CONFIG !== 'undefined') {
            discounts = CONFIG.discounts;
        }
        discounts = discounts.sort((a, b) => b.minQuantity - a.minQuantity);
        return discounts.find(d => quantity >= d.minQuantity);
    }

    getServiceName() {
        const tech = this.data.technology.toUpperCase();
        let materialName = this.data.material;
        
        if (this.apiConfig && this.apiConfig.materials && this.apiConfig.materials[this.data.material]) {
            materialName = this.apiConfig.materials[this.data.material].name;
        }
        
        return `${tech} печать (${materialName})`;
    }

    getCalculationDetails() {
        let materialName = this.data.material;
        let qualityName = this.data.quality;
        
        if (this.apiConfig) {
            if (this.apiConfig.materials && this.apiConfig.materials[this.data.material]) {
                materialName = this.apiConfig.materials[this.data.material].name;
            }
            if (this.apiConfig.quality && this.apiConfig.quality[this.data.quality]) {
                qualityName = this.apiConfig.quality[this.data.quality].name;
            }
        }
        
        const details = [
            `Технология: ${this.data.technology.toUpperCase()}`,
            `Материал: ${materialName}`,
            `Вес: ${this.data.weight}г`,
            `Количество: ${this.data.quantity} шт`,
            `Заполнение: ${this.data.infill}%`,
            `Качество: ${qualityName}`
        ];
        
        const services = [];
        Object.entries(this.data.additionalServices).forEach(([key, enabled]) => {
            if (!enabled) return;
            
            if (this.apiConfig && this.apiConfig.services) {
                const service = this.apiConfig.services.find(s => s.key === key);
                if (service) {
                    services.push(service.name);
                }
            } else if (typeof CONFIG !== 'undefined' && CONFIG.servicePrices[key]) {
                services.push(CONFIG.servicePrices[key].name);
            }
        });
        
        if (services.length > 0) {
            details.push(`Услуги: ${services.join(', ')}`);
        }
        
        return details.join('\n');
    }

    // ========================================
    // UI UPDATE
    // ========================================

    updateUI() {
        if (!this.calculation) return;
        
        const { materialCost, laborCost, additionalCost, discount, total, timeEstimate } = this.calculation;
        
        // Update breakdown
        document.getElementById('materialCost').textContent = materialCost.toLocaleString('ru-RU') + '₽';
        document.getElementById('laborCost').textContent = laborCost.toLocaleString('ru-RU') + '₽';
        document.getElementById('additionalCost').textContent = additionalCost.toLocaleString('ru-RU') + '₽';
        document.getElementById('totalPrice').textContent = total.toLocaleString('ru-RU') + '₽';
        document.getElementById('estimateTime').textContent = timeEstimate;
        
        // Show/hide discount
        const discountItem = document.getElementById('discountItem');
        if (discount > 0) {
            discountItem.style.display = 'flex';
            document.getElementById('discountAmount').textContent = '-' + discount.toLocaleString('ru-RU') + '₽';
        } else {
            discountItem.style.display = 'none';
        }
        
        // Animate result card
        this.animateResult();
    }

    animateResult() {
        const resultCard = document.querySelector('.result-card');
        if (resultCard) {
            resultCard.style.animation = 'none';
            setTimeout(() => {
                resultCard.style.animation = 'pulse 0.5s ease';
            }, 10);
        }
    }

    // ========================================
    // PUBLIC METHODS
    // ========================================

    getCalculationData() {
        return this.calculation;
    }

    getData() {
        return this.data;
    }

    reset() {
        this.data = {
            technology: 'fdm',
            material: 'pla',
            weight: 100,
            quantity: 1,
            infill: 20,
            quality: 'normal',
            additionalServices: {
                modeling: false,
                postProcessing: false,
                painting: false,
                express: false
            },
            file: null
        };
        
        this.calculation = null;
        
        // Reset UI
        document.getElementById('printTechnology').value = 'fdm';
        document.getElementById('weight').value = 100;
        document.getElementById('quantity').value = 1;
        document.getElementById('infill').value = 20;
        document.getElementById('infillValue').textContent = 20;
        document.getElementById('quality').value = 'normal';
        
        document.querySelectorAll('.checkbox-group input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });
        
        this.updateMaterialOptions();
    }
}

// ========================================
// GLOBAL CALCULATOR INSTANCE
// ========================================

const calculator = new Calculator();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    calculator.init();
});

// Global function for calculate button
function calculatePrice() {
    const result = calculator.calculate();
    
    if (result) {
        calculator.updateUI();
        app.showNotification('Расчет выполнен успешно', 'success');
    }
}

// Technology change handler
document.getElementById('printTechnology')?.addEventListener('change', () => {
    calculator.updateMaterialOptions();
});
