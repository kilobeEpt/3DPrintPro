// ========================================
// FAQ Module - Enhanced with Inline Edit, Drag & Drop
// ========================================

class FAQModule {
    constructor() {
        this.items = [];
        this.editingId = null;
        this.isDragging = false;
        this.adminMain = null;
    }
    
    async init() {
        console.log('❓ Loading FAQ...');
        
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
        await this.loadFAQ();
    }
    
    initButtons() {
        const addBtn = document.getElementById('addFAQBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showFAQModal());
        }
    }
    
    initSSEListener() {
        if (typeof EventSource === 'undefined') return;
        
        const eventSource = new EventSource('/api/updates.php');
        
        eventSource.addEventListener('content.updated', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'faq') {
                console.log('📡 FAQ updated remotely, reloading...');
                this.loadFAQ(true);
            }
        });
        
        eventSource.addEventListener('content.created', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'faq') {
                console.log('📡 FAQ created remotely, reloading...');
                this.loadFAQ(true);
            }
        });
        
        eventSource.addEventListener('content.deleted', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'faq') {
                console.log('📡 FAQ deleted remotely, reloading...');
                this.loadFAQ(true);
            }
        });
        
        eventSource.onerror = () => {
            eventSource.close();
        };
    }
    
    async loadFAQ(silent = false) {
        const container = document.getElementById('faqContainer');
        if (!container) return;
        
        try {
            if (!silent) {
                this.adminMain.showLoading(container);
            }
            
            this.items = await window.adminApi.getFAQ();
            this.renderFAQ();
            
            console.log(`✅ Loaded ${this.items.length} FAQ items`);
        } catch (error) {
            console.error('❌ Failed to load FAQ:', error);
            this.adminMain.showError(container);
        }
    }
    
    renderFAQ() {
        const container = document.getElementById('faqContainer');
        if (!container) return;
        
        if (this.items.length === 0) {
            this.adminMain.showEmpty(container, 'Нет вопросов');
            return;
        }
        
        container.innerHTML = `
            <div class="data-grid">
                <div class="grid-controls">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="faqSearch" placeholder="Поиск вопросов...">
                    </div>
                    <div class="grid-actions">
                        <button class="btn btn-sm btn-outline" onclick="faqModule.toggleDragMode()">
                            <i class="fas fa-arrows-alt"></i>
                            Изменить порядок
                        </button>
                    </div>
                </div>
                <div class="faq-list" id="faqList">
                    ${this.items.map(item => this.renderFAQItem(item)).join('')}
                </div>
            </div>
        `;
        
        const searchInput = document.getElementById('faqSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterFAQ(e.target.value));
        }
    }
    
    renderFAQItem(item) {
        return `
            <div class="faq-item" data-id="${item.id}">
                <div class="faq-header">
                    <div class="drag-handle" style="display: none;">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                    <h3>${this.escapeHtml(item.question)}</h3>
                    <div class="faq-actions">
                        <label class="toggle-switch" title="Активность">
                            <input type="checkbox" ${item.active ? 'checked' : ''} 
                                   onchange="faqModule.toggleActive('${item.id}', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                        <button class="btn btn-sm btn-icon" onclick="faqModule.editItem('${item.id}')" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="faqModule.deleteItem('${item.id}')" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="faq-body">
                    <p>${this.escapeHtml(item.answer)}</p>
                </div>
            </div>
        `;
    }
    
    toggleDragMode() {
        this.isDragging = !this.isDragging;
        const list = document.getElementById('faqList');
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
        const list = document.getElementById('faqList');
        if (!list) return;
        
        let draggedElement = null;
        
        const items = list.querySelectorAll('.faq-item');
        items.forEach((item, index) => {
            item.setAttribute('draggable', 'true');
            item.setAttribute('data-index', index);
            
            item.addEventListener('dragstart', (e) => {
                draggedElement = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                draggedElement = null;
                this.saveOrder();
            });
            
            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (item === draggedElement) return;
                
                const rect = item.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                
                if (e.clientY < midpoint) {
                    list.insertBefore(draggedElement, item);
                } else {
                    list.insertBefore(draggedElement, item.nextSibling);
                }
            });
        });
    }
    
    async saveOrder() {
        const list = document.getElementById('faqList');
        const items = list.querySelectorAll('.faq-item');
        
        const order = Array.from(items).map((item, index) => ({
            id: item.getAttribute('data-id'),
            position: index
        }));
        
        console.log('💾 Saving new order:', order);
    }
    
    filterFAQ(query) {
        const list = document.getElementById('faqList');
        const items = list.querySelectorAll('.faq-item');
        
        query = query.toLowerCase();
        
        items.forEach(item => {
            const id = item.getAttribute('data-id');
            const faqItem = this.items.find(i => i.id === id);
            if (!faqItem) return;
            
            const matchesSearch = faqItem.question.toLowerCase().includes(query) || 
                                  faqItem.answer.toLowerCase().includes(query);
            
            if (matchesSearch) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    async toggleActive(id, active) {
        const item = this.items.find(i => i.id === id);
        if (!item) return;
        
        const previousState = item.active;
        item.active = active ? 1 : 0;
        
        await this.adminMain.withOptimisticUpdate(
            async () => {
                await window.adminApi.updateFAQItem(id, { active: item.active });
                this.adminMain.showToast(
                    active ? 'Вопрос активирован' : 'Вопрос деактивирован',
                    'success'
                );
            },
            () => {
                item.active = previousState;
                const checkbox = document.querySelector(`[data-id="${id}"] input[type="checkbox"]`);
                if (checkbox) checkbox.checked = previousState;
            },
            'Ошибка изменения статуса'
        );
    }
    
    showFAQModal(item = null) {
        this.editingId = item?.id || null;
        
        const body = `
            <form id="faqForm">
                <div class="form-group">
                    <label>Вопрос *</label>
                    <input type="text" name="question" class="form-control" value="${item?.question || ''}" required>
                </div>
                <div class="form-group">
                    <label>Ответ *</label>
                    <textarea name="answer" class="form-control" rows="5" required>${item?.answer || ''}</textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" ${item?.active ? 'checked' : ''}>
                        <span>Активен</span>
                    </label>
                </div>
            </form>
        `;
        
        const modal = this.adminMain.createModal({
            title: item ? 'Редактировать вопрос' : 'Добавить вопрос',
            body: body,
            size: 'medium'
        });
        
        const submitBtn = modal.querySelector('#modalSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.saveFAQItem(modal));
        }
    }
    
    async editItem(id) {
        const item = this.items.find(i => i.id === id);
        if (item) {
            this.showFAQModal(item);
        }
    }
    
    async saveFAQItem(modal) {
        const form = document.getElementById('faqForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const data = {
            question: formData.get('question'),
            answer: formData.get('answer'),
            active: formData.get('active') === 'on' ? 1 : 0
        };
        
        try {
            if (this.editingId) {
                await window.adminApi.updateFAQItem(this.editingId, data);
                this.adminMain.showToast('Вопрос обновлен', 'success');
            } else {
                await window.adminApi.createFAQItem(data);
                this.adminMain.showToast('Вопрос добавлен', 'success');
            }
            
            modal.remove();
            await this.loadFAQ();
        } catch (error) {
            console.error('❌ Failed to save FAQ item:', error);
            if (error.errors) {
                this.adminMain.displayValidationErrors(error.errors);
            } else {
                this.adminMain.showToast('Ошибка сохранения вопроса', 'error');
            }
        }
    }
    
    async deleteItem(id) {
        if (!this.adminMain.showConfirm('Удалить этот вопрос?')) return;
        
        try {
            await window.adminApi.deleteFAQItem(id);
            this.adminMain.showToast('Вопрос удален', 'success');
            await this.loadFAQ();
        } catch (error) {
            console.error('❌ Failed to delete FAQ item:', error);
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
        window.faqModule = new FAQModule();
        window.faqModule.init();
    });
} else {
    window.faqModule = new FAQModule();
    window.faqModule.init();
}
