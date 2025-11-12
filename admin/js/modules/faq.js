// FAQ Module
class FAQModule {
    constructor() { this.items = []; }
    async init() {
        console.log('❓ Loading FAQ...');
        if (!window.adminApi) {
            console.warn('⚠️ adminApi not ready yet, retrying...');
            setTimeout(() => this.init(), 100);
            return;
        }
        const btn = document.getElementById('addFAQBtn');
        if (btn) btn.addEventListener('click', () => AdminMain.prototype.showToast('Функция в разработке', 'info'));
        await this.loadFAQ();
    }
    async loadFAQ() {
        const container = document.getElementById('faqContainer');
        if (!container) return;
        try {
            AdminMain.prototype.showLoading(container);
            this.items = await window.adminApi.getFAQ();
            if (this.items.length === 0) { AdminMain.prototype.showEmpty(container, 'Нет вопросов'); return; }
            container.innerHTML = this.items.map(item => `<div class="faq-card"><h4>${item.question}</h4><p>${item.answer}</p></div>`).join('');
            console.log(`✅ Loaded ${this.items.length} FAQ items`);
        } catch (error) { console.error('❌ Failed to load FAQ:', error); AdminMain.prototype.showError(container); }
    }
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', () => { window.faqModule = new FAQModule(); window.faqModule.init(); }); } else { window.faqModule = new FAQModule(); window.faqModule.init(); }
