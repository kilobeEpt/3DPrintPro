/**
 * Order Form Handler with Telegram Integration
 * 
 * Handles form submission, validation, file uploads,
 * and displays appropriate user feedback.
 */

class OrderFormHandler {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.submitButton = null;
        this.originalButtonText = '';
        
        if (this.form) {
            this.init();
        }
    }
    
    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        this.submitButton = this.form.querySelector('button[type="submit"]');
        
        if (this.submitButton) {
            this.originalButtonText = this.submitButton.innerHTML;
        }
        
        // Add honeypot field (hidden)
        this.addHoneypotField();
    }
    
    addHoneypotField() {
        const honeypot = document.createElement('input');
        honeypot.type = 'text';
        honeypot.name = 'website';
        honeypot.style.cssText = 'position: absolute; left: -9999px; opacity: 0; pointer-events: none;';
        honeypot.tabIndex = -1;
        honeypot.autocomplete = 'off';
        this.form.appendChild(honeypot);
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        // Clear previous errors
        this.clearErrors();
        
        // Validate form
        const validationErrors = this.validateForm();
        
        if (Object.keys(validationErrors).length > 0) {
            this.displayErrors(validationErrors);
            return;
        }
        
        // Show loading state
        this.setLoadingState(true);
        
        try {
            // Prepare form data
            const formData = new FormData(this.form);
            
            // Submit form
            const response = await fetch('/order-submit.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.handleSuccess(result);
            } else {
                this.handleError(result);
            }
            
        } catch (error) {
            console.error('Form submission error:', error);
            this.handleError({
                error: 'Произошла ошибка при отправке формы',
                details: { message: error.message }
            });
        } finally {
            this.setLoadingState(false);
        }
    }
    
    validateForm() {
        const errors = {};
        const data = new FormData(this.form);
        
        // Name validation
        const name = data.get('name')?.trim();
        if (!name) {
            errors.name = 'Имя обязательно для заполнения';
        } else if (name.length < 2) {
            errors.name = 'Имя должно содержать минимум 2 символа';
        } else if (name.length > 100) {
            errors.name = 'Имя не должно превышать 100 символов';
        }
        
        // Email validation
        const email = data.get('email')?.trim();
        if (!email) {
            errors.email = 'Email обязателен для заполнения';
        } else if (!this.isValidEmail(email)) {
            errors.email = 'Неверный формат email';
        }
        
        // Phone validation
        const phone = data.get('phone')?.trim();
        if (!phone) {
            errors.phone = 'Телефон обязателен для заполнения';
        } else if (phone.length < 10) {
            errors.phone = 'Телефон должен содержать минимум 10 символов';
        }
        
        // Service validation
        const service = data.get('service')?.trim();
        if (!service) {
            errors.service = 'Выберите услугу';
        }
        
        // Description validation
        const description = data.get('description')?.trim();
        if (!description) {
            errors.description = 'Описание обязательно для заполнения';
        } else if (description.length < 10) {
            errors.description = 'Описание должно содержать минимум 10 символов';
        } else if (description.length > 2000) {
            errors.description = 'Описание не должно превышать 2000 символов';
        }
        
        return errors;
    }
    
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    displayErrors(errors) {
        for (const [field, message] of Object.entries(errors)) {
            const input = this.form.querySelector(`[name="${field}"]`);
            
            if (input) {
                // Add error class to input
                input.classList.add('error');
                
                // Create error message element
                const errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.textContent = message;
                errorElement.style.cssText = 'color: #e74c3c; font-size: 12px; margin-top: 5px;';
                
                // Insert after input or its parent
                const parent = input.closest('.form-group') || input.parentElement;
                parent.appendChild(errorElement);
            }
        }
        
        // Show notification
        this.showNotification('Пожалуйста, исправьте ошибки в форме', 'error');
    }
    
    clearErrors() {
        // Remove error classes
        this.form.querySelectorAll('.error').forEach(el => {
            el.classList.remove('error');
        });
        
        // Remove error messages
        this.form.querySelectorAll('.error-message').forEach(el => {
            el.remove();
        });
    }
    
    handleSuccess(result) {
        // Show success message
        this.showNotification(result.message, 'success');
        
        // Reset form
        this.form.reset();
        
        // Scroll to top
        this.form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Show detailed success message
        const successHtml = `
            <div style="padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #155724; margin: 0 0 10px 0;">
                    <i class="fas fa-check-circle"></i>
                    Спасибо! Заявка отправлена
                </h3>
                <p style="color: #155724; margin: 0;">
                    Мы получили вашу заявку и свяжемся с вами в ближайшее время.
                </p>
            </div>
        `;
        
        // Insert success message before form
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = successHtml;
        this.form.parentElement.insertBefore(tempDiv.firstElementChild, this.form);
        
        // Remove success message after 10 seconds
        setTimeout(() => {
            const successMessage = this.form.parentElement.querySelector('[style*="background: #d4edda"]');
            if (successMessage) {
                successMessage.remove();
            }
        }, 10000);
    }
    
    handleError(result) {
        const errorMessage = result.error || 'Произошла ошибка';
        
        // Show error notification
        this.showNotification(errorMessage, 'error');
        
        // Display field-specific errors
        if (result.details && typeof result.details === 'object') {
            this.displayErrors(result.details);
        }
    }
    
    setLoadingState(loading) {
        if (!this.submitButton) return;
        
        if (loading) {
            this.submitButton.disabled = true;
            this.submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
        } else {
            this.submitButton.disabled = false;
            this.submitButton.innerHTML = this.originalButtonText;
        }
    }
    
    showNotification(message, type = 'info') {
        // Check if notification system exists
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }
        
        // Fallback: create simple notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${colors[type] || colors.info};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}

// Add CSS animations
if (!document.getElementById('order-form-animations')) {
    const style = document.createElement('style');
    style.id = 'order-form-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .form-group input.error,
        .form-group textarea.error,
        .form-group select.error {
            border-color: #e74c3c !important;
        }
    `;
    document.head.appendChild(style);
}

// Auto-initialize for contact form
document.addEventListener('DOMContentLoaded', () => {
    // Initialize contact form if exists
    if (document.getElementById('contactForm')) {
        new OrderFormHandler('contactForm');
    }
});
