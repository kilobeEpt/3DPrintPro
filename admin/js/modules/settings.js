// Settings Module
class SettingsModule {
    constructor() { 
        this.settings = {};
        this.tokenVisible = false;
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
        
        await this.loadSettings();
    }
    
    async loadSettings() {
        try {
            if (!window.adminApi) {
                console.warn('⚠️ adminApi not ready yet');
                setTimeout(() => this.loadSettings(), 100);
                return;
            }
            
            this.settings = await window.adminApi.getSettings();
            this.populateForm();
            console.log('✅ Settings loaded');
        } catch (error) {
            console.error('❌ Failed to load settings:', error);
            AdminMain.prototype.showToast('Ошибка загрузки настроек', 'error');
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
                AdminMain.prototype.showToast('Тестовое сообщение отправлено', 'success');
            } else {
                const errorMsg = data.error || 'Неизвестная ошибка';
                resultSpan.innerHTML = `<span class="text-danger"><i class="fas fa-times-circle"></i> Ошибка: ${errorMsg}</span>`;
                AdminMain.prototype.showToast(`Ошибка: ${errorMsg}`, 'error');
            }
        } catch (error) {
            console.error('❌ Failed to test Telegram:', error);
            resultSpan.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> Ошибка сети</span>';
            AdminMain.prototype.showToast('Ошибка при отправке тестового сообщения', 'error');
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
            
            await window.adminApi.updateSettings(settings);
            if (!silent) {
                AdminMain.prototype.showToast('Настройки сохранены', 'success');
            }
            console.log('✅ Settings saved');
        } catch (error) {
            console.error('❌ Failed to save settings:', error);
            if (!silent) {
                AdminMain.prototype.showToast('Ошибка сохранения настроек', 'error');
            }
            throw error;
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
