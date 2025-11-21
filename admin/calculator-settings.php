<?php
define('ADMIN_INIT', true);
require_once __DIR__ . '/includes/session-config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
Auth::require('/admin/login.php');

$pageTitle = 'Настройки калькулятора';
$pageScripts = ['/admin/js/modules/calculator-settings.js'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Настройки калькулятора</h2>
        <div>
            <button class="btn btn-primary" id="saveAllBtn">
                <i class="fas fa-save"></i>
                Сохранить все изменения
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <div id="validationErrors"></div>
        
        <!-- Tabs -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="materials">
                    <i class="fas fa-cube"></i>
                    Материалы
                </button>
                <button class="tab-btn" data-tab="services">
                    <i class="fas fa-wrench"></i>
                    Услуги
                </button>
                <button class="tab-btn" data-tab="quality">
                    <i class="fas fa-sliders-h"></i>
                    Качество печати
                </button>
                <button class="tab-btn" data-tab="discounts">
                    <i class="fas fa-percent"></i>
                    Скидки
                </button>
                <button class="tab-btn" data-tab="formulas">
                    <i class="fas fa-calculator"></i>
                    Формулы
                </button>
                <button class="tab-btn" data-tab="sandbox">
                    <i class="fas fa-flask"></i>
                    Тестирование
                </button>
            </div>
            
            <!-- Materials Tab -->
            <div class="tab-content active" id="materials-tab">
                <div class="tab-header">
                    <h3>Материалы для печати</h3>
                    <button class="btn btn-secondary" id="addMaterialBtn">
                        <i class="fas fa-plus"></i>
                        Добавить материал
                    </button>
                </div>
                
                <div id="materialsList"></div>
            </div>
            
            <!-- Services Tab -->
            <div class="tab-content" id="services-tab">
                <div class="tab-header">
                    <h3>Дополнительные услуги</h3>
                    <button class="btn btn-secondary" id="addServiceBtn">
                        <i class="fas fa-plus"></i>
                        Добавить услугу
                    </button>
                </div>
                
                <div id="servicesList"></div>
            </div>
            
            <!-- Quality Tab -->
            <div class="tab-content" id="quality-tab">
                <div class="tab-header">
                    <h3>Настройки качества печати</h3>
                    <p class="text-muted">Множители влияют на цену и время печати</p>
                </div>
                
                <div id="qualityList"></div>
            </div>
            
            <!-- Discounts Tab -->
            <div class="tab-content" id="discounts-tab">
                <div class="tab-header">
                    <h3>Объемные скидки</h3>
                    <button class="btn btn-secondary" id="addDiscountBtn">
                        <i class="fas fa-plus"></i>
                        Добавить скидку
                    </button>
                </div>
                
                <div id="discountsList"></div>
            </div>
            
            <!-- Formulas Tab -->
            <div class="tab-content" id="formulas-tab">
                <div class="tab-header">
                    <h3>Формулы расчета</h3>
                    <p class="text-muted">Редактирование математических формул. Будьте осторожны!</p>
                </div>
                
                <div id="formulasList"></div>
            </div>
            
            <!-- Sandbox Tab -->
            <div class="tab-content" id="sandbox-tab">
                <div class="tab-header">
                    <h3>Тестирование калькулятора</h3>
                    <p class="text-muted">Проверьте, как работают ваши настройки</p>
                </div>
                
                <div class="sandbox-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="testTechnology">Технология</label>
                            <select id="testTechnology" class="form-control">
                                <option value="fdm">FDM</option>
                                <option value="sla">SLA</option>
                                <option value="sls">SLS</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="testMaterial">Материал</label>
                            <select id="testMaterial" class="form-control"></select>
                        </div>
                        
                        <div class="form-group">
                            <label for="testWeight">Вес (г)</label>
                            <input type="number" id="testWeight" class="form-control" value="100" min="1" max="10000">
                        </div>
                        
                        <div class="form-group">
                            <label for="testQuantity">Количество</label>
                            <input type="number" id="testQuantity" class="form-control" value="1" min="1" max="1000">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="testInfill">Заполнение (%)</label>
                            <input type="range" id="testInfill" class="form-control" value="20" min="0" max="100">
                            <span id="testInfillValue">20%</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="testQuality">Качество</label>
                            <select id="testQuality" class="form-control"></select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Дополнительные услуги</label>
                        <div id="testServices" class="checkbox-group"></div>
                    </div>
                    
                    <button class="btn btn-primary" id="runTestBtn">
                        <i class="fas fa-play"></i>
                        Рассчитать
                    </button>
                </div>
                
                <div id="testResults" class="test-results" style="display: none;">
                    <h4>Результат расчета</h4>
                    <div id="testResultsContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Material Modal -->
<div id="materialModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="materialModalTitle">Добавить материал</h3>
            <button class="close-btn" onclick="window.calculatorSettings.closeMaterialModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="materialForm">
                <input type="hidden" id="materialIndex" value="">
                
                <div class="form-group">
                    <label for="materialKey">Ключ (латиница, без пробелов)</label>
                    <input type="text" id="materialKey" class="form-control" required>
                    <small class="form-text text-muted">Используется в коде, например: pla, abs, petg</small>
                </div>
                
                <div class="form-group">
                    <label for="materialName">Название</label>
                    <input type="text" id="materialName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="materialPrice">Цена за грамм (₽)</label>
                    <input type="number" id="materialPrice" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="materialTechnology">Технология</label>
                    <select id="materialTechnology" class="form-control" required>
                        <option value="fdm">FDM</option>
                        <option value="sla">SLA</option>
                        <option value="sls">SLS</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="materialActive" checked>
                        <span>Активен (отображается в калькуляторе)</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="window.calculatorSettings.closeMaterialModal()">Отмена</button>
            <button class="btn btn-primary" onclick="window.calculatorSettings.saveMaterial()">Сохранить</button>
        </div>
    </div>
</div>

<!-- Service Modal -->
<div id="serviceModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="serviceModalTitle">Добавить услугу</h3>
            <button class="close-btn" onclick="window.calculatorSettings.closeServiceModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="serviceForm">
                <input type="hidden" id="serviceIndex" value="">
                
                <div class="form-group">
                    <label for="serviceKey">Ключ (латиница, без пробелов)</label>
                    <input type="text" id="serviceKey" class="form-control" required>
                    <small class="form-text text-muted">Используется в коде, например: modeling, painting</small>
                </div>
                
                <div class="form-group">
                    <label for="serviceName">Название</label>
                    <input type="text" id="serviceName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="servicePrice">Цена (₽)</label>
                    <input type="number" id="servicePrice" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="serviceUnit">Единица измерения</label>
                    <select id="serviceUnit" class="form-control" required>
                        <option value="шт">За штуку (умножается на количество)</option>
                        <option value="час">За час</option>
                        <option value="заказ">За заказ (фиксированная цена)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="serviceActive" checked>
                        <span>Активна (отображается в калькуляторе)</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="window.calculatorSettings.closeServiceModal()">Отмена</button>
            <button class="btn btn-primary" onclick="window.calculatorSettings.saveService()">Сохранить</button>
        </div>
    </div>
</div>

<!-- Discount Modal -->
<div id="discountModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="discountModalTitle">Добавить скидку</h3>
            <button class="close-btn" onclick="window.calculatorSettings.closeDiscountModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="discountForm">
                <input type="hidden" id="discountIndex" value="">
                
                <div class="form-group">
                    <label for="discountMinQuantity">Минимальное количество</label>
                    <input type="number" id="discountMinQuantity" class="form-control" min="1" required>
                    <small class="form-text text-muted">От какого количества действует скидка</small>
                </div>
                
                <div class="form-group">
                    <label for="discountPercent">Процент скидки</label>
                    <input type="number" id="discountPercent" class="form-control" min="0" max="100" step="0.1" required>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="discountActive" checked>
                        <span>Активна</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="window.calculatorSettings.closeDiscountModal()">Отмена</button>
            <button class="btn btn-primary" onclick="window.calculatorSettings.saveDiscount()">Сохранить</button>
        </div>
    </div>
</div>

<!-- Formula Modal -->
<div id="formulaModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="formulaModalTitle">Редактировать формулу</h3>
            <button class="close-btn" onclick="window.calculatorSettings.closeFormulaModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formulaForm">
                <input type="hidden" id="formulaKey" value="">
                
                <div class="form-group">
                    <label for="formulaName">Название</label>
                    <input type="text" id="formulaName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="formulaDescription">Описание</label>
                    <textarea id="formulaDescription" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="formulaExpression">Формула</label>
                    <textarea id="formulaExpression" class="form-control formula-input" rows="3" required></textarea>
                    <small class="form-text text-muted">
                        Допустимые операторы: +, -, *, /, (), min, max, abs, ceil, floor, round, sqrt, pow
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="formulaVariables">Переменные (через запятую)</label>
                    <input type="text" id="formulaVariables" class="form-control">
                    <small class="form-text text-muted">Например: weight, price, quantity</small>
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn btn-info" id="validateFormulaBtn">
                        <i class="fas fa-check"></i>
                        Проверить формулу
                    </button>
                    <span id="formulaValidationResult"></span>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="formulaActive" checked>
                        <span>Активна</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="window.calculatorSettings.closeFormulaModal()">Отмена</button>
            <button class="btn btn-primary" onclick="window.calculatorSettings.saveFormula()">Сохранить</button>
        </div>
    </div>
</div>

<style>
.tabs-container {
    margin-top: 20px;
}

.tabs-header {
    display: flex;
    gap: 5px;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 20px;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
}

.tab-btn:hover {
    color: #333;
    background: #f5f5f5;
}

.tab-btn.active {
    color: #2196F3;
    border-bottom-color: #2196F3;
}

.tab-btn i {
    margin-right: 8px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.tab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.tab-header h3 {
    margin: 0;
}

.config-item {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 10px;
}

.config-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.config-item-title {
    font-size: 16px;
    font-weight: 600;
}

.config-item-inactive {
    opacity: 0.6;
}

.config-item-actions {
    display: flex;
    gap: 5px;
}

.config-item-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.config-item-field {
    font-size: 14px;
}

.config-item-label {
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
}

.config-item-value {
    font-weight: 500;
    margin-top: 2px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.sandbox-form {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.test-results {
    background: #fff;
    border: 2px solid #4CAF50;
    border-radius: 6px;
    padding: 20px;
    margin-top: 20px;
}

.test-results h4 {
    color: #4CAF50;
    margin-top: 0;
}

.result-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.result-item {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 6px;
}

.result-label {
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.result-value {
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.formula-input {
    font-family: 'Courier New', monospace;
    font-size: 14px;
}

.modal-large .modal-content {
    max-width: 700px;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
