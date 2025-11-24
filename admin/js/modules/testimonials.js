// ========================================
// Testimonials Module - Enhanced with Moderation Workflow, Avatar Upload
// ========================================

class TestimonialsModule {
    constructor() {
        this.items = [];
        this.editingId = null;
        this.adminMain = null;
        this.uploadedFile = null;
        this.currentFilter = 'all';
    }
    
    async init() {
        console.log('💬 Loading testimonials...');
        
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
        await this.loadTestimonials();
    }
    
    initButtons() {
        const addBtn = document.getElementById('addTestimonialBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showTestimonialModal());
        }
    }
    
    initSSEListener() {
        if (typeof EventSource === 'undefined') return;
        
        const eventSource = new EventSource('/api/updates.php');
        
        eventSource.addEventListener('content.updated', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'testimonials') {
                console.log('📡 Testimonial updated remotely, reloading...');
                this.loadTestimonials(true);
            }
        });
        
        eventSource.addEventListener('content.created', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'testimonials') {
                console.log('📡 Testimonial created remotely, reloading...');
                this.loadTestimonials(true);
            }
        });
        
        eventSource.addEventListener('content.deleted', (e) => {
            const data = JSON.parse(e.data);
            if (data.entity_type === 'testimonials') {
                console.log('📡 Testimonial deleted remotely, reloading...');
                this.loadTestimonials(true);
            }
        });
        
        eventSource.onerror = () => {
            eventSource.close();
        };
    }
    
    async loadTestimonials(silent = false) {
        const container = document.getElementById('testimonialsContainer');
        if (!container) return;
        
        try {
            if (!silent) {
                this.adminMain.showLoading(container);
            }
            
            this.items = await window.adminApi.getTestimonials();
            this.renderTestimonials();
            
            console.log(`✅ Loaded ${this.items.length} testimonials`);
        } catch (error) {
            console.error('❌ Failed to load testimonials:', error);
            this.adminMain.showError(container);
        }
    }
    
    renderTestimonials() {
        const container = document.getElementById('testimonialsContainer');
        if (!container) return;
        
        if (this.items.length === 0) {
            this.adminMain.showEmpty(container, 'Нет отзывов');
            return;
        }
        
        const pending = this.items.filter(t => t.status === 'pending').length;
        const approved = this.items.filter(t => t.status === 'approved').length;
        
        container.innerHTML = `
            <div class="data-grid">
                <div class="grid-controls">
                    <div class="filter-tabs">
                        <button class="filter-tab ${this.currentFilter === 'all' ? 'active' : ''}" onclick="testimonialsModule.setFilter('all')">
                            Все (${this.items.length})
                        </button>
                        <button class="filter-tab ${this.currentFilter === 'pending' ? 'active' : ''}" onclick="testimonialsModule.setFilter('pending')">
                            На модерации (${pending})
                        </button>
                        <button class="filter-tab ${this.currentFilter === 'approved' ? 'active' : ''}" onclick="testimonialsModule.setFilter('approved')">
                            Одобренные (${approved})
                        </button>
                    </div>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="testimonialsSearch" placeholder="Поиск отзывов...">
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="60">Фото</th>
                            <th width="150">Имя</th>
                            <th>Отзыв</th>
                            <th width="100">Рейтинг</th>
                            <th width="120">Статус</th>
                            <th width="200">Действия</th>
                        </tr>
                    </thead>
                    <tbody id="testimonialsTableBody">
                        ${this.getFilteredItems().map(item => this.renderTestimonialRow(item)).join('')}
                    </tbody>
                </table>
            </div>
        `;
        
        const searchInput = document.getElementById('testimonialsSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterTestimonials(e.target.value));
        }
    }
    
    getFilteredItems() {
        if (this.currentFilter === 'all') {
            return this.items;
        }
        return this.items.filter(item => item.status === this.currentFilter);
    }
    
    setFilter(filter) {
        this.currentFilter = filter;
        this.renderTestimonials();
    }
    
    renderTestimonialRow(item) {
        const statusClasses = {
            pending: 'badge-warning',
            approved: 'badge-success',
            rejected: 'badge-danger'
        };
        
        const statusLabels = {
            pending: 'На модерации',
            approved: 'Одобрено',
            rejected: 'Отклонено'
        };
        
        return `
            <tr class="data-row" data-id="${item.id}" data-status="${item.status}">
                <td>
                    <div class="avatar-cell">
                        ${item.avatar_path ? 
                            `<img src="${item.avatar_path}" alt="${this.escapeHtml(item.name)}">` :
                            '<div class="avatar-placeholder"><i class="fas fa-user"></i></div>'
                        }
                    </div>
                </td>
                <td>
                    <div class="name-cell">
                        <strong>${this.escapeHtml(item.name)}</strong>
                        ${item.company ? `<small>${this.escapeHtml(item.company)}</small>` : ''}
                    </div>
                </td>
                <td>
                    <div class="text-cell">
                        ${this.escapeHtml(item.text?.substring(0, 100) || '')}${item.text?.length > 100 ? '...' : ''}
                    </div>
                </td>
                <td>
                    <div class="rating-cell">
                        ${this.renderStars(item.rating || 5)}
                    </div>
                </td>
                <td>
                    <span class="badge ${statusClasses[item.status] || 'badge-secondary'}">
                        ${statusLabels[item.status] || item.status}
                    </span>
                </td>
                <td class="actions-cell">
                    <div class="btn-group">
                        ${item.status === 'pending' ? `
                            <button class="btn btn-sm btn-success" onclick="testimonialsModule.approve('${item.id}')" title="Одобрить">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="testimonialsModule.reject('${item.id}')" title="Отклонить">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-sm btn-icon" onclick="testimonialsModule.editItem('${item.id}')" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="testimonialsModule.deleteItem('${item.id}')" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    renderStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="fas fa-star ${i <= rating ? 'star-filled' : 'star-empty'}"></i>`;
        }
        return stars;
    }
    
    filterTestimonials(query) {
        const tbody = document.getElementById('testimonialsTableBody');
        const rows = tbody.querySelectorAll('.data-row');
        
        query = query.toLowerCase();
        
        rows.forEach(row => {
            const id = row.getAttribute('data-id');
            const item = this.items.find(i => i.id === id);
            if (!item) return;
            
            const matchesSearch = item.name.toLowerCase().includes(query) || 
                                  (item.text && item.text.toLowerCase().includes(query)) ||
                                  (item.company && item.company.toLowerCase().includes(query));
            
            if (matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    async approve(id) {
        const item = this.items.find(i => i.id === id);
        if (!item) return;
        
        const previousStatus = item.status;
        item.status = 'approved';
        
        await this.adminMain.withOptimisticUpdate(
            async () => {
                await window.adminApi.updateTestimonial(id, { status: 'approved' });
                this.adminMain.showToast('Отзыв одобрен', 'success');
                this.renderTestimonials();
            },
            () => {
                item.status = previousStatus;
                this.renderTestimonials();
            },
            'Ошибка одобрения отзыва'
        );
    }
    
    async reject(id) {
        const item = this.items.find(i => i.id === id);
        if (!item) return;
        
        const previousStatus = item.status;
        item.status = 'rejected';
        
        await this.adminMain.withOptimisticUpdate(
            async () => {
                await window.adminApi.updateTestimonial(id, { status: 'rejected' });
                this.adminMain.showToast('Отзыв отклонен', 'warning');
                this.renderTestimonials();
            },
            () => {
                item.status = previousStatus;
                this.renderTestimonials();
            },
            'Ошибка отклонения отзыва'
        );
    }
    
    showTestimonialModal(item = null) {
        this.editingId = item?.id || null;
        this.uploadedFile = null;
        
        const body = `
            <form id="testimonialForm">
                <div class="form-group">
                    <label>Фото клиента</label>
                    <div id="avatarUploadContainer"></div>
                </div>
                <div class="form-group">
                    <label>Имя клиента *</label>
                    <input type="text" name="name" class="form-control" value="${item?.name || ''}" required>
                </div>
                <div class="form-group">
                    <label>Компания</label>
                    <input type="text" name="company" class="form-control" value="${item?.company || ''}">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="${item?.email || ''}">
                </div>
                <div class="form-group">
                    <label>Отзыв *</label>
                    <textarea name="text" class="form-control" rows="4" required>${item?.text || ''}</textarea>
                </div>
                <div class="form-group">
                    <label>Рейтинг</label>
                    <div class="rating-input">
                        ${[1, 2, 3, 4, 5].map(star => `
                            <label>
                                <input type="radio" name="rating" value="${star}" ${(item?.rating || 5) === star ? 'checked' : ''}>
                                <i class="fas fa-star"></i>
                            </label>
                        `).join('')}
                    </div>
                </div>
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status" class="form-control">
                        <option value="pending" ${item?.status === 'pending' ? 'selected' : ''}>На модерации</option>
                        <option value="approved" ${item?.status === 'approved' ? 'selected' : ''}>Одобрено</option>
                        <option value="rejected" ${item?.status === 'rejected' ? 'selected' : ''}>Отклонено</option>
                    </select>
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
            title: item ? 'Редактировать отзыв' : 'Добавить отзыв',
            body: body,
            size: 'medium'
        });
        
        const uploadContainer = modal.querySelector('#avatarUploadContainer');
        if (uploadContainer) {
            uploadContainer.innerHTML = this.adminMain.createFileUpload({
                accept: 'image/*',
                maxSize: 2097152,
                preview: true,
                currentImage: item?.avatar_path || null,
                onUpload: (file) => {
                    this.uploadedFile = file;
                }
            });
        }
        
        const submitBtn = modal.querySelector('#modalSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.saveTestimonial(modal));
        }
    }
    
    async editItem(id) {
        const item = this.items.find(i => i.id === id);
        if (item) {
            this.showTestimonialModal(item);
        }
    }
    
    async saveTestimonial(modal) {
        const form = document.getElementById('testimonialForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        
        if (this.uploadedFile) {
            formData.append('avatar', this.uploadedFile);
        }
        
        formData.set('active', formData.get('active') === 'on' ? '1' : '0');
        
        try {
            if (this.editingId) {
                await window.adminApi.updateTestimonial(this.editingId, formData);
                this.adminMain.showToast('Отзыв обновлен', 'success');
            } else {
                await window.adminApi.createTestimonial(formData);
                this.adminMain.showToast('Отзыв добавлен', 'success');
            }
            
            modal.remove();
            await this.loadTestimonials();
        } catch (error) {
            console.error('❌ Failed to save testimonial:', error);
            if (error.errors) {
                this.adminMain.displayValidationErrors(error.errors);
            } else {
                this.adminMain.showToast('Ошибка сохранения отзыва', 'error');
            }
        }
    }
    
    async deleteItem(id) {
        if (!this.adminMain.showConfirm('Удалить этот отзыв?')) return;
        
        try {
            await window.adminApi.deleteTestimonial(id);
            this.adminMain.showToast('Отзыв удален', 'success');
            await this.loadTestimonials();
        } catch (error) {
            console.error('❌ Failed to delete testimonial:', error);
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
        window.testimonialsModule = new TestimonialsModule();
        window.testimonialsModule.init();
    });
} else {
    window.testimonialsModule = new TestimonialsModule();
    window.testimonialsModule.init();
}
