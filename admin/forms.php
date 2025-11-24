<?php
// ========================================
// Forms Management Page (v1.0 - Form Builder)
// ========================================

define('ADMIN_INIT', true);

require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

Auth::require('/admin/login.php');

$pageTitle = 'Формы';
$pageScripts = ['/admin/js/modules/forms.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="forms-workspace">
    <!-- Workspace Header -->
    <div class="workspace-header">
        <div class="workspace-title">
            <h1>Конструктор форм</h1>
            <p class="workspace-subtitle">Создание и управление формами без редактирования кода</p>
        </div>
        <div class="workspace-actions">
            <button class="btn btn-primary" id="createFormBtn">
                <i class="fas fa-plus"></i>
                Создать форму
            </button>
        </div>
    </div>

    <!-- Forms List -->
    <div class="card forms-list-card">
        <div class="table-header">
            <div class="table-info">
                <span id="formsInfo">Загрузка...</span>
            </div>
            <div class="table-controls">
                <input type="text" class="filter-input" id="searchInput" placeholder="Поиск форм...">
            </div>
        </div>

        <div id="formsTable">
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Загрузка форм...</p>
            </div>
        </div>
    </div>
</div>

<!-- Form Editor Modal -->
<div class="modal modal-xl" id="formEditorModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="editorTitle">Редактор формы</h2>
            <button class="btn-close" onclick="formsModule.closeEditor()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <!-- Editor Tabs -->
            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="settings">
                        <i class="fas fa-cog"></i>
                        Настройки
                    </button>
                    <button class="tab-btn" data-tab="fields">
                        <i class="fas fa-list"></i>
                        Поля
                    </button>
                    <button class="tab-btn" data-tab="conditional">
                        <i class="fas fa-code-branch"></i>
                        Условия
                    </button>
                    <button class="tab-btn" data-tab="notifications">
                        <i class="fas fa-bell"></i>
                        Уведомления
                    </button>
                    <button class="tab-btn" data-tab="calculator">
                        <i class="fas fa-calculator"></i>
                        Калькулятор
                    </button>
                    <button class="tab-btn" data-tab="preview">
                        <i class="fas fa-eye"></i>
                        Предпросмотр
                    </button>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="tab-content active" id="settingsTab">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Название формы *</label>
                        <input type="text" class="form-control" id="formName" required>
                    </div>
                    <div class="form-group">
                        <label>Слаг *</label>
                        <input type="text" class="form-control" id="formSlug" required>
                        <small class="form-text">Используется в URL (только a-z, 0-9, дефисы)</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea class="form-control" id="formDescription" rows="3"></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Сообщение об успехе</label>
                        <input type="text" class="form-control" id="successMessage" 
                               placeholder="Спасибо! Ваша заявка получена.">
                    </div>
                    <div class="form-group">
                        <label>URL перенаправления (опционально)</label>
                        <input type="text" class="form-control" id="redirectUrl" 
                               placeholder="/thank-you">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Порядок сортировки</label>
                        <input type="number" class="form-control" id="sortOrder" value="0">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="formActive" checked>
                            <span>Активна</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Fields Tab -->
            <div class="tab-content" id="fieldsTab">
                <div class="fields-toolbar">
                    <button class="btn btn-sm btn-primary" id="addFieldBtn">
                        <i class="fas fa-plus"></i>
                        Добавить поле
                    </button>
                    <div class="fields-hint">
                        <i class="fas fa-info-circle"></i>
                        Перетащите поля для изменения порядка
                    </div>
                </div>
                <div id="fieldsList" class="fields-list">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Нет полей. Добавьте первое поле.</p>
                    </div>
                </div>
            </div>

            <!-- Conditional Logic Tab -->
            <div class="tab-content" id="conditionalTab">
                <div class="conditional-toolbar">
                    <button class="btn btn-sm btn-primary" id="addConditionBtn">
                        <i class="fas fa-plus"></i>
                        Добавить условие
                    </button>
                </div>
                <div id="conditionsList" class="conditions-list">
                    <div class="empty-state">
                        <i class="fas fa-code-branch"></i>
                        <p>Нет условий. Создайте первое условие.</p>
                    </div>
                </div>
            </div>

            <!-- Notifications Tab -->
            <div class="tab-content" id="notificationsTab">
                <div class="notifications-section">
                    <h3>Telegram</h3>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="telegramEnabled">
                            <span>Включить Telegram уведомления</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>ID чата (опционально - по умолчанию глобальные настройки)</label>
                        <input type="text" class="form-control" id="telegramChatId" 
                               placeholder="Оставьте пустым для использования глобальных настроек">
                    </div>
                </div>

                <div class="notifications-section">
                    <h3>Email</h3>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="emailEnabled">
                            <span>Включить Email уведомления</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Email получатели (через запятую)</label>
                        <input type="text" class="form-control" id="emailRecipients" 
                               placeholder="admin@example.com, manager@example.com">
                    </div>
                    <div class="form-group">
                        <label>Шаблон email</label>
                        <select class="form-control" id="emailTemplate">
                            <option value="default">По умолчанию</option>
                            <option value="order">Заказ</option>
                            <option value="contact">Обращение</option>
                            <option value="custom">Пользовательский</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Calculator Tab -->
            <div class="tab-content" id="calculatorTab">
                <div class="calculator-section">
                    <p>Свяжите выходы калькулятора с полями формы для автоматического заполнения.</p>
                    <div id="calculatorMappings" class="calculator-mappings">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="calculatorEnabled">
                                <span>Включить интеграцию с калькулятором</span>
                            </label>
                        </div>
                        <div id="mappingsList" class="mappings-list">
                            <div class="empty-state">
                                <i class="fas fa-calculator"></i>
                                <p>Добавьте поля формы для настройки маппинга калькулятора.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Tab -->
            <div class="tab-content" id="previewTab">
                <div class="preview-container">
                    <div id="formPreview" class="form-preview">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Загрузка предпросмотра...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="formsModule.closeEditor()">Отмена</button>
            <button class="btn btn-primary" id="saveFormBtn">
                <i class="fas fa-save"></i>
                Сохранить
            </button>
        </div>
    </div>
</div>

<!-- Field Editor Modal -->
<div class="modal" id="fieldEditorModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="fieldEditorTitle">Редактор поля</h2>
            <button class="btn-close" onclick="formsModule.closeFieldEditor()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editingFieldId">
            <div class="form-grid">
                <div class="form-group">
                    <label>Название поля *</label>
                    <input type="text" class="form-control" id="fieldName" required>
                    <small class="form-text">Используется в коде (a-z, 0-9, подчеркивания)</small>
                </div>
                <div class="form-group">
                    <label>Тип поля *</label>
                    <select class="form-control" id="fieldType" required>
                        <option value="text">Текст</option>
                        <option value="email">Email</option>
                        <option value="phone">Телефон</option>
                        <option value="number">Число</option>
                        <option value="textarea">Многострочный текст</option>
                        <option value="select">Выпадающий список</option>
                        <option value="radio">Радиокнопки</option>
                        <option value="checkbox">Чекбокс</option>
                        <option value="file">Файл</option>
                        <option value="hidden">Скрытое</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Метка поля *</label>
                <input type="text" class="form-control" id="fieldLabel" required>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Placeholder</label>
                    <input type="text" class="form-control" id="fieldPlaceholder">
                </div>
                <div class="form-group">
                    <label>Значение по умолчанию</label>
                    <input type="text" class="form-control" id="fieldDefaultValue">
                </div>
            </div>
            <div class="form-group" id="fieldOptionsGroup" style="display: none;">
                <label>Опции (каждая с новой строки)</label>
                <textarea class="form-control" id="fieldOptions" rows="5" 
                          placeholder="Опция 1&#10;Опция 2&#10;Опция 3"></textarea>
                <small class="form-text">Для select, radio, checkbox</small>
            </div>
            <div class="form-group">
                <label>Текст подсказки</label>
                <textarea class="form-control" id="fieldHelpText" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Правила валидации</label>
                <div class="validation-rules">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="fieldRequired">
                                <span>Обязательное</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Минимальная длина</label>
                            <input type="number" class="form-control" id="fieldMinLength">
                        </div>
                        <div class="form-group">
                            <label>Максимальная длина</label>
                            <input type="number" class="form-control" id="fieldMaxLength">
                        </div>
                    </div>
                    <div class="form-grid" id="numericValidation" style="display: none;">
                        <div class="form-group">
                            <label>Минимальное значение</label>
                            <input type="number" class="form-control" id="fieldMin">
                        </div>
                        <div class="form-group">
                            <label>Максимальное значение</label>
                            <input type="number" class="form-control" id="fieldMax">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Regex паттерн (опционально)</label>
                        <input type="text" class="form-control" id="fieldPattern" 
                               placeholder="^[0-9]+$">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="fieldActive" checked>
                    <span>Активное</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="formsModule.closeFieldEditor()">Отмена</button>
            <button class="btn btn-primary" id="saveFieldBtn">
                <i class="fas fa-save"></i>
                Сохранить поле
            </button>
        </div>
    </div>
</div>

<!-- Condition Editor Modal -->
<div class="modal" id="conditionEditorModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Редактор условия</h2>
            <button class="btn-close" onclick="formsModule.closeConditionEditor()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editingConditionId">
            <div class="form-group">
                <label>Поле для управления видимостью</label>
                <select class="form-control" id="conditionTargetField" required>
                    <option value="">Выберите поле...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Правило</label>
                <div class="condition-rule">
                    <select class="form-control" id="conditionSourceField" required>
                        <option value="">Выберите поле...</option>
                    </select>
                    <select class="form-control" id="conditionOperator" required>
                        <option value="equals">равно</option>
                        <option value="not_equals">не равно</option>
                        <option value="contains">содержит</option>
                        <option value="not_contains">не содержит</option>
                        <option value="empty">пусто</option>
                        <option value="not_empty">не пусто</option>
                    </select>
                    <input type="text" class="form-control" id="conditionValue" placeholder="Значение">
                </div>
            </div>
            <div class="form-group">
                <label>Действие</label>
                <select class="form-control" id="conditionAction" required>
                    <option value="show">Показать поле</option>
                    <option value="hide">Скрыть поле</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="formsModule.closeConditionEditor()">Отмена</button>
            <button class="btn btn-primary" id="saveConditionBtn">
                <i class="fas fa-save"></i>
                Сохранить условие
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
