// Settings Module
class SettingsModule {
    constructor() { 
        this.settings = {};
        this.tokenVisible = false;
        this.auditHistory = [];
    }
    
    async init() {
        console.log('⚙️ Loading settings...');
        
        // Save button
        const saveBtn = document.getElementById('saveSettingsBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveSettings());
        }
        
        // Toggle token visibility button
        const toggleBtn = document.getElementById('toggleTokenBtn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggleTokenVisibility());
        }
        
        // Test Telegram button
        const testBtn = document.getElementById('testTelegramBtn');
        if (testBtn) {
            testBtn.addEventListener('click', () => this.testTelegram());
        }
        
        // View Audit History button
        const auditBtn = document.getElementById('viewAuditBtn');
        if (auditBtn) {
            auditBtn.addEventListener('click', () => this.showAuditHistory());
        }
        
        await this.loadSettings();
    }
    
    async loadSettings() {
        try {
            if (!window.adminApi) {
                console.warn('⚠️ adminApi not ready yet');
                setTimeout(() => this.loadSettings(), 100);
                return;
            }
            
            const response = await window.adminApi.get('/api/settings.php');
            
            // Handle new response format with cache info
            if (response.settings) {
                this.settings = response.settings;
                
                // Log cache info if available
                if (response.cache_info) {
                    console.log('✅ Settings loaded (cache TTL: ' + response.cache_info.ttl + 's)');
                }
            } else {
                // Fallback to legacy format
                this.settings = response;
            }
            
            this.populateForm();
            this.showCacheStatus(response.cache_info);
            console.log('✅ Settings loaded');
        } catch (error) {
            console.error('❌ Failed to load settings:', error);
            this.showError('Ошибка загрузки настроек: ' + (error.message || 'Неизвестная ошибка'));
        }
    }
    
    populateForm() {
        Object.entries(this.settings).forEach(([key, value]) => {
            const input = document.querySelector(`[name="${key}"]`);
            if (!input) return;
            
            if (input.type === 'checkbox') {
                input.checked = !!value || value === '1' || value === 1;
            } else {
                input.value = value || '';
            }
        });
    }
    
    showCacheStatus(cacheInfo) {
        const statusEl = document.getElementById('cacheStatus');
        if (!statusEl || !cacheInfo) return;
        
        statusEl.innerHTML = `
            <small class="text-muted">
                <i class="fas fa-database"></i> Кэш активен (TTL: ${cacheInfo.ttl}с)
            </small>
        `;
    }
    
    toggleTokenVisibility() {
        const input = document.getElementById('telegram_bot_token');
        const icon = document.querySelector('#toggleTokenBtn i');
        if (!input || !icon) return;
        
        this.tokenVisible = !this.tokenVisible;
        input.type = this.tokenVisible ? 'text' : 'password';
        icon.className = this.tokenVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
    }
    
    async testTelegram() {
        const btn = document.getElementById('testTelegramBtn');
        const resultSpan = document.getElementById('telegramTestResult');
        
        if (!btn || !resultSpan) return;
        
        // Save settings first
        await this.saveSettings(true);
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
        resultSpan.innerHTML = '';
        
        try {
            const response = await fetch('/api/telegram-test.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.ADMIN_SESSION?.csrfToken || ''
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                resultSpan.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Сообщение отправлено!</span>';
                this.showSuccess('Тестовое сообщение отправлено');
            } else {
                const errorMsg = data.error || 'Неизвестная ошибка';
                resultSpan.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle"></i> Ошибка: ${errorMsg}</span>`;
                this.showError(`Ошибка: ${errorMsg}`);
            }
        } catch (error) {
            console.error('❌ Failed to test Telegram:', error);
            resultSpan.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Ошибка сети</span>';
            this.showError('Ошибка при отправке тестового сообщения');
        } finally {
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Отправить тестовое сообщение';
            
            // Clear result after 5 seconds
            setTimeout(() => {
                if (resultSpan) resultSpan.innerHTML = '';
            }, 5000);
        }
    }
    
    async saveSettings(silent = false) {
        const form = document.getElementById('settingsForm');
        if (!form) return;
        
        const formData = new FormData(form);
        const settings = {};
        
        // Process all form fields
        for (const [key, value] of formData.entries()) {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.type === 'checkbox') {
                settings[key] = input.checked ? '1' : '0';
            } else {
                settings[key] = value;
            }
        }
        
        // Add unchecked checkboxes as '0'
        form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            if (!formData.has(checkbox.name)) {
                settings[checkbox.name] = '0';
            }
        });
        
        try {
            if (!window.adminApi) {
                throw new Error('adminApi not ready');
            }
            
            const response = await window.adminApi.post('/api/settings.php', settings);
            
            // Handle validation errors in response
            if (response.validation_errors && Object.keys(response.validation_errors).length > 0) {
                this.showValidationErrors(response.validation_errors);
                if (!silent) {
                    this.showWarning('Настройки сохранены с ошибками валидации');
                }
            } else if (response.errors && Object.keys(response.errors).length > 0) {
                this.showValidationErrors(response.errors);
                if (!silent) {
                    this.showWarning('Некоторые настройки не удалось сохранить');
                }
            } else {
                if (!silent) {
                    this.showSuccess('Настройки сохранены');
                }
            }
            
            // Show cache invalidation status
            if (response.cache_invalidated && !silent) {
                console.log('✅ Cache invalidated');
            }
            
            console.log('✅ Settings saved:', response);
        } catch (error) {
            console.error('❌ Failed to save settings:', error);
            
            // Extract error message from API response
            let errorMsg = 'Ошибка сохранения настроек';
            if (error.response && error.response.error) {
                errorMsg = error.response.error;
            } else if (error.message) {
                errorMsg = error.message;
            }
            
            if (!silent) {
                this.showError(errorMsg);
            }
            throw error;
        }
    }
    
    showValidationErrors(errors) {
        const container = document.getElementById('validationErrors');
        if (!container) {
            // If no container, log to console
            console.warn('Validation errors:', errors);
            return;
        }
        
        const errorList = Object.entries(errors)
            .map(([key, error]) => `<li><strong>${key}:</strong> ${error}</li>`)
            .join('');
        
        container.innerHTML = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <strong>Ошибки валидации:</strong>
                <ul class="mb-0 mt-2">${errorList}</ul>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            container.innerHTML = '';
        }, 10000);
    }
    
    async showAuditHistory() {
        try {
            if (!window.adminApi) {
                throw new Error('adminApi not ready');
            }
            
            const response = await window.adminApi.get('/api/settings.php?audit=&limit=50');
            this.auditHistory = response.audit || [];
            
            this.renderAuditModal();
        } catch (error) {
            console.error('❌ Failed to load audit history:', error);
            this.showError('Ошибка загрузки истории изменений');
        }
    }
    
    renderAuditModal() {
        const modalHtml = `
            <div class="modal fade" id="auditModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-history"></i> История изменений настроек
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            ${this.renderAuditTable()}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                Закрыть
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if present
        const existingModal = document.getElementById('auditModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Append and show modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        $('#auditModal').modal('show');
    }
    
    renderAuditTable() {
        if (!this.auditHistory || this.auditHistory.length === 0) {
            return '<p class="text-muted text-center py-4">История изменений пуста</p>';
        }
        
        const rows = this.auditHistory.map(entry => {
            const date = new Date(entry.created_at);
            const formattedDate = date.toLocaleString('ru-RU');
            
            return `
                <tr>
                    <td><code>${entry.setting_key}</code></td>
                    <td><small class="text-muted">${this.formatValue(entry.old_value)}</small></td>
                    <td><small class="text-success">${this.formatValue(entry.new_value)}</small></td>
                    <td><span class="badge badge-info">${entry.changed_by || 'system'}</span></td>
                    <td><small>${formattedDate}</small></td>
                </tr>
            `;
        }).join('');
        
        return `
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Ключ</th>
                            <th>Старое значение</th>
                            <th>Новое значение</th>
                            <th>Изменил</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    formatValue(value) {
        if (value === null || value === undefined || value === '') {
            return '<em>(пусто)</em>';
        }
        
        if (typeof value === 'string' && value.length > 50) {
            return value.substring(0, 50) + '...';
        }
        
        return String(value);
    }
    
    showSuccess(message) {
        if (typeof AdminMain !== 'undefined' && AdminMain.prototype.showToast) {
            AdminMain.prototype.showToast(message, 'success');
        } else {
            alert(message);
        }
    }
    
    showError(message) {
        if (typeof AdminMain !== 'undefined' && AdminMain.prototype.showToast) {
            AdminMain.prototype.showToast(message, 'error');
        } else {
            alert(message);
        }
    }
    
    showWarning(message) {
        if (typeof AdminMain !== 'undefined' && AdminMain.prototype.showToast) {
            AdminMain.prototype.showToast(message, 'warning');
        } else {
            alert(message);
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.settingsModule = new SettingsModule();
        window.settingsModule.init();
    });
} else {
    window.settingsModule = new SettingsModule();
    window.settingsModule.init();
}
