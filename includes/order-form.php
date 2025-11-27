<?php
/**
 * Reusable Order Form Include
 * 
 * Parameters:
 * - $form_heading: Form heading (default: "Заказать 3D печать")
 * - $form_description: Form description (default: "Заполните форму...")
 * - $form_label: Section label (default: "Заказать")
 * - $section_id: Section ID (default: "order-form-section")
 * - $form_id: Form ID (default: "order-form")
 * - $preselect_service: Service to preselect (optional)
 * - $show_info: Show info message at bottom (default: true)
 */

// Set defaults
$form_heading = $form_heading ?? 'Заказать 3D печать';
$form_description = $form_description ?? 'Заполните форму, и мы свяжемся с вами в ближайшее время';
$form_label = $form_label ?? 'Заказать';
$section_id = $section_id ?? 'order-form-section';
$form_id = $form_id ?? 'order-form';
$preselect_service = $preselect_service ?? '';
$show_info = $show_info ?? true;
?>

<section id="<?= htmlspecialchars($section_id) ?>" class="order-form-container">
    <div class="container">
        <div class="section-header">
            <span class="section-label"><?= htmlspecialchars($form_label) ?></span>
            <h2 class="section-title"><?= htmlspecialchars($form_heading) ?></h2>
            <p class="section-description">
                <?= htmlspecialchars($form_description) ?>
            </p>
        </div>
        <div class="order-form-wrapper">
            <form id="<?= htmlspecialchars($form_id) ?>" method="POST" action="/order-submit.php" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="<?= htmlspecialchars($form_id) ?>Fio">
                            <i class="fas fa-user"></i>
                            ФИО*
                        </label>
                        <input type="text" id="<?= htmlspecialchars($form_id) ?>Fio" name="fio" class="form-control" placeholder="Ваше полное имя" required>
                    </div>
                    <div class="form-group">
                        <label for="<?= htmlspecialchars($form_id) ?>Email">
                            <i class="fas fa-envelope"></i>
                            Email*
                        </label>
                        <input type="email" id="<?= htmlspecialchars($form_id) ?>Email" name="email" class="form-control" placeholder="your@email.com" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="<?= htmlspecialchars($form_id) ?>Phone">
                            <i class="fas fa-phone"></i>
                            Телефон*
                        </label>
                        <input type="tel" id="<?= htmlspecialchars($form_id) ?>Phone" name="phone" class="form-control" placeholder="+7 (___) ___-__-__" required>
                    </div>
                    <div class="form-group">
                        <label for="<?= htmlspecialchars($form_id) ?>Telegram">
                            <i class="fab fa-telegram"></i>
                            Telegram username*
                        </label>
                        <input type="text" id="<?= htmlspecialchars($form_id) ?>Telegram" name="telegram" class="form-control" placeholder="username (без @)" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="<?= htmlspecialchars($form_id) ?>Service">
                        <i class="fas fa-cogs"></i>
                        Услуга*
                    </label>
                    <select id="<?= htmlspecialchars($form_id) ?>Service" name="service" class="form-control" required>
                        <option value="">Выберите услугу</option>
                        <option value="FDM печать" <?= $preselect_service === 'FDM печать' ? 'selected' : '' ?>>FDM печать</option>
                        <option value="SLA печать" <?= $preselect_service === 'SLA печать' ? 'selected' : '' ?>>SLA печать</option>
                        <option value="SLS печать" <?= $preselect_service === 'SLS печать' ? 'selected' : '' ?>>SLS печать</option>
                        <option value="Цветная печать" <?= $preselect_service === 'Цветная печать' ? 'selected' : '' ?>>Цветная печать</option>
                        <option value="Постобработка" <?= $preselect_service === 'Постобработка' ? 'selected' : '' ?>>Постобработка</option>
                        <option value="Консультация" <?= $preselect_service === 'Консультация' ? 'selected' : '' ?>>Консультация</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="<?= htmlspecialchars($form_id) ?>Description">
                        <i class="fas fa-comment-alt"></i>
                        Описание проекта*
                    </label>
                    <textarea id="<?= htmlspecialchars($form_id) ?>Description" name="description" class="form-control" rows="5" placeholder="Опишите ваш проект подробно (минимум 10 символов)" required></textarea>
                </div>

                <div class="form-group">
                    <label for="<?= htmlspecialchars($form_id) ?>Files">
                        <i class="fas fa-paperclip"></i>
                        Загрузить файл (опционально)
                    </label>
                    <input type="file" id="<?= htmlspecialchars($form_id) ?>Files" name="files" class="form-control" accept=".stl,.obj,.gcode,.step,.stp,.3mf,.amf,.ply">
                    <small class="form-text">Допустимые форматы: STL, OBJ, GCODE, STEP, 3MF, AMF, PLY (макс. 50 МБ)</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="privacy" required>
                        <span>Согласен на обработку персональных данных</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Отправить заказ
                </button>

                <?php if ($show_info): ?>
                <div class="order-form-info">
                    <i class="fas fa-info-circle"></i>
                    <p>Мы свяжемся с вами в течение 15 минут для уточнения деталей заказа</p>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>
