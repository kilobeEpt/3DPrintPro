# 🔧 Database Rebuild System - Инструкции по восстановлению

## ✅ ЧТО ОБНОВЛЕНО (v2.0)

### 1. database/schema.sql - Улучшенная схема
- ✅ Добавлены подробные комментарии к каждой таблице
- ✅ Указано какие таблицы имеют/не имеют колонку 'active'
- ✅ Опциональные DROP TABLE команды (закомментированы) для полного сброса
- ✅ Идемпотентный - безопасен для многократного запуска
- ✅ Добавлена проверка CHECK для рейтинга отзывов (1-5)

### 2. database/seed-data.php - Централизованные seed-данные (НОВЫЙ)
- ✅ Все начальные данные в одном файле
- ✅ 6 услуг (services) с полным описанием
- ✅ 4 проекта портфолио (portfolio)
- ✅ 4 отзыва клиентов (testimonials)
- ✅ 8 часто задаваемых вопросов (FAQ)
- ✅ 3 текстовых блока (content_blocks)
- ✅ 12 настроек системы (settings)
- ✅ Легко редактировать и расширять

### 3. api/init-database.php - Улучшенная инициализация (v2.0)
- ✅ Полностью идемпотентный - проверяет существующие записи перед вставкой
- ✅ Использует уникальные поля (slug, block_name, title) для проверки дубликатов
- ✅ Обновляет существующие записи если данные изменились
- ✅ Режим hard reset с токеном безопасности (?reset=TOKEN)
- ✅ Детальная отчётность о всех действиях
- ✅ Проверка минимально необходимых данных для production
- ✅ Импортирует данные из database/seed-data.php

---

## 🚀 ИНСТРУКЦИЯ ПО ВОССТАНОВЛЕНИЮ БАЗЫ ДАННЫХ

### Сценарий 1: Первый запуск (чистая база)

**Шаг 1: Создать схему базы данных**
```bash
# Через MySQL CLI:
mysql -u ch167436_3dprint -p ch167436_3dprint < database/schema.sql

# Или через PHPMyAdmin:
# 1. Откройте PHPMyAdmin
# 2. Выберите базу ch167436_3dprint
# 3. Вкладка "SQL"
# 4. Вставьте содержимое database/schema.sql
# 5. Выполнить
```

**Шаг 2: Заполнить начальными данными**
```
Откройте: https://3dprint-omsk.ru/api/init-database.php
```

**Ожидаемый результат:**
```json
{
  "status": "OK",
  "mode": "normal",
  "actions": [
    "✅ Services: 6 added, 0 updated",
    "✅ Portfolio: 4 added, 0 updated",
    "✅ Testimonials: 4 added, 0 updated",
    "✅ FAQ: 8 added, 0 updated",
    "✅ Content blocks: 3 added, 0 updated",
    "✅ Settings: 12 new keys added"
  ],
  "database_stats": {
    "services": 6,
    "portfolio": 4,
    "testimonials": 4,
    "faq": 8,
    "content_blocks": 3,
    "settings": 12
  },
  "summary": "✅ Database initialized successfully - Ready for production",
  "production_ready": true
}
```

### Сценарий 2: Обновление данных (база уже существует)

Просто откройте:
```
https://3dprint-omsk.ru/api/init-database.php
```

Скрипт:
- Проверит каждую запись по уникальному полю
- Добавит недостающие записи
- Обновит существующие, если они изменились в seed-data.php
- **НЕ создаст дубликаты**
- **НЕ удалит пользовательские данные**

### Сценарий 3: Полный сброс (ОПАСНО!)

**Только для экстренного восстановления после серьёзного сбоя!**

```
https://3dprint-omsk.ru/api/init-database.php?reset=CHANGE_ME_IN_PRODUCTION_123456
```

⚠️ **ВНИМАНИЕ:** Это удалит ВСЕ данные из таблиц:
- services
- portfolio
- testimonials
- faq
- content_blocks

Не затрагивает:
- orders (заказы клиентов)
- settings (настройки будут только добавлены, не удалены)

**Когда использовать:**
- После критической ошибки миграции
- При тестировании на dev-окружении
- Для возврата к заводским настройкам

**Шаги для production hard reset:**
1. Сделайте backup базы данных!
2. Измените RESET_TOKEN в api/init-database.php (строка 21)
3. Выполните: `https://site.com/api/init-database.php?reset=YOUR_NEW_TOKEN`
4. Проверьте результат через api/test.php

---

## 📋 БЫСТРЫЙ ЧЕКЛИСТ ВОССТАНОВЛЕНИЯ

### 5-минутный процесс полного восстановления:

```bash
# Шаг 1: Создать схему (если таблиц нет)
mysql -u user -p dbname < database/schema.sql

# Шаг 2: Заполнить данными
curl https://3dprint-omsk.ru/api/init-database.php

# Шаг 3: Проверить статус
curl https://3dprint-omsk.ru/api/test.php

# Готово! ✅
```

### Детальная проверка после восстановления:

**Шаг 1: Проверить схему базы данных**
```bash
# Проверить что все 7 таблиц созданы:
mysql -u user -p dbname -e "SHOW TABLES;"

# Должно показать:
# - orders
# - settings
# - services
# - portfolio
# - testimonials
# - faq
# - content_blocks
```

**Шаг 2: Проверить seed-данные**
```
Откройте: https://3dprint-omsk.ru/api/init-database.php
```

**Ожидаемый результат (первый запуск):**
```json
{
  "status": "OK",
  "mode": "normal",
  "actions": [
    "✅ Services: 6 added, 0 updated",
    "✅ Portfolio: 4 added, 0 updated",
    "✅ Testimonials: 4 added, 0 updated",
    "✅ FAQ: 8 added, 0 updated",
    "✅ Content blocks: 3 added, 0 updated",
    "✅ Settings: 12 new keys added"
  ],
  "database_stats": {
    "services": 6,
    "portfolio": 4,
    "testimonials": 4,
    "faq": 8,
    "content_blocks": 3,
    "settings": 12
  },
  "summary": "✅ Database initialized successfully - Ready for production",
  "production_ready": true
}
```

**Ожидаемый результат (повторный запуск):**
```json
{
  "status": "OK",
  "mode": "normal",
  "actions": [
    "✓ Services already up to date",
    "✓ Portfolio already up to date",
    "✓ Testimonials already up to date",
    "✓ FAQ already up to date",
    "✓ Content blocks already up to date",
    "✓ Settings already up to date"
  ],
  "summary": "✅ Database initialized successfully - Ready for production",
  "production_ready": true
}
```

**Шаг 3: Проверить API endpoints**
```bash
curl https://3dprint-omsk.ru/api/services.php
curl https://3dprint-omsk.ru/api/portfolio.php
curl https://3dprint-omsk.ru/api/testimonials.php
curl https://3dprint-omsk.ru/api/faq.php
curl https://3dprint-omsk.ru/api/content.php
```

Все должны вернуть:
```json
{
  "success": true,
  "items": [...],
  "total": X
}
```

**Шаг 4: Тестировать сайт**
1. Откройте: https://3dprint-omsk.ru/
2. Очистите кэш браузера (Ctrl+Shift+Del)
3. Проверьте консоль (F12) - не должно быть ошибок
4. Убедитесь что отображаются:
   - 6 услуг в блоке "Услуги"
   - 4 проекта в блоке "Портфолио"
   - Отзывы клиентов
   - FAQ вопросы

**Шаг 5: Тестировать формы**
1. Отправьте тестовую заявку через contact form
2. Проверьте в PHPMyAdmin таблицу `orders` - должна появиться запись
3. Попробуйте калькулятор - данные должны сохраниться

---

## 🐛 TROUBLESHOOTING

### Проблема: "Database connection failed"
**Диагностика:**
```bash
# Проверьте что api/config.php существует и содержит корректные credentials
cat api/config.php
```

**Решение:**
1. Убедитесь что файл api/config.php существует
2. Проверьте credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ch167436_3dprint');
   define('DB_USER', 'ch167436_3dprint');
   define('DB_PASS', '852789456');
   ```
3. Проверьте что MySQL сервер запущен
4. Проверьте права пользователя БД

### Проблема: "Table doesn't exist"
**Решение:**
```bash
# Создайте схему:
mysql -u user -p dbname < database/schema.sql

# Или через PHPMyAdmin:
# SQL → вставить database/schema.sql → Выполнить
```

### Проблема: Таблицы пустые или нет данных
**Решение:**
```bash
# Заполните данными:
curl https://3dprint-omsk.ru/api/init-database.php

# Проверьте результат:
curl https://3dprint-omsk.ru/api/test.php
```

### Проблема: Дубликаты данных после повторного запуска
**Ответ:**
Это невозможно! Скрипт api/init-database.php v2.0 полностью идемпотентный:
- Проверяет существующие записи по уникальным полям
- Не создаёт дубликаты
- Безопасен для многократного запуска

---

## 📊 PRODUCTION READY CHECKLIST

После восстановления убедитесь что:

**Схема базы данных:**
- [ ] Все 7 таблиц созданы (orders, settings, services, portfolio, testimonials, faq, content_blocks)
- [ ] Индексы созданы для всех ключевых полей
- [ ] Таблица settings имеет запись telegram_chat_id

**Seed данные:**
- [ ] services: 6 записей (FDM, SLA, моделирование, прототипирование, постобработка, консультация)
- [ ] portfolio: 4 записи (архитектура, прототип, статуэтка, деталь)
- [ ] testimonials: 4 записи (отзывы с рейтингом 5/5)
- [ ] faq: 8 записей (частые вопросы)
- [ ] content_blocks: 3 записи (hero, features, about)
- [ ] settings: 12 ключей (site_*, company_*, telegram_*, calculator_*)

**API работоспособность:**
- [ ] api/test.php возвращает success: true
- [ ] api/services.php возвращает 6 услуг
- [ ] api/portfolio.php возвращает 4 проекта
- [ ] api/testimonials.php возвращает 4 отзыва
- [ ] api/faq.php возвращает 8 вопросов
- [ ] api/content.php возвращает 3 блока

**Фронтенд:**
- [ ] Сайт загружается без ошибок в консоли
- [ ] Услуги отображаются на главной странице
- [ ] Портфолио отображается
- [ ] Отзывы отображаются
- [ ] FAQ отображается
- [ ] Contact form работает и сохраняет в orders
- [ ] Calculator form работает

**Безопасность:**
- [ ] api/config.php защищен через .htaccess
- [ ] RESET_TOKEN изменён на случайную строку (для production)
- [ ] PDO prepared statements используются везде
- [ ] XSS защита включена (htmlspecialchars)

---

## 🎉 РЕЗЮМЕ

### Что было улучшено в v2.0:

**1. Идемпотентность ✅**
- Схема безопасна для повторного запуска (CREATE TABLE IF NOT EXISTS)
- Seed скрипт проверяет дубликаты перед вставкой
- Можно запускать сколько угодно раз без последствий

**2. Централизация данных ✅**
- Все seed-данные в одном файле: database/seed-data.php
- Легко редактировать и расширять
- Источник истины для восстановления

**3. Безопасный reset ✅**
- Hard reset защищён токеном
- Не трогает orders и settings (пользовательские данные)
- Очищает и пересоздаёт только контентные таблицы

**4. Детальная отчётность ✅**
- Показывает сколько записей добавлено/обновлено
- Статистика по всем таблицам
- production_ready флаг для CI/CD

### Быстрое восстановление:
```bash
# Восстановление из нуля за 30 секунд:
mysql -u user -p dbname < database/schema.sql
curl https://site.com/api/init-database.php
curl https://site.com/api/test.php
# ✅ Готово!
```

### Ключевые файлы:
- `database/schema.sql` - Схема БД (идемпотентная)
- `database/seed-data.php` - Централизованные seed-данные
- `api/init-database.php` - Идемпотентный seed скрипт (v2.0)
- `api/db.php` - Database класс (автоматически обрабатывает tables без 'active')

---

**Версия:** v2.0 - Rebuild System  
**Дата:** Январь 2025  
**Статус:** ✅ PRODUCTION READY  
**Время восстановления:** 30 секунд - 5 минут
