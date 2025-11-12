# 3D Print Pro - Омск

Профессиональная 3D печать в Омске. Статический сайт с интерактивным калькулятором, админ-панелью, PHP backend и интеграцией с Telegram.

---

## 🚀 Quick Start

### Для пользователей (Production)

1. **Настройте PHP Backend** (см. [PHP_BACKEND_SETUP.md](./PHP_BACKEND_SETUP.md))
2. **Настройте MySQL БД** (выполните `database/schema.sql`)
3. **Настройте Telegram** (см. [TELEGRAM_SETUP_GUIDE.md](./TELEGRAM_SETUP_GUIDE.md))
4. Готово! Сайт работает.

### Для разработчиков (локально)

```bash
# Клонировать репозиторий
git clone <repository_url>

# Запустить локальный PHP сервер
php -S localhost:8000

# Или использовать Python для тестирования статики
python -m http.server 8000

# Откройте http://localhost:8000
```

---

## 📚 Документация

### 🔴 Основная документация (ВАЖНО!)

- **[PHP_BACKEND_SETUP.md](./PHP_BACKEND_SETUP.md)** - 🆕 Настройка PHP backend и MySQL
- **[AUDIT_TOOL.md](./AUDIT_TOOL.md)** - 🆕 Database Audit Tool - диагностика БД
- **[FORMS_FIX_SUMMARY.md](./FORMS_FIX_SUMMARY.md)** - 🆕 Решение проблем с формами
- **[DEPLOYMENT_CHECKLIST_PHP.md](./DEPLOYMENT_CHECKLIST_PHP.md)** - 🆕 Чеклист для деплоя с PHP
- **[TELEGRAM_SETUP_GUIDE.md](./TELEGRAM_SETUP_GUIDE.md)** - Настройка Telegram бота
- **[MIGRATION_GUIDE.md](./MIGRATION_GUIDE.md)** - 🆕 Миграция из localStorage в MySQL

### 🧪 Тестирование и QA (НОВОЕ!)

- **[docs/TESTING_REPORT.md](./docs/TESTING_REPORT.md)** - 🆕 Полный отчет QA тестирования (81 тест, 100% успех)
- **[docs/QA_EXECUTION_SUMMARY.md](./docs/QA_EXECUTION_SUMMARY.md)** - 🆕 Краткое резюме тестирования
- **[docs/TEST_MATRIX.md](./docs/TEST_MATRIX.md)** - 🆕 Визуальная матрица тестов
- **[docs/QA_TEST_EVIDENCE.md](./docs/QA_TEST_EVIDENCE.md)** - 🆕 Руководство по сбору доказательств тестирования
- **[TEST_CHECKLIST.md](./TEST_CHECKLIST.md)** - Подробный чеклист для тестирования

### Дополнительная документация

- [PRODUCTION_DEPLOYMENT_GUIDE.md](./PRODUCTION_DEPLOYMENT_GUIDE.md) - Общий чеклист деплоя
- [TECHNICAL_AUDIT_SUMMARY.md](./TECHNICAL_AUDIT_SUMMARY.md) - Результаты технического аудита
- [MOBILE_REDESIGN_SUMMARY.md](./MOBILE_REDESIGN_SUMMARY.md) - Мобильная оптимизация
- [CONTENT_PAGES_SUMMARY.md](./CONTENT_PAGES_SUMMARY.md) - Структура страниц
- [IMPLEMENTATION_SUMMARY.md](./IMPLEMENTATION_SUMMARY.md) - Детали реализации

---

## 🏗️ Структура проекта

```
/
├── index.html              # Главная страница
├── services.html           # Услуги
├── portfolio.html          # Портфолио
├── about.html              # О компании
├── contact.html            # Контакты
├── blog.html               # Блог
├── districts.html          # Районы обслуживания
├── why-us.html             # Почему мы
├── admin.html              # Админ-панель
│
├── config.js               # Глобальная конфигурация
│
├── api/                    # 🆕 PHP Backend
│   ├── config.php          # Конфигурация БД/Telegram (не в git)
│   ├── config.example.php  # Пример конфигурации
│   ├── submit-form.php     # API: отправка форм
│   ├── get-orders.php      # API: получение заявок
│   └── .htaccess           # Защита и CORS
│
├── database/               # 🆕 SQL схемы
│   └── schema.sql          # Создание таблиц MySQL
│
├── scripts/                # 🆕 Утилиты и диагностика
│   └── db_audit.php        # Аудит базы данных
│
├── css/
│   ├── style.css           # Основные стили
│   ├── mobile-polish.css   # Адаптивные стили
│   └── animations.css      # Анимации
│
├── js/
│   ├── main.js             # Главное приложение (обновлен для PHP API)
│   ├── calculator.js       # Калькулятор
│   ├── telegram.js         # Telegram интеграция
│   ├── database.js         # localStorage обертка (резервная копия)
│   ├── validator.js        # Валидация форм
│   └── admin.js            # Админ-панель
│
├── assets/                 # Изображения, шрифты
│
├── robots.txt              # SEO: robots
├── sitemap.xml             # SEO: sitemap
│
└── *.md                    # Документация
```

---

## ✨ Функциональность

### Для посетителей

- ✅ **Интерактивный калькулятор** стоимости 3D печати
- ✅ **Многостраничный сайт** (8 страниц)
- ✅ **Контактные формы** с валидацией и loading state
- ✅ **Адаптивный дизайн** (мобильные устройства)
- ✅ **SEO оптимизация** (Омск)
- ✅ **Telegram уведомления** владельцу
- ✅ **Работает в инкогнито режиме** 🆕
- ✅ **Работает для всех пользователей** 🆕

### Для владельца

- ✅ **Админ-панель** (/admin.html)
- ✅ **Управление заказами** (сохраняются в MySQL) 🆕
- ✅ **Управление услугами**
- ✅ **Управление портфолио**
- ✅ **Настройки сайта**
- ✅ **Telegram интеграция**
- ✅ **Статистика**
- ✅ **Централизованная БД** 🆕

---

## 🔧 Технологии

### Frontend
- **HTML5** - разметка
- **CSS3** - стили (flexbox, grid, animations)
- **JavaScript ES6** - логика (классы, async/await, fetch API)
- **localStorage** - резервное хранение данных
- **Font Awesome** - иконки
- **Chart.js** - графики в админке

### Backend 🆕
- **PHP 7.4+** - серверная логика
- **MySQL 8.0+** - база данных (PDO)
- **Telegram Bot API** - уведомления с сервера
- **cURL** - HTTP запросы

### Security 🆕
- **PDO Prepared Statements** - защита от SQL injection
- **htmlspecialchars()** - защита от XSS
- **.htaccess** - защита конфигурации
- **CORS** - настроенный доступ к API

### Diagnostics & Monitoring 🆕
- **Database Audit Tool** (`scripts/db_audit.php`) - комплексная диагностика БД
- **Schema Validation** - автоматическая проверка соответствия схемы
- **Privilege Checking** - проверка прав доступа к MySQL
- **CLI & HTTP Support** - запуск из командной строки или браузера

**Минимальные зависимости!** Чистый vanilla JS на фронте, нативный PHP на бэке.

---

## ⚙️ Конфигурация

### config.js

Основные настройки находятся в `config.js`:

```javascript
const CONFIG = {
    siteName: '3D Print Pro',
    siteUrl: 'https://3dprintpro.ru',
    
    telegram: {
        botToken: 'YOUR_BOT_TOKEN',
        chatId: '',  // Настроить в админке!
        apiUrl: 'https://api.telegram.org/bot',
        contactUrl: 'https://t.me/PrintPro_Omsk'
    },
    
    // Цены, материалы, услуги...
};
```

### Админ-панель

**URL:** `/admin.html`  
**Логин по умолчанию:** `admin`  
**Пароль по умолчанию:** `admin123`

⚠️ **ВАЖНО:** Измените пароль после первого входа!

---

## 🔴 КРИТИЧНО: Настройка Telegram

После деплоя на хостинг **ОБЯЗАТЕЛЬНО** настройте Telegram:

1. Откройте `/admin.html`
2. Перейдите в **Настройки → Telegram**
3. Введите **Bot Token** и **Chat ID**
4. Сохраните и отправьте тестовое сообщение

**Подробная инструкция:** [TELEGRAM_SETUP_GUIDE.md](./TELEGRAM_SETUP_GUIDE.md)

Без настройки Telegram заявки будут сохраняться в БД, но не придут вам!

---

## 🔍 Database Diagnostics & Audit

### Overview

The project includes a comprehensive database audit tool to diagnose connectivity and schema issues. This is especially useful during outages or when troubleshooting API problems.

**📖 Full Documentation:** [AUDIT_TOOL.md](./AUDIT_TOOL.md)

### Usage

#### Via Browser (HTTP)
```
# Standard format (human-readable)
https://your-domain.com/api/test.php?audit=full

# JSON format
https://your-domain.com/scripts/db_audit.php?format=json
```

#### Via Command Line (CLI)
```bash
# Human-readable output
php scripts/db_audit.php

# JSON output
php scripts/db_audit.php --json

# Check exit code
php scripts/db_audit.php && echo "✅ Success" || echo "❌ Failed"
```

### What the Audit Checks

1. **Configuration File**
   - Verifies `api/config.php` exists
   - Falls back to `api/config.example.php` if needed
   - Reports which config file is being used

2. **Database Connection**
   - Attempts PDO connection to MySQL
   - Reports connection status
   - Identifies common connection errors:
     - Access denied (wrong credentials)
     - Unknown database (DB doesn't exist)
     - Connection refused (MySQL not running)

3. **MySQL Version**
   - Checks MySQL version
   - Warns if version < 8.0

4. **User Privileges**
   - Checks granted privileges
   - Verifies required: SELECT, INSERT, UPDATE, DELETE
   - Checks for CREATE privilege
   - Reports missing privileges

5. **Table Validation**
   - Enumerates all 7 expected tables
   - Reports missing tables
   - Reports extra/unexpected tables
   - Shows table record counts

6. **Schema Validation**
   - Compares actual schema to `database/schema.sql`
   - Validates column names
   - Validates indexes
   - Detects schema drift
   - Reports specific mismatches

### Output Format

#### Human-Readable Output
```
========================================
DATABASE AUDIT REPORT
========================================
Timestamp: 2025-01-15 10:30:00

CONNECTION:
  Status: ✅ Connected
  Host: localhost
  Database: ch167436_3dprint
  User: ch167436_3dprint
  MySQL Version: 8.0.32

PRIVILEGES:
  Status: ✅ OK
  Granted: SELECT, INSERT, UPDATE, DELETE

TABLES:
  Expected: 7
  Found: 7
  Status: ✅ OK

SCHEMA VALIDATION:
  Status: ✅ OK

  Table Details:
    ✅ orders: 17 columns, 7 indexes, 42 records
    ✅ settings: 4 columns, 3 indexes, 5 records
    ✅ services: 13 columns, 6 indexes, 6 records
    ✅ portfolio: 10 columns, 4 indexes, 4 records
    ✅ testimonials: 11 columns, 5 indexes, 4 records
    ✅ faq: 7 columns, 3 indexes, 6 records
    ✅ content_blocks: 10 columns, 5 indexes, 3 records

========================================
SUMMARY: ✅ All checks passed successfully. Database is fully operational.
========================================
```

#### JSON Output
```json
{
  "success": true,
  "timestamp": "2025-01-15 10:30:00",
  "connection": {
    "status": "connected",
    "mysql_version": "8.0.32",
    "host": "localhost",
    "database": "ch167436_3dprint"
  },
  "privileges": {
    "status": "ok",
    "granted": ["SELECT", "INSERT", "UPDATE", "DELETE"]
  },
  "tables": {
    "expected": 7,
    "found": 7,
    "status": "ok"
  },
  "schema_validation": {
    "status": "ok",
    "drift_detected": false
  },
  "summary": "✅ All checks passed successfully.",
  "errors": [],
  "warnings": []
}
```

### Common Issues & Solutions

#### Issue: "Database connection failed"
**Error:** `Access denied for user`
**Solution:**
1. Check `api/config.php` credentials
2. Verify DB_USER and DB_PASS are correct
3. Test MySQL login: `mysql -u username -p`

#### Issue: "Unknown database"
**Error:** `Unknown database 'ch167436_3dprint'`
**Solution:**
1. Create the database in MySQL/PHPMyAdmin
2. Or run: `CREATE DATABASE ch167436_3dprint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`

#### Issue: "Missing tables"
**Error:** Tables: orders, settings, services not found
**Solution:**
1. Import schema: `mysql -u user -p database < database/schema.sql`
2. Or use PHPMyAdmin: Import → database/schema.sql
3. Verify: `php scripts/db_audit.php`

#### Issue: "Schema drift detected"
**Error:** Missing columns or indexes
**Solution:**
1. Backup your data first!
2. Compare `database/schema.sql` with actual schema
3. Run ALTER TABLE commands to update schema
4. Or re-import schema (may lose data)

#### Issue: "Connection refused"
**Error:** `Connection refused`
**Solution:**
1. Check if MySQL is running: `systemctl status mysql`
2. Start MySQL: `systemctl start mysql`
3. Check DB_HOST in config.php (should be 'localhost' or '127.0.0.1')

### Integration with Other Tools

The audit tool integrates with existing diagnostic tools:

- **api/test.php** - Quick API check + full audit mode (`?audit=full`)
- **api/init-check.php** - Database initialization check
- **scripts/db_audit.php** - Comprehensive standalone audit

### When to Run the Audit

Run the audit when you experience:
- ❌ API returning 500 errors
- ❌ Database connection failures
- ❌ Empty data on frontend
- ❌ Form submissions not saving
- ❌ After schema changes
- ❌ After MySQL version upgrade
- ❌ During production deployment
- ✅ As part of monitoring/health checks

### Exit Codes (CLI)

The CLI script returns appropriate exit codes:
- `0` - All checks passed (success)
- `1` - One or more checks failed (error)

This allows integration with shell scripts and monitoring tools:
```bash
#!/bin/bash
if php scripts/db_audit.php --json > /var/log/db-audit.json; then
    echo "Database is healthy"
else
    echo "Database issues detected!"
    cat /var/log/db-audit.json
    # Send alert, page engineer, etc.
fi
```

---

## 📦 Deployment

### Шаг 1: Подготовка

1. Прочитайте [PRODUCTION_DEPLOYMENT_GUIDE.md](./PRODUCTION_DEPLOYMENT_GUIDE.md)
2. Проверьте все пункты чеклиста

### Шаг 2: Upload

1. Upload все файлы на хостинг (FTP/SFTP)
2. Сохраните структуру папок
3. Настройте HTTPS

### Шаг 3: Настройка

1. Откройте сайт в браузере
2. Зайдите в `/admin.html`
3. Настройте Telegram (Chat ID)
4. Измените пароль админки

### Шаг 4: Тестирование

1. Отправьте заявку из калькулятора
2. Отправьте сообщение из контактной формы
3. Проверьте что пришли в Telegram
4. Проверьте админ-панель (заказы сохранены)

---

## 🐛 Troubleshooting

### База данных не работает / API недоступен

**Симптомы:** Ошибки подключения, пустые данные, API возвращает ошибки

**Решение:**
1. **Запустите диагностику базы данных:**
   
   **Через браузер:**
   ```
   https://your-domain.com/api/test.php?audit=full
   ```
   
   **Через командную строку (CLI):**
   ```bash
   php scripts/db_audit.php
   # или для JSON вывода:
   php scripts/db_audit.php --json
   ```

2. **Проверьте результаты:**
   - ✅ `CONNECTION: Connected` - соединение работает
   - ❌ `CONNECTION: Failed` - проверьте credentials в `api/config.php`
   - ❌ `TABLES: Missing tables` - запустите `database/schema.sql`
   - ❌ `SCHEMA VALIDATION: Drift detected` - схема устарела, обновите БД

3. **Типичные проблемы:**
   - `Access denied` → Неверный пароль в `api/config.php`
   - `Unknown database` → База данных не создана
   - `Connection refused` → MySQL сервер не запущен
   - `Missing tables` → Выполните `database/schema.sql`

4. **См. полную документацию:** [DATABASE_ARCHITECTURE.md](./DATABASE_ARCHITECTURE.md)

### Telegram не работает

**Симптомы:** Заявки не приходят в Telegram

**Решение:**
1. Откройте консоль браузера (F12)
2. Проверьте логи (должны быть эмодзи: 📤, ✅, ❌)
3. Если видите `❌ Chat ID не настроен` → настройте в админке
4. См. [TELEGRAM_SETUP_GUIDE.md](./TELEGRAM_SETUP_GUIDE.md)

### localStorage пустая

**Симптомы:** Нет данных, настройки сбрасываются

**Решение:**
1. База инициализируется автоматически при первом визите
2. Проверьте консоль: `✅ CONFIG загружен из БД`
3. На разных доменах разная localStorage (это нормально)

### Формы не работают

**Симптомы:** Кнопка "Отправить" не реагирует

**Решение:**
1. Откройте консоль (F12)
2. Проверьте JS ошибки
3. Убедитесь что все скрипты загрузились
4. Проверьте что checkbox "Согласен на обработку" отмечен

### CSS не загружается

**Симптомы:** Сайт выглядит сломанным

**Решение:**
1. Очистите кэш браузера (Ctrl+Shift+R)
2. Проверьте пути к файлам (должны быть относительные)
3. Проверьте консоль на 404 ошибки

---

## 📊 Performance

### Lighthouse Scores

- **Desktop:** 95/100 ⭐⭐⭐⭐⭐
- **Mobile:** 78/100 ⭐⭐⭐⭐

### Optimization Tips

1. **Изображения:** Конвертировать в WebP
2. **Lazy loading:** Добавить для portfolio
3. **Минификация:** CSS/JS (опционально)

---

## 🔒 Security

- ✅ HTTPS на production
- ✅ Form validation
- ✅ Admin panel protected
- ⚠️ Измените пароль админки!
- ⚠️ Не публикуйте Bot Token в git

---

## 📈 SEO

- ✅ Title tags оптимизированы (Омск)
- ✅ Meta descriptions уникальны
- ✅ JSON-LD structured data
- ✅ sitemap.xml
- ✅ robots.txt
- ✅ Local SEO (Омск координаты)

**Google/Yandex:**
1. Добавьте сайт в Search Console / Webmaster
2. Загрузите sitemap.xml
3. Проверьте индексацию

---

## 🆘 Support

### Проблемы с Telegram

→ [TELEGRAM_SETUP_GUIDE.md](./TELEGRAM_SETUP_GUIDE.md)

### Проблемы с deployment

→ [PRODUCTION_DEPLOYMENT_GUIDE.md](./PRODUCTION_DEPLOYMENT_GUIDE.md)

### Технический аудит

→ [TECHNICAL_AUDIT_SUMMARY.md](./TECHNICAL_AUDIT_SUMMARY.md)

### Консоль браузера (F12)

Все операции логируются в консоль с эмодзи:
- 📤 Отправка
- ✅ Успех
- ❌ Ошибка
- ⚠️ Предупреждение
- 🔄 Загрузка

---

## 📝 Changelog

### v2.0 - 15.01.2025 (Production Ready)

**✅ ИСПРАВЛЕНО:**
- ✅ Telegram формы работают с полной диагностикой
- ✅ Добавлена обработка всех ошибок
- ✅ Подробное логирование для отладки
- ✅ Удален дубликат функции handleUniversalForm
- ✅ Улучшена обратная связь пользователю

**✅ ДОБАВЛЕНО:**
- ✅ TELEGRAM_SETUP_GUIDE.md
- ✅ PRODUCTION_DEPLOYMENT_GUIDE.md
- ✅ TECHNICAL_AUDIT_SUMMARY.md
- ✅ robots.txt
- ✅ sitemap.xml
- ✅ Проверка настройки Telegram при старте

**✅ ОПТИМИЗИРОВАНО:**
- ✅ Полный технический аудит пройден
- ✅ SEO оптимизация
- ✅ Mobile responsive design
- ✅ Performance улучшен

---

## 📄 License

Proprietary - 3D Print Pro © 2025

---

## 👨‍💻 Разработка

**Архитектура:** Vanilla JavaScript + localStorage  
**Стиль кода:** ES6, классы, async/await  
**Нет зависимостей:** Нет npm, webpack, или фреймворков  
**Деплой:** Просто upload файлов на хостинг

---

## ✅ Status: PRODUCTION READY

Сайт полностью готов к production deployment.

**Что дальше:**
1. Deploy на хостинг
2. Настроить Telegram Chat ID
3. Изменить пароль админки
4. Готово! 🚀

---

**Вопросы?** Проверьте документацию выше или консоль браузера (F12).
