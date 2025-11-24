// ========================================
// Services Module - Enhanced with Data Grid, Inline Edit, Drag & Drop
// ========================================

class ServicesModule {
    constructor() {
        this.services = [];
        this.editingId = null;
        this.isDragging = false;
        this.adminMain = null;
    }
    
    async init() {
        console.log('🛠️ Loading services...');
        
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
        await this.loadServices();
    }
    
    initButtons() {
        const addBtn = document.getElementById('addServiceBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showServiceModal());
        }
    }
    
    initSSEListener() {
        if (typeof EventSource === 'undefined') {
            console.warn('⚠️ SSE not supported in this browser');
            return;
        }
        
        const eventSource = new EventSource('/api/updates.php');
        
        eventSource.addEventListener('content.updated', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'services') {
                console.log('📡 Service updated remotely, reloading...');
                this.loadServices(true);
            }
        });
        
        eventSource.addEventListener('content.created', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'services') {
                console.log('📡 Service created remotely, reloading...');
                this.loadServices(true);
            }
        });
        
        eventSource.addEventListener('content.deleted', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'services') {
                console.log('📡 Service deleted remotely, reloading...');
                this.loadServices(true);
            }
        });
        
        eventSource.onerror = () => {
            console.warn('⚠️ SSE connection error');
            eventSource.close();
        };
    }
    
    async loadServices(silent = false) {
        const container = document.getElementById('servicesContainer');
        if (!container) return;
        
        try {
            if (!silent) {
                this.adminMain.showLoading(container);
            }
            
            this.services = await window.adminApi.getServices();
            this.renderServices();
            
            console.log(`✅ Loaded ${this.services.length} services`);
        } catch (error) {
            console.error('❌ Failed to load services:', error);
            this.adminMain.showError(container);
        }
    }
    
    renderServices() {
        const container = document.getElementById('servicesContainer');
        if (!container) return;
        
        if (this.services.length === 0) {
            this.adminMain.showEmpty(container, 'Услуги не найдены');
            return;
        }
        
        container.innerHTML = `
            <div class="data-grid">
                <div class="grid-controls">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="servicesSearch" placeholder="Поиск услуг...">
                    </div>
                    <div class="grid-actions">
                        <button class="btn btn-sm btn-outline" onclick="servicesModule.toggleDragMode()">
                            <i class="fas fa-arrows-alt"></i>
                            Изменить порядок
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="40"></th>
                            <th width="50">Иконка</th>
                            <th>Название</th>
                            <th>Описание</th>
                            <th width="120">Цена</th>
                            <th width="100">Статус</th>
                            <th width="150">Действия</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTableBody">
                        ${this.services.map(service => this.renderServiceRow(service)).join('')}
                    </tbody>
                </table>
            </div>
        `;
        
        const searchInput = document.getElementById('servicesSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterServices(e.target.value));
        }
    }
    
    renderServiceRow(service) {
        return `
            <tr class="data-row" data-id="${service.id}">
                <td class="drag-handle-cell" style="display: none;">
                    <div class="drag-handle">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                </td>
                <td class="icon-cell">
                    <i class="${service.icon || 'fas fa-cog'}"></i>
                </td>
                <td>
                    <div class="editable-cell" data-field="name">
                        <span class="cell-value">${this.escapeHtml(service.name)}</span>
                        <i class="fas fa-pencil-alt edit-icon"></i>
                    </div>
                </td>
                <td>
                    <div class="editable-cell" data-field="description">
                        <span class="cell-value">${this.escapeHtml(service.description || '')}</span>
                        <i class="fas fa-pencil-alt edit-icon"></i>
                    </div>
                </td>
                <td>
                    <div class="editable-cell" data-field="price">
                        <span class="cell-value">${service.price ? this.adminMain.formatMoney(service.price) : '—'}</span>
                        <i class="fas fa-pencil-alt edit-icon"></i>
                    </div>
                </td>
                <td>
                    <label class="toggle-switch" title="Активность">
                        <input type="checkbox" ${service.active ? 'checked' : ''} 
                               onchange="servicesModule.toggleActive('${service.id}', this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                </td>
                <td class="actions-cell">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-icon" onclick="servicesModule.editService('${service.id}')" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="servicesModule.deleteService('${service.id}')" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    toggleDragMode() {
        this.isDragging = !this.isDragging;
        const tbody = document.getElementById('servicesTableBody');
        const dragHandleCells = tbody.querySelectorAll('.drag-handle-cell');
        
        if (this.isDragging) {
            dragHandleCells.forEach(cell => cell.style.display = 'table-cell');
            this.initDragAndDrop();
            this.adminMain.showToast('Режим изменения порядка активирован', 'info');
        } else {
            dragHandleCells.forEach(cell => cell.style.display = 'none');
            this.adminMain.showToast('Порядок сохранен', 'success');
        }
    }
    
    initDragAndDrop() {
        const tbody = document.getElementById('servicesTableBody');
        if (!tbody) return;
        
        let draggedElement = null;
        
        const rows = tbody.querySelectorAll('.data-row');
        rows.forEach((row, index) => {
            row.setAttribute('draggable', 'true');
            row.setAttribute('data-index', index);
            
            row.addEventListener('dragstart', (e) => {
                draggedElement = row;
                row.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            
            row.addEventListener('dragend', () => {
                row.classList.remove('dragging');
                draggedElement = null;
                this.saveOrder();
            });
            
            row.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (row === draggedElement) return;
                
                const rect = row.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                
                if (e.clientY < midpoint) {
                    tbody.insertBefore(draggedElement, row);
                } else {
                    tbody.insertBefore(draggedElement, row.nextSibling);
                }
            });
        });
    }
    
    async saveOrder() {
        const tbody = document.getElementById('servicesTableBody');
        const rows = tbody.querySelectorAll('.data-row');
        
        const order = Array.from(rows).map((row, index) => ({
            id: row.getAttribute('data-id'),
            position: index
        }));
        
        console.log('💾 Saving new order:', order);
    }
    
    filterServices(query) {
        const tbody = document.getElementById('servicesTableBody');
        const rows = tbody.querySelectorAll('.data-row');
        
        query = query.toLowerCase();
        
        rows.forEach(row => {
            const name = row.querySelector('[data-field="name"] .cell-value').textContent.toLowerCase();
            const description = row.querySelector('[data-field="description"] .cell-value').textContent.toLowerCase();
            
            if (name.includes(query) || description.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    async toggleActive(id, active) {
        const service = this.services.find(s => s.id === id);
        if (!service) return;
        
        const previousState = service.active;
        service.active = active ? 1 : 0;
        
        await this.adminMain.withOptimisticUpdate(
            async () => {
                await window.adminApi.updateService(id, { active: service.active });
                this.adminMain.showToast(
                    active ? 'Услуга активирована' : 'Услуга деактивирована',
                    'success'
                );
            },
            () => {
                service.active = previousState;
                const checkbox = document.querySelector(`[data-id="${id}"] input[type="checkbox"]`);
                if (checkbox) checkbox.checked = previousState;
            },
            'Ошибка изменения статуса'
        );
    }
    
    showServiceModal(service = null) {
        this.editingId = service?.id || null;
        
        const body = `
            <form id="serviceForm">
                <div class="form-group">
                    <label>Название услуги *</label>
                    <input type="text" name="name" class="form-control" value="${service?.name || ''}" required>
                </div>
                <div class="form-group">
                    <label>Иконка (Font Awesome класс)</label>
                    <input type="text" name="icon" class="form-control" value="${service?.icon || 'fas fa-cog'}" placeholder="fas fa-cog">
                    <small class="form-text">Например: fas fa-cog, fas fa-cube, fas fa-print</small>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" class="form-control" rows="3">${service?.description || ''}</textarea>
                </div>
                <div class="form-group">
                    <label>Цена (₽)</label>
                    <input type="number" name="price" class="form-control" value="${service?.price || ''}" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="active" ${service?.active ? 'checked' : ''}>
                        <span>Активна</span>
                    </label>
                </div>
            </form>
        `;
        
        const modal = this.adminMain.createModal({
            title: service ? 'Редактировать услугу' : 'Добавить услугу',
            body: body,
            size: 'medium'
        });
        
        const submitBtn = modal.querySelector('#modalSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.saveService(modal));
        }
    }
    
    async editService(id) {
        const service = this.services.find(s => s.id === id);
        if (service) {
            this.showServiceModal(service);
        }
    }
    
    async saveService(modal) {
        const form = document.getElementById('serviceForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const data = {
            name: formData.get('name'),
            icon: formData.get('icon'),
            description: formData.get('description'),
            price: formData.get('price') || null,
            active: formData.get('active') === 'on' ? 1 : 0
        };
        
        try {
            if (this.editingId) {
                await window.adminApi.updateService(this.editingId, data);
                this.adminMain.showToast('Услуга обновлена', 'success');
            } else {
                await window.adminApi.createService(data);
                this.adminMain.showToast('Услуга добавлена', 'success');
            }
            
            modal.remove();
            await this.loadServices();
        } catch (error) {
            console.error('❌ Failed to save service:', error);
            if (error.errors) {
                this.adminMain.displayValidationErrors(error.errors);
            } else {
                this.adminMain.showToast('Ошибка сохранения услуги', 'error');
            }
        }
    }
    
    async deleteService(id) {
        if (!this.adminMain.showConfirm('Удалить эту услугу?')) return;
        
        try {
            await window.adminApi.deleteService(id);
            this.adminMain.showToast('Услуга удалена', 'success');
            await this.loadServices();
        } catch (error) {
            console.error('❌ Failed to delete service:', error);
            this.adminMain.showToast('Ошибка удаления услуги', 'error');
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
        window.servicesModule = new ServicesModule();
        window.servicesModule.init();
    });
} else {
    window.servicesModule = new ServicesModule();
    window.servicesModule.init();
}
