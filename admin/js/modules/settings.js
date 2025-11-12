// Settings Module
class SettingsModule {
    constructor() { this.settings = {}; }
    async init() {
        console.log('⚙️ Loading settings...');
        const btn = document.getElementById('saveSettingsBtn');
        if (btn) btn.addEventListener('click', () => this.saveSettings());
        await this.loadSettings();
    }
    async loadSettings() {
        try {
            this.settings = await adminApi.getSettings();
            this.populateForm();
            console.log('✅ Settings loaded');
        } catch (error) { console.error('❌ Failed to load settings:', error); AdminMain.prototype.showToast('Ошибка загрузки настроек', 'error'); }
    }
    populateForm() {
        Object.entries(this.settings).forEach(([key, value]) => {
            const input = document.querySelector(`[name="${key}"]`);
            if (input) input.value = value;
        });
    }
    async saveSettings() {
        const form = document.getElementById('settingsForm');
        if (!form) return;
        const formData = new FormData(form);
        const settings = Object.fromEntries(formData.entries());
        try {
            await adminApi.updateSettings(settings);
            AdminMain.prototype.showToast('Настройки сохранены', 'success');
        } catch (error) { console.error('❌ Failed to save settings:', error); AdminMain.prototype.showToast('Ошибка сохранения настроек', 'error'); }
    }
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', () => { window.settingsModule = new SettingsModule(); window.settingsModule.init(); }); } else { window.settingsModule = new SettingsModule(); window.settingsModule.init(); }
