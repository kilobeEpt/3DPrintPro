<?php
if (!defined('ADMIN_INIT')) {
    die('Direct access not permitted');
}
?>
            </div>
        </main>
    </div>
    
    <!-- Quick Settings Dropdown -->
    <div class="dropdown-menu" id="quickSettingsDropdown" style="display: none;">
        <div class="dropdown-header">Быстрые настройки</div>
        <div class="dropdown-body">
            <div class="setting-item">
                <span>Тёмная тема</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="themeToggle">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
        <div class="dropdown-footer">
            <button class="btn btn-sm btn-outline btn-block" onclick="AdminMain.clearCache()">
                <i class="fas fa-broom"></i>
                Очистить кеш
            </button>
        </div>
    </div>
    
    <!-- Notification Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Global Scripts -->
    <script>
        // Pass PHP session data and CSRF token to JavaScript
        window.ADMIN_SESSION = {
            authenticated: true,
            login: <?php echo json_encode(Auth::user()); ?>,
            csrfToken: <?php echo json_encode(CSRF::getToken()); ?>
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="/config.js"></script>
    <script src="/js/utils.js"></script>
    <script src="/js/api-client.js"></script>
    <script src="/admin/js/admin-api-client.js"></script>
    <script src="/admin/js/admin-main.js"></script>
    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
