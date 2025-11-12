// Content Module
class ContentModule {
    constructor() { this.blocks = []; }
    async init() {
        console.log('📄 Loading content blocks...');
        if (!window.adminApi) {
            console.warn('⚠️ adminApi not ready yet, retrying...');
            setTimeout(() => this.init(), 100);
            return;
        }
        const btn = document.getElementById('addContentBtn');
        if (btn) btn.addEventListener('click', () => AdminMain.prototype.showToast('Функция в разработке', 'info'));
        await this.loadContent();
    }
    async loadContent() {
        const container = document.getElementById('contentContainer');
        if (!container) return;
        try {
            AdminMain.prototype.showLoading(container);
            this.blocks = await window.adminApi.getContentBlocks();
            if (this.blocks.length === 0) { AdminMain.prototype.showEmpty(container, 'Нет контента'); return; }
            container.innerHTML = this.blocks.map(block => `<div class="content-card"><h4>${block.title}</h4><p>${block.content}</p></div>`).join('');
            console.log(`✅ Loaded ${this.blocks.length} content blocks`);
        } catch (error) { console.error('❌ Failed to load content:', error); AdminMain.prototype.showError(container); }
    }
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', () => { window.contentModule = new ContentModule(); window.contentModule.init(); }); } else { window.contentModule = new ContentModule(); window.contentModule.init(); }
