/**
 * Simple Order Form Handler
 * NO API dependencies, NO old classes, plain fetch()
 */

(function() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOrderForm);
    } else {
        initOrderForm();
    }
    
    function initOrderForm() {
        const form = document.getElementById('order-form');
        if (!form) return;
        
        // Add honeypot field
        addHoneypot(form);
        
        // Add submit handler
        form.addEventListener('submit', handleSubmit);
        
        // Add file size display
        const fileInput = form.querySelector('[name="files"]');
        if (fileInput) {
            fileInput.addEventListener('change', displayFileInfo);
        }
    }
    
    function addHoneypot(form) {
        const honeypot = document.createElement('input');
        honeypot.type = 'text';
        honeypot.name = 'website';
        honeypot.style.cssText = 'position: absolute; left: -9999px; opacity: 0; pointer-events: none;';
        honeypot.tabIndex = -1;
        honeypot.autocomplete = 'off';
        honeypot.setAttribute('aria-hidden', 'true');
        form.appendChild(honeypot);
    }
    
    function displayFileInfo(e) {
        const file = e.target.files[0];
        const infoEl = document.getElementById('file-info');
        
        if (file && infoEl) {
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            infoEl.textContent = `Выбран файл: ${file.name} (${sizeMB} МБ)`;
            infoEl.style.display = 'block';
        } else if (infoEl) {
            infoEl.textContent = '';
            infoEl.style.display = 'none';
        }
    }
    
    async function handleSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const messageEl = document.getElementById('form-message');
        
        // Clear previous messages
        clearMessage(messageEl);
        clearErrors(form);
        
        // Get form data
        const formData = {
            fio: form.querySelector('[name="fio"]').value.trim(),
            email: form.querySelector('[name="email"]').value.trim(),
            phone: form.querySelector('[name="phone"]').value.trim(),
            telegram: form.querySelector('[name="telegram"]').value.trim(),
            service: form.querySelector('[name="service"]').value,
            description: form.querySelector('[name="description"]').value.trim(),
            website: form.querySelector('[name="website"]')?.value || ''
        };
        
        // Honeypot check (silent success for bots)
        if (formData.website) {
            showMessage(messageEl, '✅ Спасибо! Мы свяжемся с вами в ближайшее время', 'success');
            setTimeout(() => form.reset(), 1000);
            return;
        }
        
        // Validate
        const errors = validateForm(formData);
        if (Object.keys(errors).length > 0) {
            displayErrors(form, errors);
            showMessage(messageEl, '❌ Пожалуйста, исправьте ошибки в форме', 'error');
            return;
        }
        
        // Set loading state
        setLoading(submitBtn, true);
        
        try {
            // Prepare data for sending
            const sendData = {
                name: formData.fio,
                email: formData.email,
                phone: formData.phone,
                service: formData.service,
                description: `${formData.description}\n\nTelegram: @${formData.telegram}`
            };
            
            // Handle file upload separately if needed
            const fileInput = form.querySelector('[name="files"]');
            const hasFile = fileInput && fileInput.files.length > 0;
            
            let response;
            
            if (hasFile) {
                // Use FormData for file uploads
                const formDataObj = new FormData();
                formDataObj.append('name', sendData.name);
                formDataObj.append('email', sendData.email);
                formDataObj.append('phone', sendData.phone);
                formDataObj.append('service', sendData.service);
                formDataObj.append('description', sendData.description);
                formDataObj.append('files', fileInput.files[0]);
                
                response = await fetch('/order-submit.php', {
                    method: 'POST',
                    body: formDataObj
                });
            } else {
                // Use JSON for simple data
                response = await fetch('/order-submit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(sendData)
                });
            }
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showMessage(messageEl, '✅ Спасибо! Мы свяжемся с вами в ближайшее время', 'success');
                form.reset();
                clearFileInfo();
                
                // Scroll to message
                messageEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                const errorMsg = result.error || 'Ошибка отправки. Попробуйте ещё раз';
                showMessage(messageEl, '❌ ' + errorMsg, 'error');
                
                // Display field-specific errors if any
                if (result.details && typeof result.details === 'object') {
                    displayErrors(form, result.details);
                }
            }
            
        } catch (error) {
            console.error('Form submission error:', error);
            showMessage(messageEl, '❌ Ошибка отправки. Попробуйте ещё раз', 'error');
        } finally {
            setLoading(submitBtn, false);
        }
    }
    
    function validateForm(data) {
        const errors = {};
        
        // ФИО validation
        if (!data.fio) {
            errors.fio = 'ФИО обязательно для заполнения';
        } else if (data.fio.length < 2) {
            errors.fio = 'ФИО должно содержать минимум 2 символа';
        } else if (data.fio.length > 100) {
            errors.fio = 'ФИО не должно превышать 100 символов';
        }
        
        // Email validation
        if (!data.email) {
            errors.email = 'Email обязателен для заполнения';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors.email = 'Неверный формат email';
        }
        
        // Phone validation
        if (!data.phone) {
            errors.phone = 'Телефон обязателен для заполнения';
        } else if (data.phone.length < 10) {
            errors.phone = 'Телефон должен содержать минимум 10 символов';
        }
        
        // Telegram validation
        if (!data.telegram) {
            errors.telegram = 'Telegram username обязателен для заполнения';
        } else if (data.telegram.startsWith('@')) {
            errors.telegram = 'Уберите символ @ из начала username';
        } else if (data.telegram.length < 5) {
            errors.telegram = 'Telegram username должен содержать минимум 5 символов';
        }
        
        // Service validation
        if (!data.service) {
            errors.service = 'Выберите услугу';
        }
        
        // Description validation
        if (!data.description) {
            errors.description = 'Описание обязательно для заполнения';
        } else if (data.description.length < 10) {
            errors.description = 'Описание должно содержать минимум 10 символов';
        } else if (data.description.length > 2000) {
            errors.description = 'Описание не должно превышать 2000 символов';
        }
        
        return errors;
    }
    
    function displayErrors(form, errors) {
        for (const [field, message] of Object.entries(errors)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input) continue;
            
            // Add error class
            input.classList.add('error');
            
            // Create error message
            const errorEl = document.createElement('div');
            errorEl.className = 'error-message';
            errorEl.textContent = message;
            
            // Insert after input's parent
            const parent = input.closest('.form-group') || input.parentElement;
            if (parent && !parent.querySelector('.error-message')) {
                parent.appendChild(errorEl);
            }
        }
    }
    
    function clearErrors(form) {
        // Remove error classes
        form.querySelectorAll('.error').forEach(el => {
            el.classList.remove('error');
        });
        
        // Remove error messages
        form.querySelectorAll('.error-message').forEach(el => {
            el.remove();
        });
    }
    
    function showMessage(container, text, type) {
        if (!container) return;
        
        container.textContent = text;
        container.className = 'form-message ' + type;
        container.style.display = 'block';
        
        // Auto-hide success messages after 10 seconds
        if (type === 'success') {
            setTimeout(() => {
                container.style.display = 'none';
            }, 10000);
        }
    }
    
    function clearMessage(container) {
        if (!container) return;
        container.textContent = '';
        container.className = 'form-message';
        container.style.display = 'none';
    }
    
    function setLoading(button, loading) {
        if (!button) return;
        
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || 'Отправить заказ';
        }
    }
    
    function clearFileInfo() {
        const infoEl = document.getElementById('file-info');
        if (infoEl) {
            infoEl.textContent = '';
            infoEl.style.display = 'none';
        }
    }
    
})();
