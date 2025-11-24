#!/usr/bin/env php
<?php
/**
 * Demo Data Seeder
 * 
 * Comprehensive seeder that populates all public and admin surfaces with realistic sample data.
 * Orchestrates existing seeders and adds missing coverage for complete system demonstration.
 * 
 * Usage:
 *   php scripts/seed-demo-data.php [options]
 * 
 * Options:
 *   --force              Truncate existing data and reseed (⚠️ DESTRUCTIVE)
 *   --skip-settings      Skip global settings seeding
 *   --skip-calculator    Skip calculator settings seeding
 *   --skip-forms         Skip forms and form fields seeding
 *   --skip-services      Skip services seeding
 *   --skip-portfolio     Skip portfolio seeding
 *   --skip-testimonials  Skip testimonials seeding
 *   --skip-faq           Skip FAQ seeding
 *   --skip-content       Skip content blocks seeding
 *   --skip-orders        Skip orders and submissions seeding
 *   --skip-admin-users   Skip admin users seeding
 *   --verbose            Detailed logging output
 *   --help               Show this help message
 * 
 * Exit codes:
 *   0 - Success
 *   1 - Failure (insert errors)
 *   2 - Invalid usage
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Service;
use App\Models\Portfolio;
use App\Models\Testimonial;
use App\Models\FAQ;
use App\Models\ContentBlock;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OrderNote;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use App\Models\AdminUser;
use App\Services\ContentCacheService;
use App\Services\SSEBroadcaster;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;

// ========================================
// Configuration & Argument Parsing
// ========================================

$options = [
    'force' => in_array('--force', $argv),
    'verbose' => in_array('--verbose', $argv),
    'help' => in_array('--help', $argv),
    'skip_settings' => in_array('--skip-settings', $argv),
    'skip_calculator' => in_array('--skip-calculator', $argv),
    'skip_forms' => in_array('--skip-forms', $argv),
    'skip_services' => in_array('--skip-services', $argv),
    'skip_portfolio' => in_array('--skip-portfolio', $argv),
    'skip_testimonials' => in_array('--skip-testimonials', $argv),
    'skip_faq' => in_array('--skip-faq', $argv),
    'skip_content' => in_array('--skip-content', $argv),
    'skip_orders' => in_array('--skip-orders', $argv),
    'skip_admin_users' => in_array('--skip-admin-users', $argv),
];

// Show help
if ($options['help']) {
    echo file_get_contents(__FILE__);
    exit(0);
}

// Setup logging
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/seed_demo_data.log';
$logHandle = fopen($logFile, 'a');

function logMessage($message, $level = 'INFO') {
    global $logHandle, $options;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] [{$level}] {$message}\n";
    fwrite($logHandle, $logLine);
    
    if ($options['verbose'] || $level === 'ERROR') {
        echo $logLine;
    }
}

function printSection($title) {
    $line = str_repeat('=', 60);
    echo "\n{$line}\n";
    echo "  {$title}\n";
    echo "{$line}\n\n";
    logMessage($title, 'SECTION');
}

function printSuccess($message) {
    echo "✅ {$message}\n";
    logMessage($message, 'SUCCESS');
}

function printWarning($message) {
    echo "⚠️  {$message}\n";
    logMessage($message, 'WARNING');
}

function printError($message) {
    echo "❌ {$message}\n";
    logMessage($message, 'ERROR');
}

function printInfo($message) {
    echo "ℹ️  {$message}\n";
    logMessage($message, 'INFO');
}

// Initialize services
$cacheService = new ContentCacheService();
$sseBroadcaster = new SSEBroadcaster();

// Statistics
$stats = [
    'services' => 0,
    'portfolio' => 0,
    'testimonials' => 0,
    'faq' => 0,
    'content_blocks' => 0,
    'orders' => 0,
    'order_history' => 0,
    'order_notes' => 0,
    'submissions' => 0,
    'admin_users' => 0,
    'errors' => 0,
];

// ========================================
// Start Seeding
// ========================================

printSection('Demo Data Seeder v1.0');
logMessage('Starting demo data seeding process');

if ($options['force']) {
    printWarning('--force flag enabled: Will truncate existing data!');
    logMessage('Force mode enabled - will truncate tables');
}

try {
    // ========================================
    // 1. Orchestrate Existing Seeders
    // ========================================
    
    printSection('Step 1: Running Existing Seeders');
    
    if (!$options['skip_settings']) {
        printInfo('Running seed-global-settings.php...');
        ob_start();
        include __DIR__ . '/seed-global-settings.php';
        $output = ob_get_clean();
        if ($options['verbose']) echo $output;
        printSuccess('Global settings seeded');
    }
    
    if (!$options['skip_calculator']) {
        printInfo('Running seed-calculator-settings.php...');
        ob_start();
        $forceFlag = $options['force'] ? '--force' : '';
        passthru("php " . __DIR__ . "/seed-calculator-settings.php {$forceFlag}", $returnCode);
        if ($returnCode !== 0) {
            throw new Exception('Calculator settings seeder failed');
        }
        printSuccess('Calculator settings seeded');
    }
    
    if (!$options['skip_forms']) {
        printInfo('Running seed-forms.php...');
        ob_start();
        include __DIR__ . '/seed-forms.php';
        $output = ob_get_clean();
        if ($options['verbose']) echo $output;
        printSuccess('Forms and form fields seeded');
    }
    
    // ========================================
    // 2. Seed Services
    // ========================================
    
    if (!$options['skip_services']) {
        printSection('Step 2: Seeding Services');
        
        if ($options['force']) {
            Service::query()->delete();
            printWarning('Truncated services table');
        }
        
        $services = [
            [
                'name' => 'FDM печать',
                'slug' => 'fdm-printing',
                'icon' => 'fa-cube',
                'description' => 'Технология послойного наплавления пластика. Идеально подходит для прототипирования и функциональных изделий. Печать изделий размером до 300×300×400 мм.',
                'features' => ['Быстрое изготовление', 'Доступная цена', 'Различные пластики', 'Крупные модели'],
                'price' => 'от 50₽/г',
                'category' => 'printing',
                'sort_order' => 1,
                'active' => true,
                'featured' => true
            ],
            [
                'name' => 'SLA печать',
                'slug' => 'sla-printing',
                'icon' => 'fa-diamond',
                'description' => 'Стереолитография - высокая детализация и гладкая поверхность. Для ювелирных изделий и сложных моделей. Точность до 0.025 мм.',
                'features' => ['Высокая детализация', 'Гладкая поверхность', 'Точность до 0.025 мм', 'Сложные формы'],
                'price' => 'от 200₽/г',
                'category' => 'printing',
                'sort_order' => 2,
                'active' => true,
                'featured' => true
            ],
            [
                'name' => 'SLS печать',
                'slug' => 'sls-printing',
                'icon' => 'fa-layer-group',
                'description' => 'Селективное лазерное спекание нейлона. Прочные функциональные детали без поддержек. Идеально для промышленного производства.',
                'features' => ['Высокая прочность', 'Без поддержек', 'Промышленное качество', 'Термостойкость'],
                'price' => 'от 150₽/г',
                'category' => 'printing',
                'sort_order' => 3,
                'active' => true,
                'featured' => true
            ],
            [
                'name' => '3D моделирование',
                'slug' => '3d-modeling',
                'icon' => 'fa-drafting-compass',
                'description' => 'Создание 3D моделей по чертежам, эскизам или фотографиям. Подготовка файлов для печати. Оптимизация моделей для любой технологии печати.',
                'features' => ['Моделирование по ТЗ', 'Оптимизация моделей', 'Подготовка к печати', 'Визуализация'],
                'price' => 'от 500₽/час',
                'category' => 'design',
                'sort_order' => 4,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Прототипирование',
                'slug' => 'prototyping',
                'icon' => 'fa-flask',
                'description' => 'Быстрое изготовление прототипов для тестирования конструкции и функциональности. Итеративная разработка с учётом ваших замечаний.',
                'features' => ['Быстрые итерации', 'Функциональные тесты', 'Оценка эргономики', 'Презентация инвесторам'],
                'price' => 'от 1000₽',
                'category' => 'engineering',
                'sort_order' => 5,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Постобработка',
                'slug' => 'post-processing',
                'icon' => 'fa-paint-brush',
                'description' => 'Шлифовка, грунтовка, покраска и другие виды обработки готовых изделий. Доведём ваше изделие до идеального вида.',
                'features' => ['Шлифовка', 'Покраска', 'Лакировка', 'Сборка деталей'],
                'price' => 'от 300₽',
                'category' => 'finishing',
                'sort_order' => 6,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Консультация',
                'slug' => 'consultation',
                'icon' => 'fa-comments',
                'description' => 'Профессиональная консультация по выбору технологии, материалов и оптимизации проекта. Поможем найти оптимальное решение для вашей задачи.',
                'features' => ['Выбор технологии', 'Подбор материала', 'Оценка стоимости', 'Рекомендации по дизайну'],
                'price' => 'Бесплатно',
                'category' => 'support',
                'sort_order' => 7,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Реверс-инжиниринг',
                'slug' => 'reverse-engineering',
                'icon' => 'fa-search',
                'description' => '3D сканирование и воссоздание цифровых моделей существующих объектов. Восстановление чертежей и моделей.',
                'features' => ['3D сканирование', 'Создание CAD моделей', 'Точные измерения', 'Анализ геометрии'],
                'price' => 'от 2000₽',
                'category' => 'engineering',
                'sort_order' => 8,
                'active' => true,
                'featured' => false
            ],
        ];
        
        foreach ($services as $serviceData) {
            try {
                $existing = Service::where('slug', $serviceData['slug'])->first();
                if ($existing && !$options['force']) {
                    printWarning("Service '{$serviceData['name']}' already exists, skipping");
                    continue;
                }
                
                if ($existing && $options['force']) {
                    $existing->delete();
                }
                
                Service::create($serviceData);
                $stats['services']++;
                printSuccess("Created service: {$serviceData['name']}");
            } catch (Exception $e) {
                $stats['errors']++;
                printError("Failed to create service '{$serviceData['name']}': " . $e->getMessage());
            }
        }
        
        $cacheService->invalidateCache('services');
        $sseBroadcaster->broadcastCacheInvalidation('services');
    }
    
    // ========================================
    // 3. Seed Portfolio
    // ========================================
    
    if (!$options['skip_portfolio']) {
        printSection('Step 3: Seeding Portfolio');
        
        if ($options['force']) {
            Portfolio::query()->delete();
            printWarning('Truncated portfolio table');
        }
        
        $portfolioItems = [
            [
                'title' => 'Архитектурный макет жилого комплекса',
                'slug' => 'architectural-complex-model',
                'description' => 'Детальный архитектурный макет жилого комплекса масштабом 1:500. Включает ландшафт, дороги и инфраструктуру. Изготовлен на FDM принтере с последующей покраской.',
                'category' => 'architecture',
                'tags' => ['архитектура', 'макет', 'FDM', 'постобработка'],
                'sort_order' => 1,
                'active' => true,
                'featured' => true
            ],
            [
                'title' => 'Функциональный прототип корпуса устройства',
                'slug' => 'device-housing-prototype',
                'description' => 'Прототип корпуса электронного устройства с креплениями и посадочными местами. Печать из ABS пластика позволила провести функциональные тесты и доработать конструкцию.',
                'category' => 'prototyping',
                'tags' => ['прототип', 'ABS', 'корпус', 'инженерия'],
                'sort_order' => 2,
                'active' => true,
                'featured' => true
            ],
            [
                'title' => 'Ювелирная мастер-модель кольца',
                'slug' => 'jewelry-ring-master',
                'description' => 'Высокодетальная мастер-модель кольца для последующего литья. Печать выполнена на SLA принтере с разрешением 25 микрон. Идеальная гладкость поверхности.',
                'category' => 'jewelry',
                'tags' => ['SLA', 'ювелирка', 'мастер-модель', 'высокая детализация'],
                'sort_order' => 3,
                'active' => true,
                'featured' => true
            ],
            [
                'title' => 'Промышленная оснастка для производства',
                'slug' => 'industrial-tooling',
                'description' => 'Специализированная оснастка для сборочной линии. Печать из прочного нейлона PA12 на SLS принтере обеспечила необходимую стойкость к износу.',
                'category' => 'industrial',
                'tags' => ['SLS', 'нейлон', 'оснастка', 'промышленность'],
                'sort_order' => 4,
                'active' => true,
                'featured' => true
            ],
            [
                'title' => 'Коллекционная фигурка персонажа',
                'slug' => 'collectible-character-figure',
                'description' => 'Детальная фигурка высотой 30 см с мельчайшими деталями. SLA печать с последующей грунтовкой, покраской и лакировкой. Выставочное качество.',
                'category' => 'decorative',
                'tags' => ['SLA', 'фигурка', 'покраска', 'коллекция'],
                'sort_order' => 5,
                'active' => true,
                'featured' => false
            ],
            [
                'title' => 'Запасная деталь для старой техники',
                'slug' => 'replacement-part-vintage',
                'description' => 'Воссоздание утраченной детали советского фотоаппарата. 3D сканирование, моделирование и печать из PETG. Деталь полностью функциональна.',
                'category' => 'restoration',
                'tags' => ['реверс-инжиниринг', 'PETG', 'запчасть', 'реставрация'],
                'sort_order' => 6,
                'active' => true,
                'featured' => false
            ],
            [
                'title' => 'Медицинская анатомическая модель',
                'slug' => 'medical-anatomical-model',
                'description' => 'Точная анатомическая модель сустава для медицинского обучения. Печать из прозрачной смолы позволяет видеть внутреннюю структуру.',
                'category' => 'medical',
                'tags' => ['SLA', 'медицина', 'обучение', 'анатомия'],
                'sort_order' => 7,
                'active' => true,
                'featured' => false
            ],
            [
                'title' => 'Гибкие шарниры и механизмы',
                'slug' => 'flexible-joints-mechanisms',
                'description' => 'Сборная механическая конструкция с подвижными шарнирами. Печать из TPU обеспечила необходимую гибкость и износостойкость.',
                'category' => 'engineering',
                'tags' => ['TPU', 'гибкий пластик', 'шарниры', 'механизм'],
                'sort_order' => 8,
                'active' => true,
                'featured' => false
            ],
        ];
        
        foreach ($portfolioItems as $itemData) {
            try {
                $existing = Portfolio::where('slug', $itemData['slug'])->first();
                if ($existing && !$options['force']) {
                    printWarning("Portfolio item '{$itemData['title']}' already exists, skipping");
                    continue;
                }
                
                if ($existing && $options['force']) {
                    $existing->delete();
                }
                
                Portfolio::create($itemData);
                $stats['portfolio']++;
                printSuccess("Created portfolio item: {$itemData['title']}");
            } catch (Exception $e) {
                $stats['errors']++;
                printError("Failed to create portfolio item '{$itemData['title']}': " . $e->getMessage());
            }
        }
        
        $cacheService->invalidateCache('portfolio');
        $sseBroadcaster->broadcastCacheInvalidation('portfolio');
    }
    
    // ========================================
    // 4. Seed Testimonials
    // ========================================
    
    if (!$options['skip_testimonials']) {
        printSection('Step 4: Seeding Testimonials');
        
        if ($options['force']) {
            Testimonial::query()->delete();
            printWarning('Truncated testimonials table');
        }
        
        $testimonials = [
            [
                'name' => 'Алексей Петров',
                'slug' => 'aleksey-petrov',
                'position' => 'Директор ООО "ТехноПром"',
                'text' => 'Отличное качество печати и быстрые сроки. Заказывали прототипы деталей для производственной линии - всё сделано точно в срок. Особенно впечатлила прочность изделий из нейлона. Будем обращаться ещё!',
                'rating' => 5,
                'sort_order' => 1,
                'approved' => true,
                'active' => true,
                'featured' => true
            ],
            [
                'name' => 'Мария Соколова',
                'slug' => 'maria-sokolova',
                'position' => 'Архитектор, АБ "Модерн"',
                'text' => 'Профессиональный подход и внимание к деталям. Помогли с изготовлением архитектурного макета для презентации проекта инвесторам. Качество постобработки на высшем уровне. Рекомендую!',
                'rating' => 5,
                'sort_order' => 2,
                'approved' => true,
                'active' => true,
                'featured' => true
            ],
            [
                'name' => 'Игорь Васильев',
                'slug' => 'igor-vasiliev',
                'position' => 'Предприниматель',
                'text' => 'Сделали функциональный прототип моего изобретения за 3 дня. Качество превзошло все ожидания - деталь полностью работоспособна. Помогли с оптимизацией конструкции. Продолжим сотрудничество!',
                'rating' => 5,
                'sort_order' => 3,
                'approved' => true,
                'active' => true,
                'featured' => true
            ],
            [
                'name' => 'Елена Кузнецова',
                'slug' => 'elena-kuznetsova',
                'position' => 'Дизайнер интерьеров',
                'text' => 'Заказывала декоративные элементы для дизайн-проекта. SLA печать - это просто волшебство! Детализация потрясающая, поверхность идеально гладкая. После покраски выглядит как фабричное изделие.',
                'rating' => 5,
                'sort_order' => 4,
                'approved' => true,
                'active' => true,
                'featured' => true
            ],
            [
                'name' => 'Дмитрий Сидоров',
                'slug' => 'dmitriy-sidorov',
                'position' => 'Инженер-конструктор',
                'text' => 'Обращался за изготовлением сложной технической детали. Ребята не только напечатали, но и помогли оптимизировать модель для печати. Сэкономили мне кучу времени и денег на итерациях.',
                'rating' => 5,
                'sort_order' => 5,
                'approved' => true,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Анна Морозова',
                'slug' => 'anna-morozova',
                'position' => 'Владелец ювелирной мастерской',
                'text' => 'Печатаем у них мастер-модели для литья. Точность и детализация на уровне, цена адекватная. Особенно радует быстрота - модель готова на следующий день. Спасибо за качественную работу!',
                'rating' => 5,
                'sort_order' => 6,
                'approved' => true,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Сергей Новиков',
                'slug' => 'sergey-novikov',
                'position' => 'Руководитель производства',
                'text' => 'Заказывали оснастку для производства. Нейлоновые детали из SLS служат уже полгода без износа. Отличная альтернатива фрезеровке - быстрее и дешевле. Очень довольны результатом.',
                'rating' => 5,
                'sort_order' => 7,
                'approved' => true,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Ольга Титова',
                'slug' => 'olga-titova',
                'position' => 'Коллекционер',
                'text' => 'Заказывала фигурку персонажа для коллекции. Печать + покраска - получилось шедевр! Мелкие детали проработаны идеально. Цена вполне справедливая за такое качество.',
                'rating' => 5,
                'sort_order' => 8,
                'approved' => true,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Виктор Лебедев',
                'slug' => 'viktor-lebedev',
                'position' => 'Владелец автосервиса',
                'text' => 'Напечатали утраченную пластиковую деталь старого автомобиля. Сначала сканировали сохранившийся фрагмент, потом воссоздали целую деталь. Сидит как родная! Супер сервис.',
                'rating' => 5,
                'sort_order' => 9,
                'approved' => true,
                'active' => true,
                'featured' => false
            ],
            [
                'name' => 'Наталья Волкова',
                'slug' => 'natalya-volkova',
                'position' => 'Преподаватель медицинского ВУЗа',
                'text' => 'Заказывали анатомические модели для учебного процесса. Точность воспроизведения анатомии отличная. Студенты в восторге от наглядных пособий. Планируем заказать ещё несколько моделей.',
                'rating' => 5,
                'sort_order' => 10,
                'approved' => true,
                'active' => true,
                'featured' => false
            ],
        ];
        
        foreach ($testimonials as $testimonialData) {
            try {
                $existing = Testimonial::where('slug', $testimonialData['slug'])->first();
                if ($existing && !$options['force']) {
                    printWarning("Testimonial from '{$testimonialData['name']}' already exists, skipping");
                    continue;
                }
                
                if ($existing && $options['force']) {
                    $existing->delete();
                }
                
                Testimonial::create($testimonialData);
                $stats['testimonials']++;
                printSuccess("Created testimonial: {$testimonialData['name']}");
            } catch (Exception $e) {
                $stats['errors']++;
                printError("Failed to create testimonial '{$testimonialData['name']}': " . $e->getMessage());
            }
        }
        
        $cacheService->invalidateCache('testimonials');
        $sseBroadcaster->broadcastCacheInvalidation('testimonials');
    }
    
    // ========================================
    // 5. Seed FAQ
    // ========================================
    
    if (!$options['skip_faq']) {
        printSection('Step 5: Seeding FAQ');
        
        if ($options['force']) {
            FAQ::query()->delete();
            printWarning('Truncated faq table');
        }
        
        $faqItems = [
            [
                'question' => 'Какие материалы вы используете для печати?',
                'slug' => 'materials',
                'answer' => 'Мы работаем с широким спектром материалов: PLA, ABS, PETG, TPU, Nylon для FDM печати; различные фотополимерные смолы для SLA печати; нейлон PA12 и TPU для SLS печати. Поможем подобрать оптимальный материал для вашего проекта исходя из требований к прочности, детализации и условий эксплуатации.',
                'sort_order' => 1,
                'active' => true
            ],
            [
                'question' => 'Каковы сроки изготовления?',
                'slug' => 'production-time',
                'answer' => 'Сроки зависят от сложности и размера модели. Простые изделия печатаются за 1-2 дня, сложные проекты могут занять до недели. Постобработка (покраска, шлифовка) добавляет 2-3 дня. Возможна срочная печать за дополнительную плату - готовность в течение 24 часов.',
                'sort_order' => 2,
                'active' => true
            ],
            [
                'question' => 'Какой максимальный размер изделия можно напечатать?',
                'slug' => 'max-size',
                'answer' => 'Максимальные размеры зависят от технологии: FDM - до 300×300×400 мм, SLA - до 192×120×200 мм, SLS - до 330×330×400 мм. Крупные модели можем печатать по частям с последующей склейкой и постобработкой швов. Итоговый размер ограничен только вашей фантазией.',
                'sort_order' => 3,
                'active' => true
            ],
            [
                'question' => 'У меня нет 3D модели. Можете создать?',
                'slug' => 'no-3d-model',
                'answer' => 'Да, оказываем полный спектр услуг 3D моделирования. Можем создать модель по техническим чертежам, эскизам, фотографиям или устному описанию. Также выполняем реверс-инжиниринг - создание цифровой модели существующего объекта с помощью 3D сканирования.',
                'sort_order' => 4,
                'active' => true
            ],
            [
                'question' => 'Как рассчитывается стоимость?',
                'slug' => 'pricing',
                'answer' => 'Стоимость зависит от веса изделия, типа материала, технологии печати, качества поверхности и сложности постобработки. Используйте калькулятор на сайте для предварительного расчёта. Для точной оценки пришлите модель - мы бесплатно рассчитаем стоимость и сроки.',
                'sort_order' => 5,
                'active' => true
            ],
            [
                'question' => 'Предоставляете ли вы гарантию на изделия?',
                'slug' => 'warranty',
                'answer' => 'Да, гарантируем качество печати и соответствие согласованным параметрам. Если изделие не соответствует техническому заданию или имеет дефекты печати - переделаем бесплатно. Гарантия не распространяется на естественный износ и механические повреждения при эксплуатации.',
                'sort_order' => 6,
                'active' => true
            ],
            [
                'question' => 'Можно ли заказать постобработку?',
                'slug' => 'post-processing',
                'answer' => 'Да, предлагаем полный спектр постобработки: шлифовку, грунтовку, покраску, лакировку, полировку, сборку составных изделий. Также выполняем химическую обработку для улучшения поверхности. Цена постобработки рассчитывается индивидуально в зависимости от сложности и размера изделия.',
                'sort_order' => 7,
                'active' => true
            ],
            [
                'question' => 'Работаете с юридическими лицами?',
                'slug' => 'legal-entities',
                'answer' => 'Да, работаем как с физическими, так и с юридическими лицами. Предоставляем все необходимые документы: договор, счёт, акт выполненных работ, счёт-фактуру. Возможна оплата по безналичному расчёту с НДС или без НДС.',
                'sort_order' => 8,
                'active' => true
            ],
            [
                'question' => 'Можно ли приехать и посмотреть процесс печати?',
                'slug' => 'visit',
                'answer' => 'Конечно! Приглашаем посетить нашу мастерскую, посмотреть оборудование в работе и обсудить ваш проект. Предварительно позвоните или напишите, чтобы мы могли уделить вам время. Также проводим экскурсии для учебных групп.',
                'sort_order' => 9,
                'active' => true
            ],
            [
                'question' => 'Выполняете ли вы серийное производство?',
                'slug' => 'mass-production',
                'answer' => 'Да, выполняем серийное производство. 3D печать оптимальна для малых и средних серий (от 10 до 1000 шт). При больших объёмах предоставляем скидки. Также можем изготовить пресс-формы для литья пластика - это выгоднее для очень больших тиражей.',
                'sort_order' => 10,
                'active' => true
            ],
            [
                'question' => 'Какая точность печати?',
                'slug' => 'accuracy',
                'answer' => 'Точность зависит от технологии: FDM - ±0.1-0.2 мм, SLA - ±0.025-0.05 мм, SLS - ±0.1 мм. Для особо точных изделий рекомендуем SLA печать. При необходимости выполняем механическую доработку (сверление, нарезка резьбы) для достижения нужных допусков.',
                'sort_order' => 11,
                'active' => true
            ],
            [
                'question' => 'Можно ли печатать металлом?',
                'slug' => 'metal-printing',
                'answer' => 'Напрямую металлом мы не печатаем, но можем изготовить восковую или смоляную мастер-модель для последующего литья металла по выплавляемым моделям. Это широко применяется в ювелирном деле и промышленности. Также есть пластики с металлическим наполнением.',
                'sort_order' => 12,
                'active' => true
            ],
        ];
        
        foreach ($faqItems as $faqData) {
            try {
                $existing = FAQ::where('slug', $faqData['slug'])->first();
                if ($existing && !$options['force']) {
                    printWarning("FAQ '{$faqData['question']}' already exists, skipping");
                    continue;
                }
                
                if ($existing && $options['force']) {
                    $existing->delete();
                }
                
                FAQ::create($faqData);
                $stats['faq']++;
                printSuccess("Created FAQ: {$faqData['question']}");
            } catch (Exception $e) {
                $stats['errors']++;
                printError("Failed to create FAQ '{$faqData['question']}': " . $e->getMessage());
            }
        }
        
        $cacheService->invalidateCache('faq');
        $sseBroadcaster->broadcastCacheInvalidation('faq');
    }
    
    // ========================================
    // 6. Seed Content Blocks
    // ========================================
    
    if (!$options['skip_content']) {
        printSection('Step 6: Seeding Content Blocks');
        
        if ($options['force']) {
            ContentBlock::query()->delete();
            printWarning('Truncated content_blocks table');
        }
        
        $contentBlocks = [
            [
                'block_name' => 'home_hero',
                'slug' => 'home-hero',
                'title' => 'Профессиональная 3D печать в Омске',
                'content' => 'Высококачественные услуги 3D печати с использованием современного оборудования. FDM, SLA и SLS технологии. Быстрое изготовление прототипов и готовых изделий. Опыт работы более 12 лет.',
                'page' => 'index',
                'sort_order' => 1,
                'active' => true
            ],
            [
                'block_name' => 'home_features',
                'slug' => 'home-features',
                'title' => 'Наши преимущества',
                'content' => 'Профессиональное оборудование • Опытные специалисты • Быстрые сроки • Доступные цены • Качественная постобработка • Работаем с юр. лицами',
                'data' => [
                    'features' => [
                        ['icon' => 'fa-clock', 'title' => 'Быстрое изготовление', 'text' => 'От 1 дня'],
                        ['icon' => 'fa-dollar-sign', 'title' => 'Доступные цены', 'text' => 'От 50₽/г'],
                        ['icon' => 'fa-certificate', 'title' => 'Гарантия качества', 'text' => '100%'],
                        ['icon' => 'fa-headset', 'title' => 'Консультации', 'text' => 'Бесплатно']
                    ]
                ],
                'page' => 'index',
                'sort_order' => 2,
                'active' => true
            ],
            [
                'block_name' => 'about_company',
                'slug' => 'about-company',
                'title' => 'О компании 3D Print Pro',
                'content' => 'Компания 3D Print Pro - один из ведущих центров 3D печати в Омске. Мы специализируемся на высокоточной 3D печати и моделировании с 2012 года. За годы работы выполнили более 5000 проектов для частных клиентов и промышленных предприятий.',
                'data' => [
                    'stats' => [
                        ['number' => '12+', 'label' => 'лет опыта'],
                        ['number' => '5000+', 'label' => 'проектов'],
                        ['number' => '3', 'label' => 'технологии'],
                        ['number' => '99%', 'label' => 'довольных клиентов']
                    ]
                ],
                'page' => 'about',
                'sort_order' => 1,
                'active' => true
            ],
            [
                'block_name' => 'about_equipment',
                'slug' => 'about-equipment',
                'title' => 'Наше оборудование',
                'content' => 'Мы используем только профессиональное оборудование ведущих мировых производителей. FDM принтеры Raise3D и Prusa, SLA принтеры Formlabs, SLS принтер Sinterit. Все принтеры регулярно проходят техническое обслуживание и калибровку.',
                'page' => 'about',
                'sort_order' => 2,
                'active' => true
            ],
            [
                'block_name' => 'about_team',
                'slug' => 'about-team',
                'title' => 'Наша команда',
                'content' => 'В нашей команде работают опытные специалисты: 3D моделлеры, операторы печати, специалисты по постобработке. Каждый сотрудник прошёл обучение и имеет опыт работы от 3 лет. Мы постоянно следим за новинками индустрии и обучаемся новым технологиям.',
                'page' => 'about',
                'sort_order' => 3,
                'active' => true
            ],
            [
                'block_name' => 'services_intro',
                'slug' => 'services-intro',
                'title' => 'Наши услуги 3D печати',
                'content' => 'Предлагаем полный спектр услуг 3D печати и моделирования. Работаем с любыми проектами - от мелких деталей до крупногабаритных изделий. Помогаем на всех этапах: от идеи до готового изделия.',
                'page' => 'services',
                'sort_order' => 1,
                'active' => true
            ],
            [
                'block_name' => 'contact_info',
                'slug' => 'contact-info',
                'title' => 'Свяжитесь с нами',
                'content' => 'Готовы ответить на ваши вопросы и помочь с реализацией проекта. Звоните, пишите или приезжайте в нашу мастерскую. Консультации бесплатны!',
                'page' => 'contacts',
                'sort_order' => 1,
                'active' => true
            ],
        ];
        
        foreach ($contentBlocks as $blockData) {
            try {
                $existing = ContentBlock::where('slug', $blockData['slug'])->first();
                if ($existing && !$options['force']) {
                    printWarning("Content block '{$blockData['block_name']}' already exists, skipping");
                    continue;
                }
                
                if ($existing && $options['force']) {
                    $existing->delete();
                }
                
                ContentBlock::create($blockData);
                $stats['content_blocks']++;
                printSuccess("Created content block: {$blockData['block_name']}");
            } catch (Exception $e) {
                $stats['errors']++;
                printError("Failed to create content block '{$blockData['block_name']}': " . $e->getMessage());
            }
        }
        
        $cacheService->invalidateCache('content');
        $sseBroadcaster->broadcastCacheInvalidation('content');
    }
    
    // ========================================
    // 7. Seed Orders with History and Notes
    // ========================================
    
    if (!$options['skip_orders']) {
        printSection('Step 7: Seeding Orders, Submissions & History');
        
        if ($options['force']) {
            OrderNote::query()->delete();
            OrderStatusHistory::query()->delete();
            Order::query()->delete();
            FormSubmission::query()->delete();
            printWarning('Truncated orders, submissions, history and notes tables');
        }
        
        // Get form IDs
        $orderForm = Form::where('slug', 'order')->first();
        $contactForm = Form::where('slug', 'contact')->first();
        
        if (!$orderForm || !$contactForm) {
            printWarning('Forms not found - skipping orders seeding. Run seed-forms.php first.');
        } else {
            // Create demo admin user for attribution if not exists
            $demoAdmin = AdminUser::where('email', 'demo@3dprint-omsk.ru')->first();
            if (!$demoAdmin) {
                $demoAdmin = AdminUser::create([
                    'email' => 'demo@3dprint-omsk.ru',
                    'name' => 'Demo Admin',
                    'password_hash' => password_hash('demo123', PASSWORD_BCRYPT),
                    'role' => AdminUser::ROLE_ADMIN,
                    'status' => AdminUser::STATUS_ACTIVE,
                ]);
                printInfo('Created demo admin user for order attribution');
            }
            
            $orders = [
                [
                    'type' => Order::TYPE_ORDER,
                    'form_slug' => 'order',
                    'form_id' => $orderForm->id,
                    'name' => 'Иван Смирнов',
                    'phone' => '+7 (913) 123-45-67',
                    'email' => 'ivan.smirnov@example.com',
                    'telegram' => '@ivan_smirnov',
                    'service' => 'FDM печать',
                    'message' => 'Нужно напечатать прототип корпуса для электронного устройства. Размер примерно 15x10x5 см. Материал ABS.',
                    'amount' => 2500.00,
                    'status' => Order::STATUS_COMPLETED,
                    'calculator_data' => [
                        'technology' => 'fdm',
                        'material' => 'abs',
                        'weight' => 150,
                        'quantity' => 1,
                        'infill' => 20,
                        'quality' => 'normal',
                        'totalCost' => 2500
                    ],
                    'created_at' => Carbon::now()->subDays(15),
                    'history' => [
                        ['status' => Order::STATUS_NEW, 'days_ago' => 15, 'comment' => 'Заказ принят в обработку'],
                        ['status' => Order::STATUS_PROCESSING, 'days_ago' => 14, 'comment' => 'Подготовка модели к печати'],
                        ['status' => Order::STATUS_COMPLETED, 'days_ago' => 12, 'comment' => 'Заказ выполнен и отправлен клиенту'],
                    ],
                    'notes' => [
                        ['days_ago' => 14, 'text' => 'Клиент прислал файл модели, проверили на ошибки - всё ОК'],
                        ['days_ago' => 13, 'text' => 'Печать завершена, приступаем к постобработке'],
                    ]
                ],
                [
                    'type' => Order::TYPE_ORDER,
                    'form_slug' => 'order',
                    'form_id' => $orderForm->id,
                    'name' => 'Мария Иванова',
                    'phone' => '+7 (923) 234-56-78',
                    'email' => 'maria.ivanova@example.com',
                    'service' => 'SLA печать',
                    'message' => 'Необходимо изготовить ювелирную мастер-модель кольца для последующего литья. Высокая детализация критична.',
                    'amount' => 1800.00,
                    'status' => Order::STATUS_PROCESSING,
                    'calculator_data' => [
                        'technology' => 'sla',
                        'material' => 'standard_resin',
                        'weight' => 8,
                        'quantity' => 1,
                        'quality' => 'ultra',
                        'totalCost' => 1800
                    ],
                    'created_at' => Carbon::now()->subDays(3),
                    'history' => [
                        ['status' => Order::STATUS_NEW, 'days_ago' => 3, 'comment' => 'Новый заказ на мастер-модель'],
                        ['status' => Order::STATUS_PROCESSING, 'days_ago' => 2, 'comment' => 'Печать запущена'],
                    ],
                    'notes' => [
                        ['days_ago' => 2, 'text' => 'Клиент прислал STL файл, качество модели отличное'],
                        ['days_ago' => 1, 'text' => 'Печать завершена, модель на этапе промывки и засветки'],
                    ]
                ],
                [
                    'type' => Order::TYPE_ORDER,
                    'form_slug' => 'order',
                    'form_id' => $orderForm->id,
                    'name' => 'Сергей Козлов',
                    'phone' => '+7 (913) 345-67-89',
                    'email' => 'sergey.kozlov@example.com',
                    'telegram' => '@sergey_k',
                    'service' => 'SLS печать',
                    'message' => 'Требуется серия из 20 одинаковых деталей из нейлона для промышленного использования.',
                    'amount' => 15000.00,
                    'status' => Order::STATUS_NEW,
                    'calculator_data' => [
                        'technology' => 'sls',
                        'material' => 'pa12',
                        'weight' => 50,
                        'quantity' => 20,
                        'quality' => 'high',
                        'totalCost' => 15000
                    ],
                    'created_at' => Carbon::now()->subHours(5),
                    'history' => [
                        ['status' => Order::STATUS_NEW, 'days_ago' => 0, 'comment' => 'Заказ поступил, ожидает обработки'],
                    ],
                    'notes' => []
                ],
                [
                    'type' => Order::TYPE_CONTACT,
                    'form_slug' => 'contact',
                    'form_id' => $contactForm->id,
                    'name' => 'Елена Попова',
                    'phone' => '+7 (923) 456-78-90',
                    'email' => 'elena.popova@example.com',
                    'subject' => 'Консультация по выбору материала',
                    'message' => 'Здравствуйте! Хочу напечатать детали для механизма. Нужен прочный и износостойкий материал. Что посоветуете?',
                    'amount' => 0,
                    'status' => Order::STATUS_COMPLETED,
                    'created_at' => Carbon::now()->subDays(7),
                    'history' => [
                        ['status' => Order::STATUS_NEW, 'days_ago' => 7, 'comment' => 'Запрос на консультацию'],
                        ['status' => Order::STATUS_PROCESSING, 'days_ago' => 6, 'comment' => 'Консультация предоставлена по телефону'],
                        ['status' => Order::STATUS_COMPLETED, 'days_ago' => 6, 'comment' => 'Клиент получил рекомендации'],
                    ],
                    'notes' => [
                        ['days_ago' => 6, 'text' => 'Позвонил клиенту, порекомендовал нейлон PA12 или PETG в зависимости от нагрузки'],
                    ]
                ],
                [
                    'type' => Order::TYPE_ORDER,
                    'form_slug' => 'order',
                    'form_id' => $orderForm->id,
                    'name' => 'Андрей Морозов',
                    'phone' => '+7 (913) 567-89-01',
                    'email' => 'andrey.morozov@example.com',
                    'service' => 'FDM печать',
                    'message' => 'Нужен набор запасных деталей для старого оборудования. Предоставлю образцы для сканирования.',
                    'amount' => 4200.00,
                    'status' => Order::STATUS_PROCESSING,
                    'calculator_data' => [
                        'technology' => 'fdm',
                        'material' => 'petg',
                        'weight' => 200,
                        'quantity' => 5,
                        'quality' => 'high',
                        'totalCost' => 4200
                    ],
                    'created_at' => Carbon::now()->subDays(5),
                    'history' => [
                        ['status' => Order::STATUS_NEW, 'days_ago' => 5, 'comment' => 'Заказ на реверс-инжиниринг'],
                        ['status' => Order::STATUS_PROCESSING, 'days_ago' => 4, 'comment' => '3D сканирование выполнено, модели готовы'],
                    ],
                    'notes' => [
                        ['days_ago' => 4, 'text' => 'Клиент привёз образцы деталей, выполнили 3D сканирование'],
                        ['days_ago' => 3, 'text' => 'Модели созданы, согласованы с клиентом, запускаем печать'],
                    ]
                ],
                [
                    'type' => Order::TYPE_CONTACT,
                    'form_slug' => 'contact',
                    'form_id' => $contactForm->id,
                    'name' => 'Ольга Федорова',
                    'phone' => '+7 (923) 678-90-12',
                    'email' => 'olga.fedorova@example.com',
                    'subject' => 'Вопрос по срокам изготовления',
                    'message' => 'Добрый день! Интересует срочная печать архитектурного макета. Возможно ли изготовление за 2 дня?',
                    'amount' => 0,
                    'status' => Order::STATUS_NEW,
                    'created_at' => Carbon::now()->subHours(2),
                    'history' => [
                        ['status' => Order::STATUS_NEW, 'days_ago' => 0, 'comment' => 'Новый запрос, требуется ответ'],
                    ],
                    'notes' => []
                ],
                [
                    'type' => Order::TYPE_ORDER,
                    'form_slug' => 'order',
                    'form_id' => $orderForm->id,
                    'name' => 'Дмитрий Волков',
                    'phone' => '+7 (913) 789-01-23',
                    'email' => 'dmitriy.volkov@example.com',
                    'telegram' => '@d_volkov',
                    'service' => 'SLA печать',
                    'message' => 'Заказываю детальную фигурку персонажа с последующей покраской. Высота 25 см.',
                    'amount' => 6500.00,
                    'status' => Order::STATUS_CANCELLED,
                    'calculator_data' => [
                        'technology' => 'sla',
                        'material' => 'standard_resin',
                        'weight' => 180,
                        'quantity' => 1,
                        'quality' => 'ultra',
                        'services' => ['painting'],
                        'totalCost' => 6500
                    ],
                    'created_at' => Carbon::now()->subDays(20),
                    'history' => [
                        ['status' => Order::STATUS_NEW, 'days_ago' => 20, 'comment' => 'Заказ на фигурку'],
                        ['status' => Order::STATUS_CANCELLED, 'days_ago' => 19, 'comment' => 'Клиент отменил заказ по личным причинам'],
                    ],
                    'notes' => [
                        ['days_ago' => 19, 'text' => 'Клиент позвонил и попросил отменить заказ - не готова модель'],
                    ]
                ],
                [
                    'type' => Order::TYPE_ORDER,
                    'form_slug' => 'order',
                    'form_id' => $orderForm->id,
                    'name' => 'Наталья Соловьева',
                    'phone' => '+7 (923) 890-12-34',
                    'email' => 'natalya.solovyeva@example.com',
                    'service' => 'FDM печать',
                    'message' => 'Нужно напечатать учебные пособия для школы - анатомические модели органов. Всего 10 комплектов.',
                    'amount' => 8500.00,
                    'status' => Order::STATUS_COMPLETED,
                    'calculator_data' => [
                        'technology' => 'fdm',
                        'material' => 'pla',
                        'weight' => 500,
                        'quantity' => 10,
                        'quality' => 'normal',
                        'totalCost' => 8500
                    ],
                    'created_at' => Carbon::now()->subDays(25),
                    'history' => [
                        ['status' => Order::STATUS_NEW, 'days_ago' => 25, 'comment' => 'Крупный заказ для образовательного учреждения'],
                        ['status' => Order::STATUS_PROCESSING, 'days_ago' => 23, 'comment' => 'Печать первой партии запущена'],
                        ['status' => Order::STATUS_COMPLETED, 'days_ago' => 18, 'comment' => 'Все 10 комплектов изготовлены и переданы заказчику'],
                    ],
                    'notes' => [
                        ['days_ago' => 24, 'text' => 'Согласовали модели, цвета для каждого органа'],
                        ['days_ago' => 20, 'text' => 'Печать идёт по графику, первые 5 комплектов готовы'],
                    ]
                ],
            ];
            
            foreach ($orders as $orderData) {
                try {
                    // Create form submission
                    $submissionData = [
                        'name' => $orderData['name'],
                        'phone' => $orderData['phone'],
                        'email' => $orderData['email'] ?? null,
                    ];
                    
                    if ($orderData['type'] === Order::TYPE_ORDER) {
                        $submissionData['service'] = $orderData['service'];
                        $submissionData['message'] = $orderData['message'];
                    } else {
                        $submissionData['subject'] = $orderData['subject'] ?? null;
                        $submissionData['message'] = $orderData['message'];
                    }
                    
                    if (isset($orderData['telegram'])) {
                        $submissionData['telegram'] = $orderData['telegram'];
                    }
                    
                    $submission = FormSubmission::create([
                        'form_id' => $orderData['form_id'],
                        'form_slug' => $orderData['form_slug'],
                        'submitted_data' => $submissionData,
                        'status' => $orderData['status'] === Order::STATUS_COMPLETED ? 
                            FormSubmission::STATUS_PROCESSED : FormSubmission::STATUS_PENDING,
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Demo Seeder',
                        'submitted_at' => $orderData['created_at'],
                        'created_at' => $orderData['created_at'],
                    ]);
                    
                    $stats['submissions']++;
                    
                    // Create order
                    $order = Order::create([
                        'order_number' => 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                        'type' => $orderData['type'],
                        'form_submission_id' => $submission->id,
                        'form_slug' => $orderData['form_slug'],
                        'name' => $orderData['name'],
                        'phone' => $orderData['phone'],
                        'email' => $orderData['email'] ?? null,
                        'telegram' => $orderData['telegram'] ?? null,
                        'service' => $orderData['service'] ?? null,
                        'subject' => $orderData['subject'] ?? null,
                        'message' => $orderData['message'],
                        'amount' => $orderData['amount'],
                        'calculator_data' => $orderData['calculator_data'] ?? null,
                        'status' => $orderData['status'],
                        'telegram_sent' => true,
                        'created_at' => $orderData['created_at'],
                        'updated_at' => $orderData['created_at'],
                    ]);
                    
                    $stats['orders']++;
                    
                    // Create status history
                    foreach ($orderData['history'] as $historyItem) {
                        $historyDate = Carbon::now()->subDays($historyItem['days_ago']);
                        OrderStatusHistory::create([
                            'order_id' => $order->id,
                            'from_status' => null,
                            'to_status' => $historyItem['status'],
                            'changed_by' => $demoAdmin->id,
                            'comment' => $historyItem['comment'],
                            'created_at' => $historyDate,
                            'updated_at' => $historyDate,
                        ]);
                        $stats['order_history']++;
                    }
                    
                    // Create notes
                    foreach ($orderData['notes'] as $noteItem) {
                        $noteDate = Carbon::now()->subDays($noteItem['days_ago']);
                        OrderNote::create([
                            'order_id' => $order->id,
                            'admin_user_id' => $demoAdmin->id,
                            'note' => $noteItem['text'],
                            'created_at' => $noteDate,
                            'updated_at' => $noteDate,
                        ]);
                        $stats['order_notes']++;
                    }
                    
                    printSuccess("Created order: {$order->order_number} ({$orderData['name']})");
                    
                } catch (Exception $e) {
                    $stats['errors']++;
                    printError("Failed to create order for '{$orderData['name']}': " . $e->getMessage());
                }
            }
            
            $cacheService->invalidateCache('orders');
            $sseBroadcaster->broadcastCacheInvalidation('orders');
        }
    }
    
    // ========================================
    // 8. Seed Admin Users (Optional)
    // ========================================
    
    if (!$options['skip_admin_users']) {
        printSection('Step 8: Seeding Admin Users (Optional)');
        
        $demoUsers = [
            [
                'email' => 'admin@3dprint-omsk.ru',
                'name' => 'Администратор',
                'password' => 'admin123',
                'role' => AdminUser::ROLE_SUPER_ADMIN,
                'status' => AdminUser::STATUS_ACTIVE,
            ],
            [
                'email' => 'manager@3dprint-omsk.ru',
                'name' => 'Менеджер',
                'password' => 'manager123',
                'role' => AdminUser::ROLE_ADMIN,
                'status' => AdminUser::STATUS_ACTIVE,
            ],
            [
                'email' => 'editor@3dprint-omsk.ru',
                'name' => 'Редактор',
                'password' => 'editor123',
                'role' => AdminUser::ROLE_EDITOR,
                'status' => AdminUser::STATUS_ACTIVE,
            ],
        ];
        
        foreach ($demoUsers as $userData) {
            try {
                $existing = AdminUser::where('email', $userData['email'])->first();
                if ($existing && !$options['force']) {
                    printWarning("Admin user '{$userData['email']}' already exists, skipping");
                    continue;
                }
                
                if ($existing && $options['force']) {
                    $existing->delete();
                }
                
                $user = AdminUser::create([
                    'email' => $userData['email'],
                    'name' => $userData['name'],
                    'password_hash' => password_hash($userData['password'], PASSWORD_BCRYPT),
                    'role' => $userData['role'],
                    'status' => $userData['status'],
                ]);
                
                $stats['admin_users']++;
                printSuccess("Created admin user: {$userData['name']} ({$userData['email']})");
                printInfo("  Password: {$userData['password']}");
                
            } catch (Exception $e) {
                $stats['errors']++;
                printError("Failed to create admin user '{$userData['email']}': " . $e->getMessage());
            }
        }
    }
    
    // ========================================
    // Summary
    // ========================================
    
    printSection('Seeding Complete');
    
    echo "Summary:\n";
    echo "--------\n";
    echo "✅ Services:         {$stats['services']}\n";
    echo "✅ Portfolio:        {$stats['portfolio']}\n";
    echo "✅ Testimonials:     {$stats['testimonials']}\n";
    echo "✅ FAQ:              {$stats['faq']}\n";
    echo "✅ Content Blocks:   {$stats['content_blocks']}\n";
    echo "✅ Orders:           {$stats['orders']}\n";
    echo "✅ Submissions:      {$stats['submissions']}\n";
    echo "✅ Order History:    {$stats['order_history']}\n";
    echo "✅ Order Notes:      {$stats['order_notes']}\n";
    echo "✅ Admin Users:      {$stats['admin_users']}\n";
    
    if ($stats['errors'] > 0) {
        echo "❌ Errors:           {$stats['errors']}\n";
    }
    
    echo "\n";
    
    logMessage('Demo data seeding completed', 'INFO');
    logMessage(json_encode($stats), 'STATS');
    
    fclose($logHandle);
    
    if ($stats['errors'] > 0) {
        printError("Seeding completed with {$stats['errors']} errors. Check log: {$logFile}");
        exit(1);
    }
    
    printSuccess("All demo data seeded successfully!");
    printInfo("Log file: {$logFile}");
    
    exit(0);
    
} catch (Exception $e) {
    printError("Fatal error during seeding: " . $e->getMessage());
    logMessage("FATAL: " . $e->getMessage(), 'ERROR');
    logMessage($e->getTraceAsString(), 'ERROR');
    fclose($logHandle);
    exit(1);
}
