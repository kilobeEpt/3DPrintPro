<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Check if any admin users exist for onboarding
$isOnboarding = false;
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap/eloquent.php';
    
    $userCount = \App\Models\AdminUser::count();
    $isOnboarding = $userCount === 0;
} catch (Exception $e) {
    error_log('Error checking user count: ' . $e->getMessage());
}

// If not onboarding, require authentication
if (!$isOnboarding) {
    Auth::require('/admin/login.php');
    
    // Check if user is super_admin
    try {
        $authService = new \App\Services\AdminAuthService();
        $sessionId = session_id();
        $validation = $authService->validateSession($sessionId);
        
        if (!$validation['valid'] || !$validation['user']->isSuperAdmin()) {
            http_response_code(403);
            die('
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ запрещен</title>
    <link rel="stylesheet" href="/admin/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <div class="admin-dashboard">
        <main class="admin-main" style="width: 100%;">
            <div class="admin-content">
                <div class="card text-center" style="max-width: 600px; margin: 100px auto;">
                    <div class="card-body">
                        <i class="fas fa-lock" style="font-size: 4rem; color: #dc3545; margin-bottom: 1rem;"></i>
                        <h2>Доступ запрещен</h2>
                        <p class="text-muted">Только супер-администраторы могут управлять пользователями.</p>
                        <a href="/admin/index.php" class="btn btn-primary mt-3">
                            <i class="fas fa-home"></i> Вернуться на главную
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
            ');
        }
    } catch (Exception $e) {
        error_log('Error validating super admin: ' . $e->getMessage());
        http_response_code(500);
        die('Internal server error');
    }
}

$pageTitle = 'Управление пользователями';
$pageScripts = ['/admin/js/modules/users.js'];
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($isOnboarding): ?>
<!-- Onboarding Banner -->
<div class="card card-warning">
    <div class="card-body">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-user-plus" style="font-size: 2rem; color: #ff9800;"></i>
            <div>
                <h3 style="margin: 0 0 0.5rem 0;">Добро пожаловать в 3D Print Pro!</h3>
                <p style="margin: 0;">
                    Похоже, это ваш первый запуск. Создайте первого администратора для начала работы.
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>
            <i class="fas fa-users"></i>
            <?php echo $isOnboarding ? 'Создание первого администратора' : 'Администраторы'; ?>
        </h2>
        <?php if (!$isOnboarding): ?>
        <div>
            <button class="btn btn-primary" id="addUserBtn">
                <i class="fas fa-user-plus"></i>
                Добавить пользователя
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!$isOnboarding): ?>
    <div class="card-body">
        <!-- Search and Filters -->
        <div class="filters-row" style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 250px; margin: 0;">
                <input type="text" 
                       id="searchInput" 
                       class="form-control" 
                       placeholder="Поиск по email или имени...">
            </div>
            
            <div class="form-group" style="min-width: 150px; margin: 0;">
                <select id="roleFilter" class="form-control">
                    <option value="">Все роли</option>
                    <option value="super_admin">Супер-администратор</option>
                    <option value="admin">Администратор</option>
                    <option value="editor">Редактор</option>
                </select>
            </div>
            
            <div class="form-group" style="min-width: 150px; margin: 0;">
                <select id="statusFilter" class="form-control">
                    <option value="">Все статусы</option>
                    <option value="active">Активен</option>
                    <option value="inactive">Неактивен</option>
                    <option value="locked">Заблокирован</option>
                </select>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Users Table -->
    <div id="usersTableContainer">
        <div class="text-center p-4">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff;"></i>
            <p class="text-muted mt-2">Загрузка...</p>
        </div>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 id="modalTitle">Добавить пользователя</h3>
            <button class="btn-close" id="closeModalBtn">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                
                <div class="form-group">
                    <label for="userEmail">
                        Email <span class="text-danger">*</span>
                    </label>
                    <input type="email" 
                           id="userEmail" 
                           name="email" 
                           class="form-control" 
                           required
                           placeholder="admin@example.com">
                </div>
                
                <div class="form-group">
                    <label for="userName">
                        Имя <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           id="userName" 
                           name="name" 
                           class="form-control" 
                           required
                           placeholder="Иван Иванов">
                </div>
                
                <div class="form-group">
                    <label for="userPassword">
                        Пароль <span class="text-danger" id="passwordRequired">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               id="userPassword" 
                               name="password" 
                               class="form-control" 
                               placeholder="Минимум 8 символов">
                        <button type="button" class="btn btn-secondary" id="togglePasswordBtn" title="Показать/скрыть пароль">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text text-muted">
                        Пароль должен содержать минимум 8 символов, включая буквы и цифры
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="userRole">
                        Роль <span class="text-danger">*</span>
                    </label>
                    <select id="userRole" name="role" class="form-control" required>
                        <option value="super_admin">Супер-администратор</option>
                        <option value="admin">Администратор</option>
                        <option value="editor">Редактор</option>
                    </select>
                    <small class="form-text text-muted">
                        <strong>Супер-администратор:</strong> полный доступ<br>
                        <strong>Администратор:</strong> стандартный доступ<br>
                        <strong>Редактор:</strong> ограниченный доступ
                    </small>
                </div>
                
                <div class="form-group" id="statusGroup">
                    <label for="userStatus">
                        Статус <span class="text-danger">*</span>
                    </label>
                    <select id="userStatus" name="status" class="form-control" required>
                        <option value="active">Активен</option>
                        <option value="inactive">Неактивен</option>
                        <option value="locked">Заблокирован</option>
                    </select>
                </div>
                
                <div id="formErrors" class="alert alert-danger" style="display: none;"></div>
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelModalBtn">Отмена</button>
            <button type="button" class="btn btn-primary" id="saveUserBtn">
                <i class="fas fa-save"></i>
                <span id="saveBtnText">Сохранить</span>
            </button>
        </div>
    </div>
</div>

<!-- Audit History Modal -->
<div id="auditModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3>История действий</h3>
            <button class="btn-close" id="closeAuditModalBtn">&times;</button>
        </div>
        
        <div class="modal-body">
            <div id="auditHistoryContainer">
                <div class="text-center p-4">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">Загрузка...</p>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="closeAuditBtn">Закрыть</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
