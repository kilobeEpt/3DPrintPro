# Database Architecture & API Documentation

## Обзор

Полная переработка архитектуры данных с localStorage на MySQL + PHP API. Теперь все данные хранятся централизованно в базе данных и доступны через RESTful API.

## Структура базы данных

### 1. Таблица `orders` (Заявки)
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- order_number (VARCHAR(50), UNIQUE)
- type (ENUM: 'order', 'contact')
- name (VARCHAR(255), NOT NULL)
- email (VARCHAR(255))
- phone (VARCHAR(20), NOT NULL)
- telegram (VARCHAR(100))
- service (VARCHAR(255))
- subject (VARCHAR(255))
- message (TEXT)
- amount (DECIMAL(10, 2))
- calculator_data (JSON)
- status (ENUM: 'new', 'processing', 'completed', 'cancelled')
- telegram_sent (BOOLEAN)
- telegram_error (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 2. Таблица `settings` (Настройки)
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- setting_key (VARCHAR(100), UNIQUE, NOT NULL)
- setting_value (TEXT)
- updated_at (TIMESTAMP)
```

### 3. Таблица `services` (Услуги)
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- name (VARCHAR(255), NOT NULL)
- slug (VARCHAR(255), UNIQUE, NOT NULL)
- icon (VARCHAR(255))
- description (TEXT)
- features (JSON)
- price (VARCHAR(100))
- category (VARCHAR(100))
- sort_order (INT)
- active (BOOLEAN)
- featured (BOOLEAN)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 4. Таблица `portfolio` (Портфолио)
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- title (VARCHAR(255), NOT NULL)
- description (TEXT)
- image_url (VARCHAR(500))
- category (VARCHAR(100))
- tags (JSON)
- sort_order (INT)
- active (BOOLEAN)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 5. Таблица `testimonials` (Отзывы)
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- name (VARCHAR(255), NOT NULL)
- position (VARCHAR(255))
- avatar (VARCHAR(500))
- text (TEXT, NOT NULL)
- rating (INT)
- sort_order (INT)
- approved (BOOLEAN)
- active (BOOLEAN)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 6. Таблица `faq` (FAQ)
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- question (VARCHAR(500), NOT NULL)
- answer (TEXT, NOT NULL)
- sort_order (INT)
- active (BOOLEAN)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 7. Таблица `content_blocks` (Текстовые блоки)
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- block_name (VARCHAR(255), UNIQUE, NOT NULL)
- title (VARCHAR(500))
- content (LONGTEXT)
- data (JSON)
- page (VARCHAR(100))
- sort_order (INT)
- active (BOOLEAN)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## PHP API Endpoints

### Базовый URL: `/api/`

### 1. Settings API (`settings.php`)

**GET** - Получить настройки
- `GET /api/settings.php` - все настройки
- `GET /api/settings.php?key=telegram_chat_id` - одна настройка

**POST/PUT** - Сохранить настройки
```javascript
// Одна настройка
{ "key": "telegram_chat_id", "value": "123456" }

// Несколько настроек
{ "site_name": "3D Print Pro", "contact_phone": "+7..." }
```

**DELETE** - Удалить настройку
```javascript
{ "key": "setting_key" }
```

### 2. Orders API (`orders.php`)

**GET** - Получить заказы
- `GET /api/orders.php` - все заказы
- `GET /api/orders.php?id=123` - один заказ
- `GET /api/orders.php?status=new&limit=50&offset=0` - с фильтрами

**POST** - Создать заказ
```javascript
{
  "name": "Иван Иванов",
  "phone": "+7 999 123-45-67",
  "email": "ivan@example.com",
  "service": "3D печать",
  "message": "Описание заказа",
  "amount": 1500,
  "calculatorData": {...}
}
```

**PUT** - Обновить заказ
```javascript
{
  "id": 123,
  "status": "processing"
}
```

**DELETE** - Удалить заказ
- `DELETE /api/orders.php?id=123`

### 3. Services API (`services.php`)

**GET** - Получить услуги
- `GET /api/services.php` - все услуги
- `GET /api/services.php?active=true&featured=true` - с фильтрами

**POST** - Создать услугу
```javascript
{
  "name": "FDM печать",
  "slug": "fdm",
  "description": "...",
  "features": ["...", "..."],
  "price": "от 50₽/г",
  "active": true,
  "featured": false
}
```

**PUT** - Обновить услугу
```javascript
{
  "id": 1,
  "price": "от 60₽/г"
}
```

**DELETE** - Удалить услугу
- `DELETE /api/services.php?id=1`

### 4. Portfolio API (`portfolio.php`)
### 5. Testimonials API (`testimonials.php`)
### 6. FAQ API (`faq.php`)
### 7. Content API (`content.php`)

> Аналогичная структура CRUD операций для каждого endpoint

## JavaScript API Client

### Класс `APIClient`

```javascript
const apiClient = new APIClient('/api');

// Settings
await apiClient.getAllSettings();
await apiClient.getSetting('telegram_chat_id');
await apiClient.saveSettings({ site_name: "..." });

// Orders
await apiClient.getOrders({ status: 'new', limit: 50 });
await apiClient.submitOrder({ name: "...", phone: "..." });
await apiClient.updateOrder(id, { status: 'processing' });
await apiClient.deleteOrder(id);

// Services
await apiClient.getServices({ active: true });
await apiClient.createService({ name: "...", ... });
await apiClient.updateService(id, { price: "..." });
await apiClient.deleteService(id);

// Portfolio
await apiClient.getPortfolio();
await apiClient.createPortfolioItem({ title: "...", ... });
// ... аналогично для testimonials, faq, content
```

## Database Class (Обертка)

### Использование в коде

```javascript
const db = new Database();

// Автоматически использует API если доступен
// Fallback на localStorage если API недоступен

// Services
const services = await db.getServices();
await db.addService({ name: "...", ... });
await db.updateService(id, { price: "..." });
await db.deleteService(id);

// Portfolio
const portfolio = await db.getPortfolio();
await db.addPortfolioItem({ title: "...", ... });

// Testimonials
const testimonials = await db.getTestimonials();
await db.addTestimonial({ name: "...", ... });

// FAQ
const faq = await db.getFAQ();
await db.addFAQItem({ question: "...", answer: "..." });

// Orders
const orders = await db.getOrders();
```

## Инициализация базы данных

### Обзор процесса восстановления
Процесс восстановления базы данных состоит из двух этапов:
1. **Создание схемы** - создание структуры таблиц
2. **Заполнение данными** - вставка минимального набора данных для работы

### Шаг 1: Создать таблицы (схема)
```bash
# Вариант 1: PHPMyAdmin
# 1. Откройте PHPMyAdmin
# 2. Выберите базу данных
# 3. Перейдите на вкладку "SQL"
# 4. Скопируйте содержимое database/schema.sql
# 5. Нажмите "Выполнить"

# Вариант 2: MySQL CLI
mysql -u username -p database_name < database/schema.sql

# Вариант 3: Из PHP скрипта
# См. scripts/rebuild-database.php (для экстренного восстановления)
```

**Что происходит:**
- Создаются 7 таблиц (если не существуют)
- Добавляются индексы для производительности
- Вставляется начальная настройка telegram_chat_id
- Файл **идемпотентный** - безопасно запускать многократно

### Шаг 2: Заполнить начальными данными (seed)
```bash
# Обычный режим (добавляет недостающие данные)
https://your-site.com/api/init-database.php

# Режим полного сброса (удаляет все и создаёт заново)
https://your-site.com/api/init-database.php?reset=RESET_TOKEN
```

**Что происходит в обычном режиме:**
- Проверяет наличие каждой записи по уникальному полю
- Добавляет недостающие записи
- Обновляет существующие (если изменились в seed-data.php)
- **Идемпотентный** - можно запускать многократно без дубликатов

**Что происходит в режиме reset:**
- Удаляет ВСЕ данные из таблиц (кроме orders и settings)
- Сбрасывает счётчики AUTO_INCREMENT
- Заполняет таблицы заново из seed-data.php
- **ОСТОРОЖНО:** Удаляет все пользовательские данные!

### Данные которые создаются при инициализации:

**Services (6 записей):**
- FDM печать
- SLA печать  
- 3D моделирование
- Прототипирование
- Постобработка
- Консультация

**Portfolio (4 записи):**
- Архитектурный проект
- Прототип изделия
- Детальная статуэтка
- Промышленная деталь

**Testimonials (4 записи):**
- Отзывы от клиентов с рейтингом 5/5

**FAQ (8 записей):**
- Часто задаваемые вопросы и ответы

**Content Blocks (3 записи):**
- home_hero - главный баннер
- home_features - преимущества
- about_intro - о компании

**Settings (12 ключей):**
- site_name, site_description, site_keywords
- company_name, company_address, company_phone, company_email, company_hours
- telegram_token, telegram_chat_id
- calculator_base_price, calculator_currency, calculator_weight_unit

### Источник данных

Все seed-данные централизованно хранятся в `database/seed-data.php`:
```php
return [
    'services' => [...],
    'portfolio' => [...],
    'testimonials' => [...],
    'faq' => [...],
    'content_blocks' => [...],
    'settings' => [...]
];
```

Для изменения начальных данных отредактируйте этот файл.

### Быстрое восстановление после сбоя

```bash
# 1. Восстановить схему
mysql -u user -p dbname < database/schema.sql

# 2. Заполнить данными
curl https://your-site.com/api/init-database.php

# 3. Проверить статус
curl https://your-site.com/api/test.php

# Готово! База восстановлена за 30 секунд
```

## Миграция с localStorage

### Автоматическая миграция
1. Старые данные остаются в localStorage как fallback
2. API автоматически сохраняет кэш в localStorage
3. При недоступности API используется localStorage

### Ручная миграция
```javascript
// 1. Экспортировать данные из localStorage
db.exportData(); // Скачает JSON файл

// 2. Вручную импортировать в MySQL через admin panel
// или написать скрипт миграции
```

## Преимущества новой архитектуры

✅ **Централизованное хранение** - данные доступны всем пользователям
✅ **Работает в инкогнито** - не зависит от localStorage
✅ **Синхронизация** - настройки едины для всех
✅ **Fallback на localStorage** - работает даже без API
✅ **RESTful API** - стандартная архитектура
✅ **Безопасность** - PDO prepared statements, XSS protection
✅ **Расширяемость** - легко добавить новые endpoints
✅ **Production ready** - готово к деплою

## Требования

- PHP 7.4+
- MySQL 5.7+
- PDO extension
- cURL extension (для Telegram)
- mod_rewrite (для .htaccess)

## Безопасность

- ✅ PDO prepared statements (защита от SQL injection)
- ✅ htmlspecialchars() (защита от XSS)
- ✅ CORS настроен через .htaccess
- ✅ api/config.php защищен (.htaccess deny from all)
- ✅ JSON validation на входе
- ✅ Type checking для параметров

## Production Deployment

1. Загрузить все файлы на хостинг
2. Создать базу данных MySQL
3. Выполнить `database/schema.sql`
4. Настроить `api/config.php` (credentials)
5. Открыть `/api/init-database.php` для заполнения данными
6. Проверить права доступа (PHP files: 644, config.php: 600)
7. Настроить Telegram в admin panel

## Troubleshooting

**Проблема**: Database connection failed
- Проверить credentials в `api/config.php`
- Убедиться что MySQL пользователь имеет права

**Проблема**: CORS error
- Проверить `api/.htaccess`
- Убедиться что mod_headers включен

**Проблема**: 500 Internal Server Error
- Проверить PHP error logs
- Убедиться что PDO extension включен

**Проблема**: Data not loading
- Открыть DevTools Console (F12)
- Проверить Network tab для API requests
- Убедиться что `/api/init-database.php` выполнен

## Support

При возникновении проблем:
1. Проверить Console (F12) для JavaScript errors
2. Проверить Network tab для API errors
3. Проверить PHP error logs на сервере
4. Убедиться что все файлы загружены
