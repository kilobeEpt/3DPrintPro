// ========================================
// Services Module - Services Management & CRUD
// ========================================

class ServicesModule {
    constructor() {
        this.services = [];
        this.editingId = null;
    }
    
    async init() {
        console.log('🛠️ Loading services...');
        this.initButtons();
        await this.loadServices();
    }
    
    initButtons() {
        const addBtn = document.getElementById('addServiceBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showServiceModal());
        }
    }
    
    async loadServices() {
        const container = document.getElementById('servicesContainer');
        if (!container) return;
        
        try {
            AdminMain.prototype.showLoading(container);
            
            this.services = await adminApi.getServices();
            this.renderServices();
            
            console.log(`✅ Loaded ${this.services.length} services`);
        } catch (error) {
            console.error('❌ Failed to load services:', error);
            AdminMain.prototype.showError(container);
        }
    }
    
    renderServices() {
        const container = document.getElementById('servicesContainer');
        if (!container) return;
        
        if (this.services.length === 0) {
            AdminMain.prototype.showEmpty(container, 'Услуги не найдены');
            return;
        }
        
        container.innerHTML = this.services.map(service => `
            <div class="service-card" data-id="${service.id}">
                <div class="service-header">
                    <div class="service-icon">
                        <i class="${service.icon || 'fas fa-cog'}"></i>
                    </div>
                    <h3>${this.escapeHtml(service.name)}</h3>
                </div>
                <div class="service-body">
                    <p>${this.escapeHtml(service.description || '')}</p>
                    ${service.price ? `<div class="service-price">${AdminMain.prototype.formatMoney(service.price)}</div>` : ''}
                </div>
                <div class="service-footer">
                    <div class="service-meta">
                        <span class="badge ${service.active ? 'badge-success' : 'badge-secondary'}">
                            ${service.active ? 'Активна' : 'Неактивна'}
                        </span>
                    </div>
                    <div class="service-actions">
                        <button class="btn btn-sm btn-icon" onclick="servicesModule.editService('${service.id}')" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="servicesModule.deleteService('${service.id}')" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    showServiceModal(service = null) {
        this.editingId = service?.id || null;
        
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="this.parentElement.remove()"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${service ? 'Редактировать услугу' : 'Добавить услугу'}</h2>
                    <button class="btn-close" onclick="this.closest('.modal').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="serviceForm">
                        <div class="form-group">
                            <label>Название услуги</label>
                            <input type="text" name="name" class="form-control" value="${service?.name || ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Иконка (Font Awesome класс)</label>
                            <input type="text" name="icon" class="form-control" value="${service?.icon || 'fas fa-cog'}" placeholder="fas fa-cog">
                        </div>
                        <div class="form-group">
                            <label>Описание</label>
                            <textarea name="description" class="form-control" rows="3">${service?.description || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Цена (₽)</label>
                            <input type="number" name="price" class="form-control" value="${service?.price || ''}" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="active" ${service?.active ? 'checked' : ''}>
                                <span>Активна</span>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" onclick="this.closest('.modal').remove()">Отмена</button>
                    <button class="btn btn-primary" onclick="servicesModule.saveService()">Сохранить</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.classList.add('show');
    }
    
    async editService(id) {
        const service = this.services.find(s => s.id === id);
        if (service) {
            this.showServiceModal(service);
        }
    }
    
    async saveService() {
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
                await adminApi.updateService(this.editingId, data);
                AdminMain.prototype.showToast('Услуга обновлена', 'success');
            } else {
                await adminApi.createService(data);
                AdminMain.prototype.showToast('Услуга добавлена', 'success');
            }
            
            document.querySelector('.modal').remove();
            await this.loadServices();
        } catch (error) {
            console.error('❌ Failed to save service:', error);
            AdminMain.prototype.showToast('Ошибка сохранения услуги', 'error');
        }
    }
    
    async deleteService(id) {
        if (!AdminMain.prototype.showConfirm('Удалить эту услугу?')) return;
        
        try {
            await adminApi.deleteService(id);
            AdminMain.prototype.showToast('Услуга удалена', 'success');
            await this.loadServices();
        } catch (error) {
            console.error('❌ Failed to delete service:', error);
            AdminMain.prototype.showToast('Ошибка удаления услуги', 'error');
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
