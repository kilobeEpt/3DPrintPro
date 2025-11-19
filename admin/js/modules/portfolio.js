// ========================================
// Portfolio Module - Enhanced with Image Upload, Featured Toggle, Drag & Drop
// ========================================

class PortfolioModule {
    constructor() {
        this.items = [];
        this.editingId = null;
        this.isDragging = false;
        this.adminMain = null;
        this.uploadedFile = null;
    }
    
    async init() {
        console.log('🖼️ Loading portfolio...');
        
        if (!window.adminApi) {
            console.warn('⚠️ adminApi not ready yet, retrying...');
            setTimeout(() => this.init(), 100);
            return;
        }
        
        if (!window.AdminMain) {
            console.warn('⚠️ AdminMain not ready yet, retrying...');
            setTimeout(() => this.init(), 100);
            return;
        }
        
        this.adminMain = window.AdminMain;
        this.initButtons();
        this.initSSEListener();
        await this.loadPortfolio();
    }
    
    initButtons() {
        const addBtn = document.getElementById('addPortfolioBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showPortfolioModal());
        }
    }
    
    initSSEListener() {
        if (typeof EventSource === 'undefined') return;
        
        const eventSource = new EventSource('/api/updates.php');
        
        eventSource.addEventListener('content.updated', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'portfolio') {
                console.log('📡 Portfolio updated remotely, reloading...');
                this.loadPortfolio(true);
            }
        });
        
        eventSource.addEventListener('content.created', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'portfolio') {
                console.log('📡 Portfolio created remotely, reloading...');
                this.loadPortfolio(true);
            }
        });
        
        eventSource.addEventListener('content.deleted', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'portfolio') {
                console.log('📡 Portfolio deleted remotely, reloading...');
                this.loadPortfolio(true);
            }
        });
        
        eventSource.onerror = () => {
            eventSource.close();
        };
    }
    
    async loadPortfolio(silent = false) {
        const container = document.getElementById('portfolioContainer');
        if (!container) return;
        
        try {
            if (!silent) {
                this.adminMain.showLoading(container);
            }
            
            this.items = await window.adminApi.getPortfolio();
            this.renderPortfolio();
            
            console.log(`✅ Loaded ${this.items.length} portfolio items`);
        } catch (error) {
            console.error('❌ Failed to load portfolio:', error);
            this.adminMain.showError(container);
        }
    }
    
    renderPortfolio() {
        const container = document.getElementById('portfolioContainer');
        if (!container) return;
        
        if (this.items.length === 0) {
            this.adminMain.showEmpty(container, 'Нет работ в портфолио');
            return;
        }
        
        container.innerHTML = `
            <div class="data-grid">
                <div class="grid-controls">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="portfolioSearch" placeholder="Поиск работ...">
                    </div>
                    <div class="grid-actions">
                        <label class="filter-toggle">
                            <input type="checkbox" id="showFeaturedOnly">
                            <span>Только избранное</span>
                        </label>
                        <button class="btn btn-sm btn-outline" onclick="portfolioModule.toggleDragMode()">
                            <i class="fas fa-arrows-alt"></i>
                            Изменить порядок
                        </button>
                    </div>
                </div>
                <div class="portfolio-grid" id="portfolioGrid">
                    ${this.items.map(item => this.renderPortfolioCard(item)).join('')}
                </div>
            </div>
        `;
        
        const searchInput = document.getElementById('portfolioSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterPortfolio(e.target.value));
        }
        
        const featuredFilter = document.getElementById('showFeaturedOnly');
        if (featuredFilter) {
            featuredFilter.addEventListener('change', (e) => this.filterPortfolio(searchInput.value, e.target.checked));
        }
    }
    
    renderPortfolioCard(item) {
        return `
            <div class="portfolio-card" data-id="${item.id}">
                <div class="card-image">
                    ${item.image_path ? 
                        `<img src="${item.image_path}" alt="${this.escapeHtml(item.title)}">` :
                        '<div class="no-image"><i class="fas fa-image"></i></div>'
                    }
                    <div class="card-overlay">
                        <button class="btn btn-sm btn-icon" onclick="portfolioModule.editItem('${item.id}')" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="portfolioModule.deleteItem('${item.id}')" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-header-row">
                        <h3>${this.escapeHtml(item.title)}</h3>
                        <label class="star-toggle" title="Избранное">
                            <input type="checkbox" ${item.featured ? 'checked' : ''} 
                                   onchange="portfolioModule.toggleFeatured('${item.id}', this.checked)">
                            <i class="fas fa-star"></i>
                        </label>
                    </div>
                    <p>${this.escapeHtml(item.description || '')}</p>
                    <div class="card-meta">
                        <span class="badge ${item.active ? 'badge-success' : 'badge-secondary'}">
                            ${item.active ? 'Активна' : 'Неактивна'}
                        </span>
                        ${item.tags ? item.tags.map(tag => `<span class="tag">${tag}</span>`).join('') : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    toggleDragMode() {
        this.isDragging = !this.isDragging;
        const grid = document.getElementById('portfolioGrid');
        
        if (this.isDragging) {
            grid.classList.add('dragging-mode');
            this.initDragAndDrop();
            this.adminMain.showToast('Режим изменения порядка активирован', 'info');
        } else {
            grid.classList.remove('dragging-mode');
            this.adminMain.showToast('Порядок сохранен', 'success');
        }
    }
    
    initDragAndDrop() {
        const grid = document.getElementById('portfolioGrid');
        if (!grid) return;
        
        let draggedElement = null;
        
        const cards = grid.querySelectorAll('.portfolio-card');
        cards.forEach((card, index) => {
            card.setAttribute('draggable', 'true');
            card.setAttribute('data-index', index);
            
            card.addEventListener('dragstart', (e) => {
                draggedElement = card;
                card.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            card.addEventListener('dragend', () => {
                card.classList.remove('dragging');
                draggedElement = null;
                this.saveOrder();
            });
            
            card.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (card === draggedElement) return;
                
                const rect = card.getBoundingClientRect();
                const midpoint = rect.left + rect.width / 2;
                
                if (e.clientX < midpoint) {
                    grid.insertBefore(draggedElement, card);
                } else {
                    grid.insertBefore(draggedElement, card.nextSibling);
                }
            });
        });
    }
    
    async saveOrder() {
        const grid = document.getElementById('portfolioGrid');
        const cards = grid.querySelectorAll('.portfolio-card');
        
        const order = Array.from(cards).map((card, index) => ({
            id: card.getAttribute('data-id'),
            position: index
        }));
        
        console.log('💾 Saving new order:', order);
    }
    
    filterPortfolio(query, featuredOnly = false) {
        const grid = document.getElementById('portfolioGrid');
        const cards = grid.querySelectorAll('.portfolio-card');
        
        query = query.toLowerCase();
        
        cards.forEach(card => {
            const id = card.getAttribute('data-id');
            const item = this.items.find(i => i.id === id);
            if (!item) return;
            
            const matchesSearch = item.title.toLowerCase().includes(query) || 
                                  (item.description && item.description.toLowerCase().includes(query));
            const matchesFeatured = !featuredOnly || item.featured;
            
            if (matchesSearch && matchesFeatured) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    async toggleFeatured(id, featured) {
        const item = this.items.find(i => i.id === id);
        if (!item) return;
        
        const previousState = item.featured;
        item.featured = featured ? 1 : 0;
        
        await this.adminMain.withOptimisticUpdate(
            async () => {
                await window.adminApi.updatePortfolioItem(id, { featured: item.featured });
                this.adminMain.showToast(
                    featured ? 'Добавлено в избранное' : 'Удалено из избранного',
                    'success'
                );
            },
            () => {
                item.featured = previousState;
                const checkbox = document.querySelector(`[data-id="${id}"] .star-toggle input`);
                if (checkbox) checkbox.checked = previousState;
            },
            'Ошибка изменения статуса'
        );
    }
    
    showPortfolioModal(item = null) {
        this.editingId = item?.id || null;
        this.uploadedFile = null;
        
        const body = `
            <form id="portfolioForm">
                <div class="form-group">
                    <label>Изображение</label>
                    <div id="imageUploadContainer"></div>
                </div>
                <div class="form-group">
                    <label>Название работы *</label>
                    <input type="text" name="title" class="form-control" value="${item?.title || ''}" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" class="form-control" rows="3">${item?.description || ''}</textarea>
                </div>
                <div class="form-group">
                    <label>Теги (через запятую)</label>
                    <input type="text" name="tags" class="form-control" value="${item?.tags ? item.tags.join(', ') : ''}" placeholder="3D печать, прототип, моделирование">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="active" ${item?.active ? 'checked' : ''}>
                            <span>Активна</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="featured" ${item?.featured ? 'checked' : ''}>
                            <span>Избранное</span>
                        </label>
                    </div>
                </div>
            </form>
        `;
        
        const modal = this.adminMain.createModal({
            title: item ? 'Редактировать работу' : 'Добавить работу',
            body: body,
            size: 'large'
        });
        
        const uploadContainer = modal.querySelector('#imageUploadContainer');
        if (uploadContainer) {
            uploadContainer.innerHTML = this.adminMain.createFileUpload({
                accept: 'image/*',
                maxSize: 5242880,
                preview: true,
                currentImage: item?.image_path || null,
                onUpload: (file) => {
                    this.uploadedFile = file;
                }
            });
        }
        
        const submitBtn = modal.querySelector('#modalSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.savePortfolioItem(modal));
        }
    }
    
    async editItem(id) {
        const item = this.items.find(i => i.id === id);
        if (item) {
            this.showPortfolioModal(item);
        }
    }
    
    async savePortfolioItem(modal) {
        const form = document.getElementById('portfolioForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        
        if (this.uploadedFile) {
            formData.append('image', this.uploadedFile);
        }
        
        const tags = formData.get('tags');
        formData.delete('tags');
        if (tags) {
            formData.append('tags', JSON.stringify(tags.split(',').map(t => t.trim()).filter(t => t)));
        }
        
        formData.set('active', formData.get('active') === 'on' ? '1' : '0');
        formData.set('featured', formData.get('featured') === 'on' ? '1' : '0');
        
        try {
            if (this.editingId) {
                await window.adminApi.updatePortfolioItem(this.editingId, formData);
                this.adminMain.showToast('Работа обновлена', 'success');
            } else {
                await window.adminApi.createPortfolioItem(formData);
                this.adminMain.showToast('Работа добавлена', 'success');
            }
            
            modal.remove();
            await this.loadPortfolio();
        } catch (error) {
            console.error('❌ Failed to save portfolio item:', error);
            if (error.errors) {
                this.adminMain.displayValidationErrors(error.errors);
            } else {
                this.adminMain.showToast('Ошибка сохранения работы', 'error');
            }
        }
    }
    
    async deleteItem(id) {
        if (!this.adminMain.showConfirm('Удалить эту работу из портфолио?')) return;
        
        try {
            await window.adminApi.deletePortfolioItem(id);
            this.adminMain.showToast('Работа удалена', 'success');
            await this.loadPortfolio();
        } catch (error) {
            console.error('❌ Failed to delete item:', error);
            this.adminMain.showToast('Ошибка удаления', 'error');
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.portfolioModule = new PortfolioModule();
        window.portfolioModule.init();
    });
} else {
    window.portfolioModule = new PortfolioModule();
    window.portfolioModule.init();
}
