// Admin Users Management Module
class UsersModule {
    constructor() {
        this.users = [];
        this.currentUser = null;
        this.isOnboarding = false;
        this.filters = {
            search: '',
            role: '',
            status: ''
        };
    }
    
    async init() {
        console.log('👥 Loading users module...');
        
        // Check if onboarding mode
        this.isOnboarding = document.querySelector('.card-warning') !== null;
        
        if (this.isOnboarding) {
            this.initOnboarding();
        } else {
            this.initNormalMode();
        }
        
        await this.loadUsers();
    }
    
    initOnboarding() {
        console.log('🚀 Onboarding mode active');
        
        // Show modal immediately for first user creation
        setTimeout(() => {
            this.showModal();
        }, 500);
    }
    
    initNormalMode() {
        // Add User button
        const addBtn = document.getElementById('addUserBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.showModal());
        }
        
        // Search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filters.search = e.target.value;
                this.loadUsers();
            });
        }
        
        // Role filter
        const roleFilter = document.getElementById('roleFilter');
        if (roleFilter) {
            roleFilter.addEventListener('change', (e) => {
                this.filters.role = e.target.value;
                this.loadUsers();
            });
        }
        
        // Status filter
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.filters.status = e.target.value;
                this.loadUsers();
            });
        }
    }
    
    async loadUsers() {
        try {
            if (!window.adminApi) {
                console.warn('⚠️ adminApi not ready yet');
                setTimeout(() => this.loadUsers(), 100);
                return;
            }
            
            if (this.isOnboarding) {
                this.renderOnboardingForm();
                return;
            }
            
            const params = new URLSearchParams();
            if (this.filters.search) params.append('search', this.filters.search);
            if (this.filters.role) params.append('role', this.filters.role);
            if (this.filters.status) params.append('status', this.filters.status);
            
            const url = `/api/admin/users.php${params.toString() ? '?' + params.toString() : ''}`;
            const response = await window.adminApi.get(url);
            
            this.users = response.users || [];
            this.renderTable();
            
            console.log(`✅ Loaded ${this.users.length} users`);
        } catch (error) {
            console.error('❌ Failed to load users:', error);
            this.showError('Ошибка загрузки пользователей: ' + (error.message || 'Неизвестная ошибка'));
        }
    }
    
    renderOnboardingForm() {
        const container = document.getElementById('usersTableContainer');
        if (!container) return;
        
        container.innerHTML = `
            <div class="p-4 text-center">
                <i class="fas fa-user-shield" style="font-size: 4rem; color: #007bff; margin-bottom: 1rem;"></i>
                <h3>Создайте первого администратора</h3>
                <p class="text-muted">Для начала работы необходимо создать учетную запись супер-администратора</p>
            </div>
        `;
    }
    
    renderTable() {
        const container = document.getElementById('usersTableContainer');
        if (!container) return;
        
        if (this.users.length === 0) {
            container.innerHTML = `
                <div class="p-4 text-center">
                    <i class="fas fa-users-slash" style="font-size: 3rem; color: #999; margin-bottom: 1rem;"></i>
                    <p class="text-muted">Пользователи не найдены</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>Имя / Email</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Последний вход</th>
                        <th>Сессии</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    ${this.users.map(user => this.renderUserRow(user)).join('')}
                </tbody>
            </table>
        `;
        
        // Attach event listeners
        this.attachTableListeners();
    }
    
    renderUserRow(user) {
        const roleBadges = {
            'super_admin': '<span class="badge badge-danger">Супер-админ</span>',
            'admin': '<span class="badge badge-primary">Админ</span>',
            'editor': '<span class="badge badge-info">Редактор</span>'
        };
        
        const statusBadges = {
            'active': '<span class="badge badge-success">Активен</span>',
            'inactive': '<span class="badge badge-secondary">Неактивен</span>',
            'locked': '<span class="badge badge-danger">Заблокирован</span>'
        };
        
        const lastLogin = user.last_login_at 
            ? new Date(user.last_login_at).toLocaleString('ru-RU')
            : '<span class="text-muted">Никогда</span>';
        
        const sessions = user.active_sessions_count || 0;
        const sessionsText = sessions > 0 
            ? `<span class="badge badge-success">${sessions}</span>`
            : '<span class="text-muted">0</span>';
        
        return `
            <tr data-user-id="${user.id}">
                <td>
                    <div>
                        <strong>${this.escapeHtml(user.name)}</strong><br>
                        <small class="text-muted">${this.escapeHtml(user.email)}</small>
                    </div>
                </td>
                <td>${roleBadges[user.role] || user.role}</td>
                <td>${statusBadges[user.status] || user.status}</td>
                <td><small>${lastLogin}</small></td>
                <td>${sessionsText}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-secondary edit-user" 
                                data-user-id="${user.id}"
                                title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-info view-audit" 
                                data-user-id="${user.id}"
                                title="История действий">
                            <i class="fas fa-history"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-user" 
                                data-user-id="${user.id}"
                                title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    attachTableListeners() {
        // Edit buttons
        document.querySelectorAll('.edit-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = parseInt(e.currentTarget.dataset.userId);
                this.editUser(userId);
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = parseInt(e.currentTarget.dataset.userId);
                this.deleteUser(userId);
            });
        });
        
        // Audit buttons
        document.querySelectorAll('.view-audit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = parseInt(e.currentTarget.dataset.userId);
                this.showAuditHistory(userId);
            });
        });
    }
    
    showModal(user = null) {
        this.currentUser = user;
        
        const modal = document.getElementById('userModal');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('userForm');
        const passwordRequired = document.getElementById('passwordRequired');
        const statusGroup = document.getElementById('statusGroup');
        
        if (!modal || !form) return;
        
        // Reset form
        form.reset();
        document.getElementById('formErrors').style.display = 'none';
        
        if (user) {
            // Edit mode
            title.textContent = 'Редактировать пользователя';
            document.getElementById('userId').value = user.id;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userName').value = user.name;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            document.getElementById('userPassword').required = false;
            passwordRequired.style.display = 'none';
            statusGroup.style.display = 'block';
        } else {
            // Create mode
            title.textContent = this.isOnboarding ? 'Создание администратора' : 'Добавить пользователя';
            document.getElementById('userId').value = '';
            document.getElementById('userPassword').required = true;
            passwordRequired.style.display = 'inline';
            
            if (this.isOnboarding) {
                document.getElementById('userRole').value = 'super_admin';
                document.getElementById('userStatus').value = 'active';
                statusGroup.style.display = 'none';
            } else {
                statusGroup.style.display = 'block';
            }
        }
        
        modal.style.display = 'flex';
        
        // Setup modal events
        this.setupModalEvents();
    }
    
    setupModalEvents() {
        const modal = document.getElementById('userModal');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelModalBtn');
        const saveBtn = document.getElementById('saveUserBtn');
        const togglePasswordBtn = document.getElementById('togglePasswordBtn');
        
        // Close handlers
        const closeHandler = () => {
            modal.style.display = 'none';
            this.currentUser = null;
        };
        
        closeBtn.onclick = closeHandler;
        cancelBtn.onclick = closeHandler;
        
        // Click outside to close
        modal.onclick = (e) => {
            if (e.target === modal) {
                closeHandler();
            }
        };
        
        // Save handler
        saveBtn.onclick = () => this.saveUser();
        
        // Toggle password visibility
        togglePasswordBtn.onclick = () => {
            const passwordInput = document.getElementById('userPassword');
            const icon = togglePasswordBtn.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        };
    }
    
    async saveUser() {
        const form = document.getElementById('userForm');
        const formData = new FormData(form);
        const data = {};
        
        formData.forEach((value, key) => {
            if (value) data[key] = value;
        });
        
        // Remove empty password
        if (!data.password) {
            delete data.password;
        }
        
        // Client-side validation
        const errors = [];
        
        if (!data.email) {
            errors.push('Email обязателен');
        } else if (!this.isValidEmail(data.email)) {
            errors.push('Некорректный email');
        }
        
        if (!data.name) {
            errors.push('Имя обязательно');
        }
        
        if (!data.id && !data.password) {
            errors.push('Пароль обязателен при создании пользователя');
        }
        
        if (data.password) {
            const passwordError = this.validatePassword(data.password);
            if (passwordError) {
                errors.push(passwordError);
            }
        }
        
        if (!data.role) {
            errors.push('Роль обязательна');
        }
        
        if (errors.length > 0) {
            this.showFormErrors(errors);
            return;
        }
        
        // Disable save button
        const saveBtn = document.getElementById('saveUserBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
        
        try {
            if (data.id) {
                // Update
                await window.adminApi.put('/api/admin/users.php', data);
                this.showSuccess('Пользователь успешно обновлен');
            } else {
                // Create
                await window.adminApi.post('/api/admin/users.php', data);
                this.showSuccess('Пользователь успешно создан');
                
                // If onboarding, redirect to login
                if (this.isOnboarding) {
                    setTimeout(() => {
                        window.location.href = '/admin/login.php';
                    }, 2000);
                }
            }
            
            document.getElementById('userModal').style.display = 'none';
            await this.loadUsers();
        } catch (error) {
            console.error('❌ Failed to save user:', error);
            const errorMsg = error.response?.errors 
                ? Object.values(error.response.errors).flat().join(', ')
                : (error.message || 'Неизвестная ошибка');
            this.showFormErrors([errorMsg]);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }
    
    async editUser(userId) {
        try {
            const response = await window.adminApi.get(`/api/admin/users.php?id=${userId}`);
            this.showModal(response.user);
        } catch (error) {
            console.error('❌ Failed to load user:', error);
            this.showError('Ошибка загрузки пользователя');
        }
    }
    
    async deleteUser(userId) {
        const user = this.users.find(u => u.id === userId);
        if (!user) return;
        
        if (!confirm(`Вы уверены, что хотите удалить пользователя "${user.name}" (${user.email})?\n\nВсе активные сессии будут завершены.`)) {
            return;
        }
        
        try {
            await window.adminApi.delete(`/api/admin/users.php?id=${userId}`);
            this.showSuccess('Пользователь успешно удален');
            await this.loadUsers();
        } catch (error) {
            console.error('❌ Failed to delete user:', error);
            const errorMsg = error.message || 'Неизвестная ошибка';
            this.showError('Ошибка удаления: ' + errorMsg);
        }
    }
    
    async showAuditHistory(userId) {
        const user = this.users.find(u => u.id === userId);
        if (!user) return;
        
        const modal = document.getElementById('auditModal');
        const container = document.getElementById('auditHistoryContainer');
        
        if (!modal || !container) return;
        
        modal.style.display = 'flex';
        container.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2">Загрузка истории...</p>
            </div>
        `;
        
        // Setup close handlers
        const closeHandler = () => {
            modal.style.display = 'none';
        };
        
        document.getElementById('closeAuditModalBtn').onclick = closeHandler;
        document.getElementById('closeAuditBtn').onclick = closeHandler;
        modal.onclick = (e) => {
            if (e.target === modal) closeHandler();
        };
        
        try {
            const response = await window.adminApi.get(`/api/admin/users.php?action=audit_history&user_id=${userId}`);
            const logs = response.audit_logs || [];
            
            if (logs.length === 0) {
                container.innerHTML = `
                    <div class="text-center p-4">
                        <i class="fas fa-history" style="font-size: 3rem; color: #999;"></i>
                        <p class="text-muted mt-2">История действий пуста</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = `
                <div style="margin-bottom: 1rem;">
                    <strong>${this.escapeHtml(user.name)}</strong>
                    <small class="text-muted">(${this.escapeHtml(user.email)})</small>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    ${logs.map(log => this.renderAuditLogItem(log)).join('')}
                </div>
            `;
        } catch (error) {
            console.error('❌ Failed to load audit history:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    Ошибка загрузки истории: ${this.escapeHtml(error.message || 'Неизвестная ошибка')}
                </div>
            `;
        }
    }
    
    renderAuditLogItem(log) {
        const actionIcons = {
            'login': '<i class="fas fa-sign-in-alt text-success"></i>',
            'logout': '<i class="fas fa-sign-out-alt text-secondary"></i>',
            'create': '<i class="fas fa-plus-circle text-primary"></i>',
            'update': '<i class="fas fa-edit text-warning"></i>',
            'delete': '<i class="fas fa-trash text-danger"></i>'
        };
        
        const actionLabels = {
            'login': 'Вход',
            'logout': 'Выход',
            'create': 'Создание',
            'update': 'Обновление',
            'delete': 'Удаление'
        };
        
        const icon = actionIcons[log.action] || '<i class="fas fa-circle"></i>';
        const label = actionLabels[log.action] || log.action;
        const timestamp = new Date(log.created_at).toLocaleString('ru-RU');
        
        let details = '';
        if (log.payload) {
            try {
                const payload = typeof log.payload === 'string' ? JSON.parse(log.payload) : log.payload;
                details = `<pre style="font-size: 0.85rem; background: #f8f9fa; padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem;">${JSON.stringify(payload, null, 2)}</pre>`;
            } catch (e) {
                // Ignore JSON parse errors
            }
        }
        
        return `
            <div style="padding: 1rem; border-bottom: 1px solid #e9ecef;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        ${icon} <strong>${label}</strong>
                        ${log.entity_type ? `<span class="text-muted"> - ${log.entity_type}</span>` : ''}
                    </div>
                    <small class="text-muted">${timestamp}</small>
                </div>
                ${log.ip_address ? `<small class="text-muted">IP: ${log.ip_address}</small>` : ''}
                ${details}
            </div>
        `;
    }
    
    validatePassword(password) {
        if (password.length < 8) {
            return 'Пароль должен содержать минимум 8 символов';
        }
        if (!/[a-zA-Z]/.test(password)) {
            return 'Пароль должен содержать хотя бы одну букву';
        }
        if (!/[0-9]/.test(password)) {
            return 'Пароль должен содержать хотя бы одну цифру';
        }
        return null;
    }
    
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    showFormErrors(errors) {
        const errorsDiv = document.getElementById('formErrors');
        if (!errorsDiv) return;
        
        errorsDiv.innerHTML = `
            <strong>Ошибка валидации:</strong>
            <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                ${errors.map(err => `<li>${this.escapeHtml(err)}</li>`).join('')}
            </ul>
        `;
        errorsDiv.style.display = 'block';
    }
    
    showSuccess(message) {
        // Use global notification if available
        if (window.showNotification) {
            window.showNotification(message, 'success');
        } else {
            alert(message);
        }
    }
    
    showError(message) {
        // Use global notification if available
        if (window.showNotification) {
            window.showNotification(message, 'error');
        } else {
            alert(message);
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const module = new UsersModule();
        module.init();
    });
} else {
    const module = new UsersModule();
    module.init();
}
