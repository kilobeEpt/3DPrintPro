/**
 * Calculator Settings Module
 * 
 * Manages calculator configuration in the admin panel.
 */

class CalculatorSettingsModule {
    constructor() {
        this.config = {
            materials: [],
            services: [],
            quality_multipliers: {},
            discounts: [],
            formulas: {},
            validation: {}
        };
        
        this.hasChanges = false;
    }
    
    async init() {
        console.log('Initializing Calculator Settings Module');
        
        // Load configuration
        await this.loadConfig();
        
        // Initialize tabs
        this.initTabs();
        
        // Initialize event listeners
        this.initEventListeners();
        
        // Render all sections
        this.renderMaterials();
        this.renderServices();
        this.renderQuality();
        this.renderDiscounts();
        this.renderFormulas();
        this.renderSandbox();
    }
    
    initTabs() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabName = btn.getAttribute('data-tab');
                
                // Remove active class from all tabs
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab
                btn.classList.add('active');
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });
    }
    
    initEventListeners() {
        // Save all button
        document.getElementById('saveAllBtn').addEventListener('click', () => this.saveAll());
        
        // Material events
        document.getElementById('addMaterialBtn').addEventListener('click', () => this.openMaterialModal());
        
        // Service events
        document.getElementById('addServiceBtn').addEventListener('click', () => this.openServiceModal());
        
        // Discount events
        document.getElementById('addDiscountBtn').addEventListener('click', () => this.openDiscountModal());
        
        // Sandbox events
        document.getElementById('testTechnology').addEventListener('change', () => this.updateSandboxMaterials());
        document.getElementById('testInfill').addEventListener('input', (e) => {
            document.getElementById('testInfillValue').textContent = e.target.value + '%';
        });
        document.getElementById('runTestBtn').addEventListener('click', () => this.runTest());
        
        // Formula validation
        document.getElementById('validateFormulaBtn')?.addEventListener('click', () => this.validateFormula());
        
        // Warn on unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (this.hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }
    
    async loadConfig() {
        try {
            const response = await window.adminApi.get('/api/calculator-settings.php?admin=1');
            
            if (response.success) {
                this.config = response.data;
                console.log('Config loaded:', this.config);
            } else {
                window.showNotification('Ошибка загрузки настроек', 'error');
            }
        } catch (error) {
            console.error('Failed to load config:', error);
            window.showNotification('Ошибка загрузки настроек', 'error');
        }
    }
    
    // ========================================
    // MATERIALS
    // ========================================
    
    renderMaterials() {
        const container = document.getElementById('materialsList');
        
        if (!this.config.materials || this.config.materials.length === 0) {
            container.innerHTML = '<p class="text-muted">Нет материалов</p>';
            return;
        }
        
        // Group by technology
        const grouped = {
            fdm: [],
            sla: [],
            sls: []
        };
        
        this.config.materials.forEach((material, index) => {
            material._index = index;
            grouped[material.technology].push(material);
        });
        
        let html = '';
        
        Object.entries(grouped).forEach(([tech, materials]) => {
            if (materials.length > 0) {
                const techName = tech.toUpperCase();
                html += `<h4>${techName}</h4>`;
                
                materials.forEach(material => {
                    const activeClass = material.active ? '' : 'config-item-inactive';
                    html += `
                        <div class="config-item ${activeClass}">
                            <div class="config-item-header">
                                <div class="config-item-title">${material.name}</div>
                                <div class="config-item-actions">
                                    <button class="btn btn-sm btn-secondary" onclick="window.calculatorSettings.editMaterial(${material._index})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="window.calculatorSettings.deleteMaterial(${material._index})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="config-item-details">
                                <div class="config-item-field">
                                    <div class="config-item-label">Ключ</div>
                                    <div class="config-item-value">${material.key}</div>
                                </div>
                                <div class="config-item-field">
                                    <div class="config-item-label">Цена</div>
                                    <div class="config-item-value">${material.price} ₽/г</div>
                                </div>
                                <div class="config-item-field">
                                    <div class="config-item-label">Статус</div>
                                    <div class="config-item-value">${material.active ? 'Активен' : 'Неактивен'}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
        });
        
        container.innerHTML = html;
    }
    
    openMaterialModal(index = null) {
        const modal = document.getElementById('materialModal');
        const form = document.getElementById('materialForm');
        form.reset();
        
        if (index !== null) {
            const material = this.config.materials[index];
            document.getElementById('materialModalTitle').textContent = 'Редактировать материал';
            document.getElementById('materialIndex').value = index;
            document.getElementById('materialKey').value = material.key;
            document.getElementById('materialName').value = material.name;
            document.getElementById('materialPrice').value = material.price;
            document.getElementById('materialTechnology').value = material.technology;
            document.getElementById('materialActive').checked = material.active !== false;
        } else {
            document.getElementById('materialModalTitle').textContent = 'Добавить материал';
            document.getElementById('materialIndex').value = '';
        }
        
        modal.style.display = 'flex';
    }
    
    closeMaterialModal() {
        document.getElementById('materialModal').style.display = 'none';
    }
    
    saveMaterial() {
        const index = document.getElementById('materialIndex').value;
        const material = {
            key: document.getElementById('materialKey').value,
            name: document.getElementById('materialName').value,
            price: parseFloat(document.getElementById('materialPrice').value),
            technology: document.getElementById('materialTechnology').value,
            active: document.getElementById('materialActive').checked,
            order: index !== '' ? this.config.materials[index].order : this.config.materials.length + 1
        };
        
        if (index !== '') {
            this.config.materials[index] = material;
        } else {
            this.config.materials.push(material);
        }
        
        this.hasChanges = true;
        this.renderMaterials();
        this.closeMaterialModal();
        window.showNotification('Материал сохранен. Не забудьте сохранить все изменения!', 'info');
    }
    
    editMaterial(index) {
        this.openMaterialModal(index);
    }
    
    deleteMaterial(index) {
        if (confirm('Удалить этот материал?')) {
            this.config.materials.splice(index, 1);
            this.hasChanges = true;
            this.renderMaterials();
            window.showNotification('Материал удален. Не забудьте сохранить все изменения!', 'info');
        }
    }
    
    // ========================================
    // SERVICES
    // ========================================
    
    renderServices() {
        const container = document.getElementById('servicesList');
        
        if (!this.config.services || this.config.services.length === 0) {
            container.innerHTML = '<p class="text-muted">Нет услуг</p>';
            return;
        }
        
        let html = '';
        
        this.config.services.forEach((service, index) => {
            const activeClass = service.active ? '' : 'config-item-inactive';
            html += `
                <div class="config-item ${activeClass}">
                    <div class="config-item-header">
                        <div class="config-item-title">${service.name}</div>
                        <div class="config-item-actions">
                            <button class="btn btn-sm btn-secondary" onclick="window.calculatorSettings.editService(${index})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="window.calculatorSettings.deleteService(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="config-item-details">
                        <div class="config-item-field">
                            <div class="config-item-label">Ключ</div>
                            <div class="config-item-value">${service.key}</div>
                        </div>
                        <div class="config-item-field">
                            <div class="config-item-label">Цена</div>
                            <div class="config-item-value">${service.price} ₽/${service.unit}</div>
                        </div>
                        <div class="config-item-field">
                            <div class="config-item-label">Статус</div>
                            <div class="config-item-value">${service.active ? 'Активна' : 'Неактивна'}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    openServiceModal(index = null) {
        const modal = document.getElementById('serviceModal');
        const form = document.getElementById('serviceForm');
        form.reset();
        
        if (index !== null) {
            const service = this.config.services[index];
            document.getElementById('serviceModalTitle').textContent = 'Редактировать услугу';
            document.getElementById('serviceIndex').value = index;
            document.getElementById('serviceKey').value = service.key;
            document.getElementById('serviceName').value = service.name;
            document.getElementById('servicePrice').value = service.price;
            document.getElementById('serviceUnit').value = service.unit;
            document.getElementById('serviceActive').checked = service.active !== false;
        } else {
            document.getElementById('serviceModalTitle').textContent = 'Добавить услугу';
            document.getElementById('serviceIndex').value = '';
        }
        
        modal.style.display = 'flex';
    }
    
    closeServiceModal() {
        document.getElementById('serviceModal').style.display = 'none';
    }
    
    saveService() {
        const index = document.getElementById('serviceIndex').value;
        const service = {
            key: document.getElementById('serviceKey').value,
            name: document.getElementById('serviceName').value,
            price: parseFloat(document.getElementById('servicePrice').value),
            unit: document.getElementById('serviceUnit').value,
            active: document.getElementById('serviceActive').checked,
            order: index !== '' ? this.config.services[index].order : this.config.services.length + 1
        };
        
        if (index !== '') {
            this.config.services[index] = service;
        } else {
            this.config.services.push(service);
        }
        
        this.hasChanges = true;
        this.renderServices();
        this.closeServiceModal();
        window.showNotification('Услуга сохранена. Не забудьте сохранить все изменения!', 'info');
    }
    
    editService(index) {
        this.openServiceModal(index);
    }
    
    deleteService(index) {
        if (confirm('Удалить эту услугу?')) {
            this.config.services.splice(index, 1);
            this.hasChanges = true;
            this.renderServices();
            window.showNotification('Услуга удалена. Не забудьте сохранить все изменения!', 'info');
        }
    }
    
    // ========================================
    // QUALITY
    // ========================================
    
    renderQuality() {
        const container = document.getElementById('qualityList');
        
        if (!this.config.quality_multipliers || Object.keys(this.config.quality_multipliers).length === 0) {
            container.innerHTML = '<p class="text-muted">Нет настроек качества</p>';
            return;
        }
        
        let html = '';
        
        Object.entries(this.config.quality_multipliers).forEach(([key, quality]) => {
            const activeClass = quality.active ? '' : 'config-item-inactive';
            html += `
                <div class="config-item ${activeClass}">
                    <div class="config-item-header">
                        <div class="config-item-title">${quality.name}</div>
                    </div>
                    <div class="config-item-details">
                        <div class="config-item-field">
                            <div class="config-item-label">Ключ</div>
                            <div class="config-item-value">${key}</div>
                        </div>
                        <div class="config-item-field">
                            <div class="config-item-label">Множитель цены</div>
                            <div class="config-item-value">${quality.multiplier}x</div>
                        </div>
                        <div class="config-item-field">
                            <div class="config-item-label">Множитель времени</div>
                            <div class="config-item-value">${quality.time}x</div>
                        </div>
                        <div class="config-item-field">
                            <div class="config-item-label">Статус</div>
                            <div class="config-item-value">${quality.active ? 'Активно' : 'Неактивно'}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    // ========================================
    // DISCOUNTS
    // ========================================
    
    renderDiscounts() {
        const container = document.getElementById('discountsList');
        
        if (!this.config.discounts || this.config.discounts.length === 0) {
            container.innerHTML = '<p class="text-muted">Нет скидок</p>';
            return;
        }
        
        // Sort by minQuantity
        const sorted = [...this.config.discounts].sort((a, b) => a.minQuantity - b.minQuantity);
        
        let html = '';
        
        sorted.forEach((discount, index) => {
            const actualIndex = this.config.discounts.indexOf(discount);
            const activeClass = discount.active ? '' : 'config-item-inactive';
            html += `
                <div class="config-item ${activeClass}">
                    <div class="config-item-header">
                        <div class="config-item-title">Скидка ${discount.percent}% от ${discount.minQuantity} шт</div>
                        <div class="config-item-actions">
                            <button class="btn btn-sm btn-secondary" onclick="window.calculatorSettings.editDiscount(${actualIndex})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="window.calculatorSettings.deleteDiscount(${actualIndex})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    openDiscountModal(index = null) {
        const modal = document.getElementById('discountModal');
        const form = document.getElementById('discountForm');
        form.reset();
        
        if (index !== null) {
            const discount = this.config.discounts[index];
            document.getElementById('discountModalTitle').textContent = 'Редактировать скидку';
            document.getElementById('discountIndex').value = index;
            document.getElementById('discountMinQuantity').value = discount.minQuantity;
            document.getElementById('discountPercent').value = discount.percent;
            document.getElementById('discountActive').checked = discount.active !== false;
        } else {
            document.getElementById('discountModalTitle').textContent = 'Добавить скидку';
            document.getElementById('discountIndex').value = '';
        }
        
        modal.style.display = 'flex';
    }
    
    closeDiscountModal() {
        document.getElementById('discountModal').style.display = 'none';
    }
    
    saveDiscount() {
        const index = document.getElementById('discountIndex').value;
        const discount = {
            minQuantity: parseInt(document.getElementById('discountMinQuantity').value),
            percent: parseFloat(document.getElementById('discountPercent').value),
            active: document.getElementById('discountActive').checked
        };
        
        if (index !== '') {
            this.config.discounts[index] = discount;
        } else {
            this.config.discounts.push(discount);
        }
        
        this.hasChanges = true;
        this.renderDiscounts();
        this.closeDiscountModal();
        window.showNotification('Скидка сохранена. Не забудьте сохранить все изменения!', 'info');
    }
    
    editDiscount(index) {
        this.openDiscountModal(index);
    }
    
    deleteDiscount(index) {
        if (confirm('Удалить эту скидку?')) {
            this.config.discounts.splice(index, 1);
            this.hasChanges = true;
            this.renderDiscounts();
            window.showNotification('Скидка удалена. Не забудьте сохранить все изменения!', 'info');
        }
    }
    
    // ========================================
    // FORMULAS
    // ========================================
    
    renderFormulas() {
        const container = document.getElementById('formulasList');
        
        if (!this.config.formulas || Object.keys(this.config.formulas).length === 0) {
            container.innerHTML = '<p class="text-muted">Нет формул</p>';
            return;
        }
        
        let html = '';
        
        Object.entries(this.config.formulas).forEach(([key, formula]) => {
            const activeClass = formula.active ? '' : 'config-item-inactive';
            const variables = (formula.variables || []).join(', ');
            html += `
                <div class="config-item ${activeClass}">
                    <div class="config-item-header">
                        <div class="config-item-title">${formula.name}</div>
                        <div class="config-item-actions">
                            <button class="btn btn-sm btn-secondary" onclick="window.calculatorSettings.editFormula('${key}')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                    <div class="config-item-details">
                        <div class="config-item-field">
                            <div class="config-item-label">Формула</div>
                            <div class="config-item-value" style="font-family: monospace;">${formula.formula}</div>
                        </div>
                        <div class="config-item-field">
                            <div class="config-item-label">Переменные</div>
                            <div class="config-item-value">${variables}</div>
                        </div>
                    </div>
                    ${formula.description ? `<p style="margin-top: 10px; color: #666;">${formula.description}</p>` : ''}
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    editFormula(key) {
        const modal = document.getElementById('formulaModal');
        const form = document.getElementById('formulaForm');
        form.reset();
        
        const formula = this.config.formulas[key];
        document.getElementById('formulaModalTitle').textContent = 'Редактировать формулу';
        document.getElementById('formulaKey').value = key;
        document.getElementById('formulaName').value = formula.name;
        document.getElementById('formulaDescription').value = formula.description || '';
        document.getElementById('formulaExpression').value = formula.formula;
        document.getElementById('formulaVariables').value = (formula.variables || []).join(', ');
        document.getElementById('formulaActive').checked = formula.active !== false;
        
        modal.style.display = 'flex';
    }
    
    closeFormulaModal() {
        document.getElementById('formulaModal').style.display = 'none';
    }
    
    async validateFormula() {
        const formula = document.getElementById('formulaExpression').value;
        const variablesStr = document.getElementById('formulaVariables').value;
        const variables = variablesStr.split(',').map(v => v.trim()).filter(v => v);
        
        const resultEl = document.getElementById('formulaValidationResult');
        resultEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Проверка...';
        
        try {
            const response = await window.adminApi.post('/api/calculator-settings.php?action=validate', {
                formula,
                variables,
                test_values: variables.reduce((acc, v) => ({ ...acc, [v]: 10 }), {})
            });
            
            if (response.success && response.data.valid) {
                resultEl.innerHTML = '<span style="color: green;"><i class="fas fa-check"></i> Формула корректна</span>';
                if (response.data.test_result) {
                    resultEl.innerHTML += ` (тест: ${response.data.test_result.result})`;
                }
            } else {
                const errors = response.data.errors.join(', ');
                resultEl.innerHTML = `<span style="color: red;"><i class="fas fa-times"></i> ${errors}</span>`;
            }
        } catch (error) {
            resultEl.innerHTML = '<span style="color: red;"><i class="fas fa-times"></i> Ошибка проверки</span>';
        }
    }
    
    saveFormula() {
        const key = document.getElementById('formulaKey').value;
        const variablesStr = document.getElementById('formulaVariables').value;
        const variables = variablesStr.split(',').map(v => v.trim()).filter(v => v);
        
        const formula = {
            name: document.getElementById('formulaName').value,
            description: document.getElementById('formulaDescription').value,
            formula: document.getElementById('formulaExpression').value,
            variables: variables,
            active: document.getElementById('formulaActive').checked
        };
        
        this.config.formulas[key] = formula;
        this.hasChanges = true;
        this.renderFormulas();
        this.closeFormulaModal();
        window.showNotification('Формула сохранена. Не забудьте сохранить все изменения!', 'info');
    }
    
    // ========================================
    // SANDBOX
    // ========================================
    
    renderSandbox() {
        this.updateSandboxMaterials();
        this.updateSandboxQuality();
        this.updateSandboxServices();
    }
    
    updateSandboxMaterials() {
        const tech = document.getElementById('testTechnology').value;
        const select = document.getElementById('testMaterial');
        
        const materials = this.config.materials.filter(m => m.technology === tech && m.active);
        
        select.innerHTML = materials.map(m => 
            `<option value="${m.key}">${m.name} (${m.price}₽/г)</option>`
        ).join('');
    }
    
    updateSandboxQuality() {
        const select = document.getElementById('testQuality');
        
        const qualities = Object.entries(this.config.quality_multipliers)
            .filter(([k, q]) => q.active);
        
        select.innerHTML = qualities.map(([k, q]) => 
            `<option value="${k}">${q.name} (${q.multiplier}x)</option>`
        ).join('');
    }
    
    updateSandboxServices() {
        const container = document.getElementById('testServices');
        
        const services = this.config.services.filter(s => s.active);
        
        container.innerHTML = services.map(s => 
            `<label class="checkbox-label">
                <input type="checkbox" value="${s.key}">
                <span>${s.name} (${s.price}₽/${s.unit})</span>
            </label>`
        ).join('');
    }
    
    async runTest() {
        const testData = {
            weight: parseFloat(document.getElementById('testWeight').value),
            quantity: parseInt(document.getElementById('testQuantity').value),
            infill: parseInt(document.getElementById('testInfill').value),
            material: document.getElementById('testMaterial').value,
            quality: document.getElementById('testQuality').value,
            additional_services: Array.from(document.querySelectorAll('#testServices input:checked')).map(cb => cb.value)
        };
        
        try {
            const response = await window.adminApi.post('/api/calculator-settings.php?action=test', testData);
            
            if (response.success) {
                this.displayTestResults(response.data);
            } else {
                window.showNotification('Ошибка расчета: ' + response.message, 'error');
            }
        } catch (error) {
            console.error('Test failed:', error);
            window.showNotification('Ошибка выполнения теста', 'error');
        }
    }
    
    displayTestResults(data) {
        const container = document.getElementById('testResultsContent');
        const breakdown = data.breakdown;
        
        let html = `
            <div class="result-grid">
                <div class="result-item">
                    <div class="result-label">Материалы</div>
                    <div class="result-value">${breakdown.material_cost.toLocaleString('ru-RU')} ₽</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Работа</div>
                    <div class="result-value">${breakdown.labor_cost.toLocaleString('ru-RU')} ₽</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Услуги</div>
                    <div class="result-value">${breakdown.services_cost.toLocaleString('ru-RU')} ₽</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Скидка</div>
                    <div class="result-value">-${breakdown.discount.toLocaleString('ru-RU')} ₽ (${breakdown.discount_percent}%)</div>
                </div>
                <div class="result-item" style="grid-column: span 2;">
                    <div class="result-label">Итого</div>
                    <div class="result-value" style="font-size: 28px; color: #4CAF50;">${breakdown.total.toLocaleString('ru-RU')} ₽</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Время изготовления</div>
                    <div class="result-value">${data.time_estimate}</div>
                    <small>${data.time_hours} часов / ${data.time_days} дней</small>
                </div>
            </div>
        `;
        
        if (data.services_used && data.services_used.length > 0) {
            html += `<p style="margin-top: 15px;"><strong>Дополнительные услуги:</strong> ${data.services_used.join(', ')}</p>`;
        }
        
        html += `<div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px; font-size: 12px;">`;
        html += `<strong>Детали:</strong><br>`;
        html += `Материал: ${data.details.material}, Вес: ${data.details.weight}г, Количество: ${data.details.quantity} шт<br>`;
        html += `Заполнение: ${data.details.infill}% (фактор: ${data.details.infill_factor}), Качество: ${data.details.quality}`;
        html += `</div>`;
        
        container.innerHTML = html;
        document.getElementById('testResults').style.display = 'block';
    }
    
    // ========================================
    // SAVE ALL
    // ========================================
    
    async saveAll() {
        if (!this.hasChanges) {
            window.showNotification('Нет изменений для сохранения', 'info');
            return;
        }
        
        const btn = document.getElementById('saveAllBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
        
        try {
            // Save materials
            await window.adminApi.post('/api/calculator-settings.php?action=materials', {
                materials: this.config.materials
            });
            
            // Save services
            await window.adminApi.post('/api/calculator-settings.php?action=services', {
                services: this.config.services
            });
            
            // Save quality
            await window.adminApi.post('/api/calculator-settings.php?action=quality', {
                quality_multipliers: this.config.quality_multipliers
            });
            
            // Save discounts
            await window.adminApi.post('/api/calculator-settings.php?action=discounts', {
                discounts: this.config.discounts
            });
            
            // Save formulas
            await window.adminApi.post('/api/calculator-settings.php?action=formulas', {
                formulas: this.config.formulas
            });
            
            this.hasChanges = false;
            window.showNotification('Все настройки сохранены успешно!', 'success');
            
        } catch (error) {
            console.error('Save failed:', error);
            window.showNotification('Ошибка сохранения настроек', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Сохранить все изменения';
        }
    }
}

// Initialize module
const calculatorSettings = new CalculatorSettingsModule();
window.calculatorSettings = calculatorSettings;

document.addEventListener('DOMContentLoaded', () => {
    calculatorSettings.init();
});
