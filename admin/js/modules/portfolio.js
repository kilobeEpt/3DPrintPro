// Portfolio Module - Portfolio Management & CRUD
class PortfolioModule {
    constructor() { this.items = []; this.editingId = null; }
    async init() { console.log('🖼️ Loading portfolio...'); this.initButtons(); await this.loadPortfolio(); }
    initButtons() { const btn = document.getElementById('addPortfolioBtn'); if (btn) btn.addEventListener('click', () => this.showModal()); }
    async loadPortfolio() {
        const container = document.getElementById('portfolioContainer');
        if (!container) return;
        try {
            AdminMain.prototype.showLoading(container);
            this.items = await adminApi.getPortfolio();
            this.renderPortfolio();
            console.log(`✅ Loaded ${this.items.length} portfolio items`);
        } catch (error) { console.error('❌ Failed to load portfolio:', error); AdminMain.prototype.showError(container); }
    }
    renderPortfolio() {
        const container = document.getElementById('portfolioContainer');
        if (!container) return;
        if (this.items.length === 0) { AdminMain.prototype.showEmpty(container, 'Нет работ в портфолио'); return; }
        container.innerHTML = this.items.map(item => `
            <div class="portfolio-card">
                ${item.image_url ? `<img src="${item.image_url}" alt="${item.title}">` : '<div class="no-image"><i class="fas fa-image"></i></div>'}
                <div class="portfolio-info">
                    <h3>${this.escapeHtml(item.title)}</h3>
                    <p>${this.escapeHtml(item.description || '')}</p>
                    <div class="portfolio-actions">
                        <button class="btn btn-sm" onclick="portfolioModule.editItem('${item.id}')"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" onclick="portfolioModule.deleteItem('${item.id}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    showModal(item = null) { AdminMain.prototype.showToast('Функция в разработке', 'info'); }
    async editItem(id) { const item = this.items.find(i => i.id === id); if (item) this.showModal(item); }
    async deleteItem(id) {
        if (!AdminMain.prototype.showConfirm('Удалить эту работу из портфолио?')) return;
        try {
            await adminApi.deletePortfolioItem(id);
            AdminMain.prototype.showToast('Работа удалена', 'success');
            await this.loadPortfolio();
        } catch (error) { console.error('❌ Failed to delete item:', error); AdminMain.prototype.showToast('Ошибка удаления', 'error'); }
    }
    escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', () => { window.portfolioModule = new PortfolioModule(); window.portfolioModule.init(); }); } else { window.portfolioModule = new PortfolioModule(); window.portfolioModule.init(); }
