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
    }

    init() {
        this.initInputs();
        this.loadPricesFromConfig(); // ИСПРАВЛЕНО: загрузка из CONFIG
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
                this.data.weight = parseFloat(e.target.value) || 0;
                this.validateWeight();
            });
        }
        
        // Quantity
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.addEventListener('input', (e) => {
                this.data.quantity = parseInt(e.target.value) || 1;
                this.validateQuantity();
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

    validateWeight() {
        const input = document.getElementById('weight');
        const value = this.data.weight;
        
        if (value < 1) {
            input.value = 1;
            this.data.weight = 1;
        } else if (value > 10000) {
            input.value = 10000;
            this.data.weight = 10000;
            app.showNotification('Максимальный вес - 10000г. Для больших заказов свяжитесь с нами.', 'warning');
        }
    }

    validateQuantity() {
        const input = document.getElementById('quantity');
        const value = this.data.quantity;
        
        if (value < 1) {
            input.value = 1;
            this.data.quantity = 1;
        } else if (value > 1000) {
            input.value = 1000;
            this.data.quantity = 1000;
            app.showNotification('Для заказов более 1000 шт свяжитесь с нами напрямую.', 'warning');
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
        
        const materials = Object.entries(CONFIG.materialPrices)
            .filter(([key, mat]) => mat.technology === this.data.technology);
        
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
        const service = el.getAttribute('data-service');
        if (CONFIG.servicePrices[service]) {
            el.textContent = CONFIG.servicePrices[service].price;
        }
         });
    
        console.log('✅ Цены услуг обновлены:', CONFIG.servicePrices);
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
        
        // Validate inputs
        if (weight <= 0 || quantity <= 0) {
            app.showNotification('Пожалуйста, введите корректные значения', 'error');
            return null;
        }
        
        // Get material price from CONFIG
        const materialInfo = CONFIG.materialPrices[this.data.material];
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
        const qualityInfo = CONFIG.qualityMultipliers[quality];
        const qualityMultiplier = qualityInfo ? qualityInfo.multiplier : 1;
        laborCost = laborCost * qualityMultiplier;
        
        // Multiply by quantity
        const subtotal = (materialCost + laborCost) * quantity;
        
        // Additional services
        let additionalCost = 0;
        Object.entries(this.data.additionalServices).forEach(([service, enabled]) => {
            if (enabled && CONFIG.servicePrices[service]) {
                const price = CONFIG.servicePrices[service].price;
                const unit = CONFIG.servicePrices[service].unit;
                
                if (unit === 'шт') {
                    additionalCost += price * quantity;
                } else {
                    additionalCost += price;
                }
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
        const discounts = CONFIG.discounts.sort((a, b) => b.minQuantity - a.minQuantity);
        return discounts.find(d => quantity >= d.minQuantity);
    }

    getServiceName() {
        const tech = this.data.technology.toUpperCase();
        const material = CONFIG.materialPrices[this.data.material]?.name || this.data.material;
        return `${tech} печать (${material})`;
    }

    getCalculationDetails() {
        const details = [
            `Технология: ${this.data.technology.toUpperCase()}`,
            `Материал: ${CONFIG.materialPrices[this.data.material]?.name}`,
            `Вес: ${this.data.weight}г`,
            `Количество: ${this.data.quantity} шт`,
            `Заполнение: ${this.data.infill}%`,
            `Качество: ${CONFIG.qualityMultipliers[this.data.quality]?.name}`
        ];
        
        const services = [];
        Object.entries(this.data.additionalServices).forEach(([key, enabled]) => {
            if (enabled && CONFIG.servicePrices[key]) {
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