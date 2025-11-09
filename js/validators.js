// ========================================
// FORM VALIDATORS
// ========================================

class Validator {
    constructor() {
        this.errors = {};
    }
    
    // Required field
    required(value, fieldName) {
        if (!value || value.toString().trim() === '') {
            this.errors[fieldName] = `Поле "${fieldName}" обязательно для заполнения`;
            return false;
        }
        return true;
    }
    
    // Email validation
    email(value, fieldName = 'Email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            this.errors[fieldName] = 'Некорректный email адрес';
            return false;
        }
        return true;
    }
    
    // Phone validation (Russian format)
    phone(value, fieldName = 'Телефон') {
        const phoneRegex = /^(\+7|8)?[\s\-]?\(?[489][0-9]{2}\)?[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            this.errors[fieldName] = 'Некорректный номер телефона';
            return false;
        }
        return true;
    }
    
    // Min length
    minLength(value, min, fieldName) {
        if (value.length < min) {
            this.errors[fieldName] = `Минимальная длина - ${min} символов`;
            return false;
        }
        return true;
    }
    
    // Max length
    maxLength(value, max, fieldName) {
        if (value.length > max) {
            this.errors[fieldName] = `Максимальная длина - ${max} символов`;
            return false;
        }
        return true;
    }
    
    // Number validation
    number(value, fieldName = 'Значение') {
        if (isNaN(value) || value === '') {
            this.errors[fieldName] = 'Должно быть числом';
            return false;
        }
        return true;
    }
    
    // Min value
    min(value, min, fieldName) {
        if (parseFloat(value) < min) {
            this.errors[fieldName] = `Минимальное значение - ${min}`;
            return false;
        }
        return true;
    }
    
    // Max value
    max(value, max, fieldName) {
        if (parseFloat(value) > max) {
            this.errors[fieldName] = `Максимальное значение - ${max}`;
            return false;
        }
        return true;
    }
    
    // URL validation
    url(value, fieldName = 'URL') {
        try {
            new URL(value);
            return true;
        } catch {
            this.errors[fieldName] = 'Некорректный URL';
            return false;
        }
    }
    
    // File validation
    file(file, options = {}) {
        const {
            maxSize = CONFIG.maxFileSize,
            allowedTypes = CONFIG.allowedFileTypes,
            fieldName = 'Файл'
        } = options;
        
        if (file.size > maxSize) {
            this.errors[fieldName] = `Размер файла не должен превышать ${this.formatBytes(maxSize)}`;
            return false;
        }
        
        const ext = '.' + file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(ext)) {
            this.errors[fieldName] = `Допустимые форматы: ${allowedTypes.join(', ')}`;
            return false;
        }
        
        return true;
    }
    
    // Custom pattern
    pattern(value, pattern, fieldName, message = 'Неверный формат') {
        if (!pattern.test(value)) {
            this.errors[fieldName] = message;
            return false;
        }
        return true;
    }
    
    // Get all errors
    getErrors() {
        return this.errors;
    }
    
    // Has errors
    hasErrors() {
        return Object.keys(this.errors).length > 0;
    }
    
    // Clear errors
    clearErrors() {
        this.errors = {};
    }
    
    // Format bytes
    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Show errors in form
    showErrors(formElement) {
        // Clear previous errors
        formElement.querySelectorAll('.error-message').forEach(el => el.remove());
        formElement.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        
        // Show new errors
        Object.keys(this.errors).forEach(fieldName => {
            const field = formElement.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('error');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = this.errors[fieldName];
                
                field.parentNode.appendChild(errorDiv);
            }
        });
    }
}

// Form field configuration
class FormField {
    constructor(config) {
        this.name = config.name;
        this.label = config.label;
        this.type = config.type || 'text';
        this.required = config.required || false;
        this.placeholder = config.placeholder || '';
        this.value = config.value || '';
        this.options = config.options || [];
        this.validation = config.validation || {};
        this.helpText = config.helpText || '';
    }
    
    render() {
        let html = `<div class="form-group" data-field="${this.name}">`;
        html += `<label for="${this.name}">`;
        html += this.label;
        if (this.required) {
            html += ' <span class="required-mark">*</span>';
        }
        html += '</label>';
        
        switch (this.type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
            case 'url':
            case 'date':
                html += `<input type="${this.type}" 
                         id="${this.name}" 
                         name="${this.name}" 
                         class="form-control" 
                         placeholder="${this.placeholder}"
                         value="${this.value}"
                         ${this.required ? 'required' : ''}>`;
                break;
                
            case 'textarea':
                html += `<textarea id="${this.name}" 
                         name="${this.name}" 
                         class="form-control" 
                         placeholder="${this.placeholder}"
                         ${this.required ? 'required' : ''}>${this.value}</textarea>`;
                break;
                
            case 'select':
                html += `<select id="${this.name}" name="${this.name}" class="form-control" ${this.required ? 'required' : ''}>`;
                this.options.forEach(opt => {
                    const selected = opt.value === this.value ? 'selected' : '';
                    html += `<option value="${opt.value}" ${selected}>${opt.label}</option>`;
                });
                html += '</select>';
                break;
                
            case 'checkbox':
                html += `<label class="checkbox-label">
                         <input type="checkbox" id="${this.name}" name="${this.name}" ${this.value ? 'checked' : ''}>
                         <span>${this.placeholder}</span>
                         </label>`;
                break;
                
            case 'file':
                html += `<input type="file" id="${this.name}" name="${this.name}" class="form-control" ${this.required ? 'required' : ''}>`;
                break;
        }
        
        if (this.helpText) {
            html += `<small class="help-text">${this.helpText}</small>`;
        }
        
        html += '</div>';
        return html;
    }
    
    validate(value) {
        const validator = new Validator();
        let isValid = true;
        
        if (this.required) {
            isValid = validator.required(value, this.label) && isValid;
        }
        
        if (value && this.validation) {
            if (this.type === 'email') {
                isValid = validator.email(value, this.label) && isValid;
            }
            if (this.type === 'tel') {
                isValid = validator.phone(value, this.label) && isValid;
            }
            if (this.validation.minLength) {
                isValid = validator.minLength(value, this.validation.minLength, this.label) && isValid;
            }
            if (this.validation.maxLength) {
                isValid = validator.maxLength(value, this.validation.maxLength, this.label) && isValid;
            }
            if (this.validation.min !== undefined) {
                isValid = validator.min(value, this.validation.min, this.label) && isValid;
            }
            if (this.validation.max !== undefined) {
                isValid = validator.max(value, this.validation.max, this.label) && isValid;
            }
        }
        
        return { isValid, errors: validator.getErrors() };
    }
}