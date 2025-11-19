// ========================================
// Forms Module - Form Builder with Drag & Drop
// ========================================

class FormsModule {
    constructor() {
        this.forms = [];
        this.currentForm = null;
        this.currentFields = [];
        this.currentConditions = [];
        this.editingFieldIndex = null;
        this.editingConditionIndex = null;
        this.draggedElement = null;
        this.adminMain = null;
    }
    
    async init() {
        console.log('📝 Loading forms...');
        
        if (!window.adminApi || !window.AdminMain) {
            setTimeout(() => this.init(), 100);
            return;
        }
        
        this.adminMain = window.AdminMain;
        this.initButtons();
        this.initFieldTypeListener();
        await this.loadForms();
    }
    
    initButtons() {
        document.getElementById('createFormBtn')?.addEventListener('click', () => this.showEditor());
        document.getElementById('saveFormBtn')?.addEventListener('click', () => this.saveForm());
        document.getElementById('addFieldBtn')?.addEventListener('click', () => this.showFieldEditor());
        document.getElementById('saveFieldBtn')?.addEventListener('click', () => this.saveField());
        document.getElementById('addConditionBtn')?.addEventListener('click', () => this.showConditionEditor());
        document.getElementById('saveConditionBtn')?.addEventListener('click', () => this.saveCondition());
        
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.switchTab(e.target.closest('.tab-btn').dataset.tab));
        });
        
        document.getElementById('formSlug')?.addEventListener('input', (e) => {
            e.target.value = e.target.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
        });
        
        document.getElementById('searchInput')?.addEventListener('input', () => this.filterForms());
    }
    
    initFieldTypeListener() {
        const fieldType = document.getElementById('fieldType');
        if (fieldType) {
            fieldType.addEventListener('change', () => this.updateFieldOptionsVisibility());
        }
    }
    
    updateFieldOptionsVisibility() {
        const fieldType = document.getElementById('fieldType').value;
        const optionsGroup = document.getElementById('fieldOptionsGroup');
        const numericValidation = document.getElementById('numericValidation');
        
        optionsGroup.style.display = ['select', 'radio', 'checkbox'].includes(fieldType) ? 'block' : 'none';
        numericValidation.style.display = fieldType === 'number' ? 'flex' : 'none';
    }
    
    async loadForms() {
        const table = document.getElementById('formsTable');
        if (!table) return;
        
        try {
            this.adminMain.showLoading(table);
            
            const response = await window.adminApi.getForms();
            this.forms = response.data.forms;
            
            this.renderFormsTable();
            this.updateFormsInfo();
            
            console.log(`✅ Loaded ${this.forms.length} forms`);
        } catch (error) {
            console.error('❌ Failed to load forms:', error);
            this.adminMain.showError(table, 'Не удалось загрузить формы');
        }
    }
    
    filterForms() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const filtered = this.forms.filter(form => 
            form.name.toLowerCase().includes(search) ||
            form.slug.toLowerCase().includes(search) ||
            (form.description && form.description.toLowerCase().includes(search))
        );
        this.renderFormsTable(filtered);
    }
    
    renderFormsTable(forms = this.forms) {
        const table = document.getElementById('formsTable');
        if (!table) return;
        
        if (forms.length === 0) {
            table.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Нет форм</p>
                    <button class="btn btn-primary" onclick="formsModule.showEditor()">
                        Создать первую форму
                    </button>
                </div>
            `;
            return;
        }
        
        table.innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Слаг</th>
                        <th>Поля</th>
                        <th>Статус</th>
                        <th>Обновлено</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    ${forms.map(form => `
                        <tr>
                            <td>
                                <strong>${this.escapeHtml(form.name)}</strong>
                                ${form.description ? `<br><small class="text-muted">${this.escapeHtml(form.description)}</small>` : ''}
                            </td>
                            <td><code>${this.escapeHtml(form.slug)}</code></td>
                            <td>${form.fields_count || 0}</td>
                            <td>
                                <span class="badge ${form.active ? 'badge-success' : 'badge-secondary'}">
                                    ${form.active ? 'Активна' : 'Неактивна'}
                                </span>
                            </td>
                            <td>${this.formatDate(form.updated_at)}</td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline" onclick="formsModule.editForm(${form.id})" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline" onclick="formsModule.duplicateForm(${form.id})" title="Дублировать">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline" onclick="formsModule.deleteForm(${form.id})" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }
    
    updateFormsInfo() {
        const info = document.getElementById('formsInfo');
        if (info) {
            info.textContent = `Всего форм: ${this.forms.length}`;
        }
    }
    
    async showEditor(formId = null) {
        this.currentForm = null;
        this.currentFields = [];
        this.currentConditions = [];
        
        if (formId) {
            try {
                const response = await window.adminApi.getForm(formId);
                this.currentForm = response.data.form;
                this.currentFields = this.currentForm.fields || [];
                this.currentConditions = this.currentForm.settings?.conditional_rules || [];
            } catch (error) {
                console.error('Failed to load form:', error);
                this.adminMain.showNotification('Не удалось загрузить форму', 'error');
                return;
            }
        }
        
        this.populateFormEditor();
        this.switchTab('settings');
        this.showModal('formEditorModal');
        
        document.getElementById('editorTitle').textContent = formId ? 'Редактировать форму' : 'Новая форма';
    }
    
    populateFormEditor() {
        if (this.currentForm) {
            document.getElementById('formName').value = this.currentForm.name || '';
            document.getElementById('formSlug').value = this.currentForm.slug || '';
            document.getElementById('formDescription').value = this.currentForm.description || '';
            document.getElementById('successMessage').value = this.currentForm.success_message || '';
            document.getElementById('redirectUrl').value = this.currentForm.redirect_url || '';
            document.getElementById('sortOrder').value = this.currentForm.sort_order || 0;
            document.getElementById('formActive').checked = this.currentForm.active !== false;
            
            const settings = this.currentForm.settings || {};
            document.getElementById('telegramEnabled').checked = settings.telegram_enabled || false;
            document.getElementById('telegramChatId').value = settings.telegram_chat_id || '';
            document.getElementById('emailEnabled').checked = settings.email_enabled || false;
            document.getElementById('emailRecipients').value = settings.email_recipients || '';
            document.getElementById('emailTemplate').value = settings.email_template || 'default';
            document.getElementById('calculatorEnabled').checked = settings.calculator_enabled || false;
        } else {
            document.getElementById('formName').value = '';
            document.getElementById('formSlug').value = '';
            document.getElementById('formDescription').value = '';
            document.getElementById('successMessage').value = '';
            document.getElementById('redirectUrl').value = '';
            document.getElementById('sortOrder').value = 0;
            document.getElementById('formActive').checked = true;
            document.getElementById('telegramEnabled').checked = false;
            document.getElementById('telegramChatId').value = '';
            document.getElementById('emailEnabled').checked = false;
            document.getElementById('emailRecipients').value = '';
            document.getElementById('emailTemplate').value = 'default';
            document.getElementById('calculatorEnabled').checked = false;
        }
        
        this.renderFieldsList();
        this.renderConditionsList();
        this.renderCalculatorMappings();
        this.renderPreview();
    }
    
    renderFieldsList() {
        const list = document.getElementById('fieldsList');
        if (!list) return;
        
        if (this.currentFields.length === 0) {
            list.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Нет полей. Добавьте первое поле.</p>
                </div>
            `;
            return;
        }
        
        list.innerHTML = this.currentFields.map((field, index) => `
            <div class="field-item" draggable="true" data-index="${index}">
                <div class="field-drag">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                <div class="field-content">
                    <div class="field-header">
                        <strong>${this.escapeHtml(field.label)}</strong>
                        <span class="badge">${this.getFieldTypeLabel(field.type)}</span>
                        ${field.required ? '<span class="badge badge-danger">Обязательное</span>' : ''}
                        ${!field.active ? '<span class="badge badge-secondary">Неактивно</span>' : ''}
                    </div>
                    <div class="field-meta">
                        <small>Имя: <code>${this.escapeHtml(field.name)}</code></small>
                    </div>
                </div>
                <div class="field-actions">
                    <button class="btn btn-sm btn-outline" onclick="formsModule.editField(${index})" title="Редактировать">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="formsModule.deleteField(${index})" title="Удалить">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
        
        this.initDragAndDrop();
    }
    
    initDragAndDrop() {
        const items = document.querySelectorAll('.field-item');
        
        items.forEach(item => {
            item.addEventListener('dragstart', (e) => {
                this.draggedElement = item;
                item.classList.add('dragging');
            });
            
            item.addEventListener('dragend', (e) => {
                item.classList.remove('dragging');
                this.draggedElement = null;
            });
            
            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                const afterElement = this.getDragAfterElement(e.clientY);
                const list = document.getElementById('fieldsList');
                if (afterElement == null) {
                    list.appendChild(this.draggedElement);
                } else {
                    list.insertBefore(this.draggedElement, afterElement);
                }
            });
            
            item.addEventListener('drop', (e) => {
                e.preventDefault();
                this.reorderFields();
            });
        });
    }
    
    getDragAfterElement(y) {
        const draggableElements = [...document.querySelectorAll('.field-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    reorderFields() {
        const items = document.querySelectorAll('.field-item');
        const newOrder = [];
        
        items.forEach((item, newIndex) => {
            const oldIndex = parseInt(item.dataset.index);
            const field = this.currentFields[oldIndex];
            field.sort_order = newIndex;
            newOrder.push(field);
        });
        
        this.currentFields = newOrder;
        this.renderFieldsList();
    }
    
    showFieldEditor(index = null) {
        this.editingFieldIndex = index;
        
        if (index !== null && this.currentFields[index]) {
            const field = this.currentFields[index];
            document.getElementById('editingFieldId').value = field.id || '';
            document.getElementById('fieldName').value = field.name || '';
            document.getElementById('fieldLabel').value = field.label || '';
            document.getElementById('fieldType').value = field.type || 'text';
            document.getElementById('fieldPlaceholder').value = field.placeholder || '';
            document.getElementById('fieldDefaultValue').value = field.default_value || '';
            document.getElementById('fieldHelpText').value = field.help_text || '';
            document.getElementById('fieldRequired').checked = field.required || false;
            document.getElementById('fieldActive').checked = field.active !== false;
            
            if (field.options && Array.isArray(field.options)) {
                document.getElementById('fieldOptions').value = field.options.join('\n');
            } else {
                document.getElementById('fieldOptions').value = '';
            }
            
            const rules = field.validation_rules || {};
            document.getElementById('fieldMinLength').value = rules.minLength || '';
            document.getElementById('fieldMaxLength').value = rules.maxLength || '';
            document.getElementById('fieldMin').value = rules.min || '';
            document.getElementById('fieldMax').value = rules.max || '';
            document.getElementById('fieldPattern').value = rules.pattern || '';
            
            document.getElementById('fieldEditorTitle').textContent = 'Редактировать поле';
        } else {
            document.getElementById('editingFieldId').value = '';
            document.getElementById('fieldName').value = '';
            document.getElementById('fieldLabel').value = '';
            document.getElementById('fieldType').value = 'text';
            document.getElementById('fieldPlaceholder').value = '';
            document.getElementById('fieldDefaultValue').value = '';
            document.getElementById('fieldHelpText').value = '';
            document.getElementById('fieldOptions').value = '';
            document.getElementById('fieldRequired').checked = false;
            document.getElementById('fieldActive').checked = true;
            document.getElementById('fieldMinLength').value = '';
            document.getElementById('fieldMaxLength').value = '';
            document.getElementById('fieldMin').value = '';
            document.getElementById('fieldMax').value = '';
            document.getElementById('fieldPattern').value = '';
            
            document.getElementById('fieldEditorTitle').textContent = 'Новое поле';
        }
        
        this.updateFieldOptionsVisibility();
        this.showModal('fieldEditorModal');
    }
    
    saveField() {
        const name = document.getElementById('fieldName').value.trim();
        const label = document.getElementById('fieldLabel').value.trim();
        const type = document.getElementById('fieldType').value;
        
        if (!name || !label || !type) {
            this.adminMain.showNotification('Заполните обязательные поля', 'error');
            return;
        }
        
        const field = {
            id: document.getElementById('editingFieldId').value || undefined,
            name,
            label,
            type,
            placeholder: document.getElementById('fieldPlaceholder').value.trim() || null,
            default_value: document.getElementById('fieldDefaultValue').value.trim() || null,
            help_text: document.getElementById('fieldHelpText').value.trim() || null,
            required: document.getElementById('fieldRequired').checked,
            active: document.getElementById('fieldActive').checked,
            validation_rules: {},
            options: null,
        };
        
        const minLength = document.getElementById('fieldMinLength').value;
        const maxLength = document.getElementById('fieldMaxLength').value;
        const min = document.getElementById('fieldMin').value;
        const max = document.getElementById('fieldMax').value;
        const pattern = document.getElementById('fieldPattern').value.trim();
        
        if (minLength) field.validation_rules.minLength = parseInt(minLength);
        if (maxLength) field.validation_rules.maxLength = parseInt(maxLength);
        if (min) field.validation_rules.min = parseFloat(min);
        if (max) field.validation_rules.max = parseFloat(max);
        if (pattern) field.validation_rules.pattern = pattern;
        
        if (['select', 'radio', 'checkbox'].includes(type)) {
            const optionsText = document.getElementById('fieldOptions').value.trim();
            if (optionsText) {
                field.options = optionsText.split('\n').map(opt => opt.trim()).filter(opt => opt);
            }
        }
        
        if (this.editingFieldIndex !== null) {
            this.currentFields[this.editingFieldIndex] = field;
        } else {
            field.sort_order = this.currentFields.length;
            this.currentFields.push(field);
        }
        
        this.renderFieldsList();
        this.renderPreview();
        this.closeFieldEditor();
    }
    
    editField(index) {
        this.showFieldEditor(index);
    }
    
    deleteField(index) {
        if (confirm('Удалить это поле?')) {
            this.currentFields.splice(index, 1);
            this.renderFieldsList();
            this.renderPreview();
        }
    }
    
    renderConditionsList() {
        const list = document.getElementById('conditionsList');
        if (!list) return;
        
        if (this.currentConditions.length === 0) {
            list.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-code-branch"></i>
                    <p>Нет условий. Создайте первое условие.</p>
                </div>
            `;
            return;
        }
        
        list.innerHTML = this.currentConditions.map((cond, index) => `
            <div class="condition-item">
                <div class="condition-content">
                    <strong>${cond.action === 'show' ? 'Показать' : 'Скрыть'}</strong>
                    <code>${cond.target_field}</code>
                    <span>если</span>
                    <code>${cond.source_field}</code>
                    <strong>${this.getOperatorLabel(cond.operator)}</strong>
                    ${cond.value ? `<code>${this.escapeHtml(cond.value)}</code>` : ''}
                </div>
                <div class="condition-actions">
                    <button class="btn btn-sm btn-outline" onclick="formsModule.editCondition(${index})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline" onclick="formsModule.deleteCondition(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    showConditionEditor(index = null) {
        this.editingConditionIndex = index;
        
        const targetField = document.getElementById('conditionTargetField');
        const sourceField = document.getElementById('conditionSourceField');
        
        targetField.innerHTML = '<option value="">Выберите поле...</option>' +
            this.currentFields.map(f => `<option value="${f.name}">${this.escapeHtml(f.label)}</option>`).join('');
        sourceField.innerHTML = '<option value="">Выберите поле...</option>' +
            this.currentFields.map(f => `<option value="${f.name}">${this.escapeHtml(f.label)}</option>`).join('');
        
        if (index !== null && this.currentConditions[index]) {
            const cond = this.currentConditions[index];
            document.getElementById('editingConditionId').value = index;
            targetField.value = cond.target_field;
            sourceField.value = cond.source_field;
            document.getElementById('conditionOperator').value = cond.operator;
            document.getElementById('conditionValue').value = cond.value || '';
            document.getElementById('conditionAction').value = cond.action;
        } else {
            document.getElementById('editingConditionId').value = '';
            targetField.value = '';
            sourceField.value = '';
            document.getElementById('conditionOperator').value = 'equals';
            document.getElementById('conditionValue').value = '';
            document.getElementById('conditionAction').value = 'show';
        }
        
        this.showModal('conditionEditorModal');
    }
    
    saveCondition() {
        const condition = {
            target_field: document.getElementById('conditionTargetField').value,
            source_field: document.getElementById('conditionSourceField').value,
            operator: document.getElementById('conditionOperator').value,
            value: document.getElementById('conditionValue').value.trim() || null,
            action: document.getElementById('conditionAction').value,
        };
        
        if (!condition.target_field || !condition.source_field) {
            this.adminMain.showNotification('Выберите поля для условия', 'error');
            return;
        }
        
        if (this.editingConditionIndex !== null) {
            this.currentConditions[this.editingConditionIndex] = condition;
        } else {
            this.currentConditions.push(condition);
        }
        
        this.renderConditionsList();
        this.closeConditionEditor();
    }
    
    editCondition(index) {
        this.showConditionEditor(index);
    }
    
    deleteCondition(index) {
        if (confirm('Удалить это условие?')) {
            this.currentConditions.splice(index, 1);
            this.renderConditionsList();
        }
    }
    
    renderCalculatorMappings() {
        const list = document.getElementById('mappingsList');
        if (!list) return;
        
        if (this.currentFields.length === 0) {
            list.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-calculator"></i>
                    <p>Добавьте поля формы для настройки маппинга калькулятора.</p>
                </div>
            `;
            return;
        }
        
        const settings = this.currentForm?.settings || {};
        const mappings = settings.calculator_mapping || {};
        
        list.innerHTML = `
            <div class="form-group">
                <label>Маппинг полей калькулятора</label>
                ${this.currentFields.map(field => `
                    <div class="mapping-row">
                        <label>${this.escapeHtml(field.label)}</label>
                        <input type="text" class="form-control" 
                               data-field="${field.name}"
                               placeholder="calculator.outputKey"
                               value="${mappings[field.name] || ''}">
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    renderPreview() {
        const preview = document.getElementById('formPreview');
        if (!preview) return;
        
        if (this.currentFields.length === 0) {
            preview.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-eye-slash"></i>
                    <p>Добавьте поля для предпросмотра</p>
                </div>
            `;
            return;
        }
        
        preview.innerHTML = `
            <form class="preview-form">
                ${this.currentFields.filter(f => f.active !== false).map(field => this.renderPreviewField(field)).join('')}
                <button type="button" class="btn btn-primary" disabled>Отправить</button>
            </form>
        `;
    }
    
    renderPreviewField(field) {
        let input = '';
        
        switch (field.type) {
            case 'textarea':
                input = `<textarea class="form-control" placeholder="${this.escapeHtml(field.placeholder || '')}" ${field.required ? 'required' : ''}>${this.escapeHtml(field.default_value || '')}</textarea>`;
                break;
            case 'select':
                input = `<select class="form-control" ${field.required ? 'required' : ''}>
                    <option value="">Выберите...</option>
                    ${(field.options || []).map(opt => `<option>${this.escapeHtml(opt)}</option>`).join('')}
                </select>`;
                break;
            case 'radio':
                input = (field.options || []).map(opt => `
                    <label class="radio-label">
                        <input type="radio" name="${field.name}" value="${this.escapeHtml(opt)}" ${field.required ? 'required' : ''}>
                        <span>${this.escapeHtml(opt)}</span>
                    </label>
                `).join('');
                break;
            case 'checkbox':
                if (field.options && field.options.length > 0) {
                    input = (field.options || []).map(opt => `
                        <label class="checkbox-label">
                            <input type="checkbox" name="${field.name}[]" value="${this.escapeHtml(opt)}">
                            <span>${this.escapeHtml(opt)}</span>
                        </label>
                    `).join('');
                } else {
                    input = `<label class="checkbox-label">
                        <input type="checkbox" name="${field.name}" ${field.required ? 'required' : ''}>
                        <span>${this.escapeHtml(field.label)}</span>
                    </label>`;
                }
                break;
            case 'hidden':
                return `<input type="hidden" name="${field.name}" value="${this.escapeHtml(field.default_value || '')}">`;
            default:
                input = `<input type="${field.type}" class="form-control" placeholder="${this.escapeHtml(field.placeholder || '')}" value="${this.escapeHtml(field.default_value || '')}" ${field.required ? 'required' : ''}>`;
        }
        
        return `
            <div class="form-group">
                ${field.type !== 'checkbox' || (field.options && field.options.length > 0) ? `<label>${this.escapeHtml(field.label)}${field.required ? ' *' : ''}</label>` : ''}
                ${input}
                ${field.help_text ? `<small class="form-text">${this.escapeHtml(field.help_text)}</small>` : ''}
            </div>
        `;
    }
    
    async saveForm() {
        const name = document.getElementById('formName').value.trim();
        const slug = document.getElementById('formSlug').value.trim();
        
        if (!name || !slug) {
            this.adminMain.showNotification('Заполните обязательные поля', 'error');
            return;
        }
        
        const calculatorMapping = {};
        document.querySelectorAll('#mappingsList input[data-field]').forEach(input => {
            const fieldName = input.dataset.field;
            const mappingValue = input.value.trim();
            if (mappingValue) {
                calculatorMapping[fieldName] = mappingValue;
            }
        });
        
        const formData = {
            name,
            slug,
            description: document.getElementById('formDescription').value.trim() || null,
            success_message: document.getElementById('successMessage').value.trim() || null,
            redirect_url: document.getElementById('redirectUrl').value.trim() || null,
            sort_order: parseInt(document.getElementById('sortOrder').value) || 0,
            active: document.getElementById('formActive').checked,
            settings: {
                telegram_enabled: document.getElementById('telegramEnabled').checked,
                telegram_chat_id: document.getElementById('telegramChatId').value.trim() || null,
                email_enabled: document.getElementById('emailEnabled').checked,
                email_recipients: document.getElementById('emailRecipients').value.trim() || null,
                email_template: document.getElementById('emailTemplate').value,
                calculator_enabled: document.getElementById('calculatorEnabled').checked,
                calculator_mapping: calculatorMapping,
                conditional_rules: this.currentConditions,
            }
        };
        
        try {
            if (this.currentForm) {
                formData.id = this.currentForm.id;
                await window.adminApi.updateForm(formData);
                
                await this.saveFormFields(this.currentForm.id);
            } else {
                const response = await window.adminApi.createForm(formData);
                const formId = response.data.form_id;
                
                if (this.currentFields.length > 0) {
                    await this.saveFormFields(formId);
                }
            }
            
            this.adminMain.showNotification('Форма сохранена', 'success');
            this.closeEditor();
            await this.loadForms();
        } catch (error) {
            console.error('Failed to save form:', error);
            this.adminMain.showNotification(error.message || 'Не удалось сохранить форму', 'error');
        }
    }
    
    async saveFormFields(formId) {
        for (const field of this.currentFields) {
            const fieldData = { ...field, form_id: formId };
            
            if (field.id) {
                await window.adminApi.updateFormField(fieldData);
            } else {
                await window.adminApi.createFormField(fieldData);
            }
        }
    }
    
    async editForm(formId) {
        await this.showEditor(formId);
    }
    
    async duplicateForm(formId) {
        if (!confirm('Создать копию этой формы?')) return;
        
        try {
            const response = await window.adminApi.getForm(formId);
            const form = response.data.form;
            
            form.name = form.name + ' (копия)';
            form.slug = form.slug + '-copy-' + Date.now();
            delete form.id;
            delete form.created_at;
            delete form.updated_at;
            
            const createResponse = await window.adminApi.createForm(form);
            const newFormId = createResponse.data.form_id;
            
            for (const field of form.fields || []) {
                delete field.id;
                field.form_id = newFormId;
                await window.adminApi.createFormField(field);
            }
            
            this.adminMain.showNotification('Форма скопирована', 'success');
            await this.loadForms();
        } catch (error) {
            console.error('Failed to duplicate form:', error);
            this.adminMain.showNotification('Не удалось скопировать форму', 'error');
        }
    }
    
    async deleteForm(formId) {
        if (!confirm('Удалить эту форму? Это действие необратимо.')) return;
        
        try {
            await window.adminApi.deleteForm(formId);
            this.adminMain.showNotification('Форма удалена', 'success');
            await this.loadForms();
        } catch (error) {
            console.error('Failed to delete form:', error);
            this.adminMain.showNotification(error.message || 'Не удалось удалить форму', 'error');
        }
    }
    
    switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id === tabName + 'Tab');
        });
        
        if (tabName === 'preview') {
            this.renderPreview();
        } else if (tabName === 'calculator') {
            this.renderCalculatorMappings();
        }
    }
    
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    closeEditor() {
        this.closeModal('formEditorModal');
    }
    
    closeFieldEditor() {
        this.closeModal('fieldEditorModal');
    }
    
    closeConditionEditor() {
        this.closeModal('conditionEditorModal');
    }
    
    getFieldTypeLabel(type) {
        const labels = {
            text: 'Текст',
            email: 'Email',
            phone: 'Телефон',
            number: 'Число',
            textarea: 'Многострочный',
            select: 'Список',
            radio: 'Радио',
            checkbox: 'Чекбокс',
            file: 'Файл',
            hidden: 'Скрытое'
        };
        return labels[type] || type;
    }
    
    getOperatorLabel(op) {
        const labels = {
            equals: 'равно',
            not_equals: 'не равно',
            contains: 'содержит',
            not_contains: 'не содержит',
            empty: 'пусто',
            not_empty: 'не пусто'
        };
        return labels[op] || op;
    }
    
    formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

const formsModule = new FormsModule();
document.addEventListener('DOMContentLoaded', () => formsModule.init());
