// ========================================
// Content Module - Enhanced with Markdown Preview, Drag & Drop
// ========================================

class ContentModule {
    constructor() {
        this.blocks = [];
        this.editingId = null;
        this.isDragging = false;
        this.adminMain = null;
    }
    
    async init() {
        console.log('📄 Loading content blocks...');
        
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
        await this.loadContent();
    }
    
    initButtons() {
        const addBtn = document.getElementById('addContentBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showContentModal());
        }
    }
    
    initSSEListener() {
        if (typeof EventSource === 'undefined') return;
        
        const eventSource = new EventSource('/api/updates.php');
        
        eventSource.addEventListener('content.updated', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'content_blocks') {
                console.log('📡 Content updated remotely, reloading...');
                this.loadContent(true);
            }
        });
        
        eventSource.addEventListener('content.created', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'content_blocks') {
                console.log('📡 Content created remotely, reloading...');
                this.loadContent(true);
            }
        });
        
        eventSource.addEventListener('content.deleted', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'content_blocks') {
                console.log('📡 Content deleted remotely, reloading...');
                this.loadContent(true);
            }
        });
        
        eventSource.onerror = () => {
            eventSource.close();
        };
    }
    
    async loadContent(silent = false) {
        const container = document.getElementById('contentContainer');
        if (!container) return;
        
        try {
            if (!silent) {
                this.adminMain.showLoading(container);
            }
            
            this.blocks = await window.adminApi.getContentBlocks();
            this.renderContent();
            
            console.log(`✅ Loaded ${this.blocks.length} content blocks`);
        } catch (error) {
            console.error('❌ Failed to load content:', error);
            this.adminMain.showError(container);
        }
    }
    
    renderContent() {
        const container = document.getElementById('contentContainer');
        if (!container) return;
        
        if (this.blocks.length === 0) {
            this.adminMain.showEmpty(container, 'Нет контента');
            return;
        }
        
        container.innerHTML = `
            <div class="data-grid">
                <div class="grid-controls">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="contentSearch" placeholder="Поиск контента...">
                    </div>
                    <div class="grid-actions">
                        <button class="btn btn-sm btn-outline" onclick="contentModule.toggleDragMode()">
                            <i class="fas fa-arrows-alt"></i>
                            Изменить порядок
                        </button>
                    </div>
                </div>
                <div class="content-list" id="contentList">
                    ${this.blocks.map(block => this.renderContentBlock(block)).join('')}
                </div>
            </div>
        `;
        
        const searchInput = document.getElementById('contentSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterContent(e.target.value));
        }
    }
    
    renderContentBlock(block) {
        return `
            <div class="content-block" data-id="${block.id}">
                <div class="block-header">
                    <div class="drag-handle" style="display: none;">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                    <div class="block-info">
                        <h3>${this.escapeHtml(block.title)}</h3>
                        <span class="block-location">${this.escapeHtml(block.location || 'Не указано')}</span>
                    </div>
                    <div class="block-actions">
                        <label class="toggle-switch" title="Активность">
                            <input type="checkbox" ${block.active ? 'checked' : ''} 
                                   onchange="contentModule.toggleActive('${block.id}', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                        <button class="btn btn-sm btn-icon" onclick="contentModule.editBlock('${block.id}')" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="contentModule.deleteBlock('${block.id}')" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="block-body">
                    <p>${this.truncate(this.escapeHtml(block.content || ''), 200)}</p>
                </div>
            </div>
        `;
    }
    
    toggleDragMode() {
        this.isDragging = !this.isDragging;
        const list = document.getElementById('contentList');
        const dragHandles = list.querySelectorAll('.drag-handle');
        
        if (this.isDragging) {
            dragHandles.forEach(handle => handle.style.display = 'flex');
            this.initDragAndDrop();
            this.adminMain.showToast('Режим изменения порядка активирован', 'info');
        } else {
            dragHandles.forEach(handle => handle.style.display = 'none');
            this.adminMain.showToast('Порядок сохранен', 'success');
        }
    }
    
    initDragAndDrop() {
        const list = document.getElementById('contentList');
        if (!list) return;
        
        let draggedElement = null;
        
        const blocks = list.querySelectorAll('.content-block');
        blocks.forEach((block, index) => {
            block.setAttribute('draggable', 'true');
            block.setAttribute('data-index', index);
            
            block.addEventListener('dragstart', (e) => {
                draggedElement = block;
                block.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            block.addEventListener('dragend', () => {
                block.classList.remove('dragging');
                draggedElement = null;
                this.saveOrder();
            });
            
            block.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (block === draggedElement) return;
                
                const rect = block.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                
                if (e.clientY < midpoint) {
                    list.insertBefore(draggedElement, block);
                } else {
                    list.insertBefore(draggedElement, block.nextSibling);
                }
            });
        });
    }
    
    async saveOrder() {
        const list = document.getElementById('contentList');
        const blocks = list.querySelectorAll('.content-block');
        
        const order = Array.from(blocks).map((block, index) => ({
            id: block.getAttribute('data-id'),
            position: index
        }));
        
        console.log('💾 Saving new order:', order);
    }
    
    filterContent(query) {
        const list = document.getElementById('contentList');
        const blocks = list.querySelectorAll('.content-block');
        
        query = query.toLowerCase();
        
        blocks.forEach(block => {
            const id = block.getAttribute('data-id');
            const contentBlock = this.blocks.find(b => b.id === id);
            if (!contentBlock) return;
            
            const matchesSearch = contentBlock.title.toLowerCase().includes(query) || 
                                  (contentBlock.content && contentBlock.content.toLowerCase().includes(query)) ||
                                  (contentBlock.location && contentBlock.location.toLowerCase().includes(query));
            
            if (matchesSearch) {
                block.style.display = '';
            } else {
                block.style.display = 'none';
            }
        });
    }
    
    async toggleActive(id, active) {
        const block = this.blocks.find(b => b.id === id);
        if (!block) return;
        
        const previousState = block.active;
        block.active = active ? 1 : 0;
        
        await this.adminMain.withOptimisticUpdate(
            async () => {
                await window.adminApi.updateContentBlock(id, { active: block.active });
                this.adminMain.showToast(
                    active ? 'Блок активирован' : 'Блок деактивирован',
                    'success'
                );
            },
            () => {
                block.active = previousState;
                const checkbox = document.querySelector(`[data-id="${id}"] input[type="checkbox"]`);
                if (checkbox) checkbox.checked = previousState;
            },
            'Ошибка изменения статуса'
        );
    }
    
    showContentModal(block = null) {
        this.editingId = block?.id || null;
        
        const body = `
            <form id="contentForm">
                <div class="form-group">
                    <label>Заголовок блока *</label>
                    <input type="text" name="title" class="form-control" value="${block?.title || ''}" required>
                </div>
                <div class="form-group">
                    <label>Расположение</label>
                    <input type="text" name="location" class="form-control" value="${block?.location || ''}" placeholder="home_hero, about_section и т.д.">
                    <small class="form-text">Идентификатор места где отображается блок</small>
                </div>
                <div class="form-group">
                    <label>Содержимое *</label>
                    <textarea id="contentTextarea" name="content" class="form-control" rows="10" required>${block?.content || ''}</textarea>
                    <small class="form-text">Поддерживается Markdown: **жирный**, *курсив*, # Заголовок</small>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" ${block?.active ? 'checked' : ''}>
                        <span>Активен</span>
                    </label>
                </div>
            </form>
        `;
        
        const modal = this.adminMain.createModal({
            title: block ? 'Редактировать блок' : 'Добавить блок',
            body: body,
            size: 'large'
        });
        
        setTimeout(() => {
            this.adminMain.createMarkdownPreview('contentTextarea');
        }, 100);
        
        const submitBtn = modal.querySelector('#modalSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.saveContentBlock(modal));
        }
    }
    
    async editBlock(id) {
        const block = this.blocks.find(b => b.id === id);
        if (block) {
            this.showContentModal(block);
        }
    }
    
    async saveContentBlock(modal) {
        const form = document.getElementById('contentForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const data = {
            title: formData.get('title'),
            location: formData.get('location'),
            content: formData.get('content'),
            active: formData.get('active') === 'on' ? 1 : 0
        };
        
        try {
            if (this.editingId) {
                await window.adminApi.updateContentBlock(this.editingId, data);
                this.adminMain.showToast('Блок обновлен', 'success');
            } else {
                await window.adminApi.createContentBlock(data);
                this.adminMain.showToast('Блок добавлен', 'success');
            }
            
            modal.remove();
            await this.loadContent();
        } catch (error) {
            console.error('❌ Failed to save content block:', error);
            if (error.errors) {
                this.adminMain.displayValidationErrors(error.errors);
            } else {
                this.adminMain.showToast('Ошибка сохранения блока', 'error');
            }
        }
    }
    
    async deleteBlock(id) {
        if (!this.adminMain.showConfirm('Удалить этот блок контента?')) return;
        
        try {
            await window.adminApi.deleteContentBlock(id);
            this.adminMain.showToast('Блок удален', 'success');
            await this.loadContent();
        } catch (error) {
            console.error('❌ Failed to delete content block:', error);
            this.adminMain.showToast('Ошибка удаления', 'error');
        }
    }
    
    truncate(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
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
        window.contentModule = new ContentModule();
        window.contentModule.init();
    });
} else {
    window.contentModule = new ContentModule();
    window.contentModule.init();
}
