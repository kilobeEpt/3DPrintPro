# Application Layer

This directory contains the application's business logic, organized using a modern PHP architecture pattern.

## Directory Structure

```
app/
├── Models/          # Eloquent ORM models (database entities)
│   ├── BaseModel.php
│   ├── Service.php
│   ├── Order.php
│   ├── Setting.php
│   ├── Portfolio.php
│   ├── FAQ.php
│   ├── Testimonial.php
│   └── ContentBlock.php
└── Services/        # Business logic services (future)
```

## Models

All models extend `BaseModel` which provides common functionality:

- Automatic timestamp management (`created_at`, `updated_at`)
- Active scope (`->active()`)
- Ordered scope (`->ordered()`)

### Available Models

| Model | Table | Description |
|-------|-------|-------------|
| `Service` | `services` | Service offerings and pricing |
| `Order` | `orders` | Customer orders and inquiries |
| `Setting` | `settings` | Application configuration |
| `Portfolio` | `portfolio` | Project showcase items |
| `FAQ` | `faq` | Frequently asked questions |
| `Testimonial` | `testimonials` | Customer reviews |
| `ContentBlock` | `content_blocks` | Dynamic page content |

### Usage Examples

```php
use App\Models\Service;
use App\Models\Order;

// Query active services
$services = Service::active()->ordered()->get();

// Create new order
$order = Order::create([
    'order_number' => 'ORD-' . time(),
    'name' => 'John Doe',
    'phone' => '+1234567890',
    'status' => Order::STATUS_NEW,
]);

// Get setting
$chatId = Setting::get('telegram_chat_id');
```

## Services (Future)

The `Services/` directory is reserved for business logic services that orchestrate models and provide reusable functionality. Examples might include:

- `OrderService` - Order processing logic
- `TelegramService` - Telegram notification handling
- `CalculatorService` - Pricing calculations
- `StatisticsService` - Analytics and reporting

## Naming Conventions

- **Models**: Singular, PascalCase (e.g., `Service`, `Order`)
- **Services**: Descriptive, ends with "Service" (e.g., `OrderService`)
- **Methods**: camelCase (e.g., `getPendingOrders()`)
- **Properties**: snake_case in database, camelCase in PHP

## Best Practices

1. **Keep models thin**: Business logic belongs in Services
2. **Use scopes**: For reusable query conditions
3. **Type hint**: Always type hint parameters and returns
4. **Document**: Use PHPDoc blocks for all public methods
5. **Validate**: Validate data before persisting to database

## See Also

- [Eloquent Setup Documentation](../docs/ELOQUENT_SETUP.md)
- [Database Schema](../database/schema.sql)
- [Laravel Eloquent Docs](https://laravel.com/docs/8.x/eloquent)
