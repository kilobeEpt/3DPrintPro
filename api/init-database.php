<?php
// ========================================
// Database Initialization Script
// Populates database with default data
// ========================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$db = new Database();

try {
    // Initialize Services
    $services = [
        [
            'name' => 'FDM печать',
            'slug' => 'fdm',
            'icon' => 'fa-cube',
            'description' => 'Печать методом послойного наплавления. Идеально для прототипов и функциональных деталей.',
            'features' => ['Быстрое изготовление', 'Низкая стоимость', 'Прочные детали', 'Широкий выбор материалов'],
            'price' => 'от 50₽/г',
            'active' => 1,
            'featured' => 0,
            'sort_order' => 1
        ],
        [
            'name' => 'SLA/SLS печать',
            'slug' => 'sla',
            'icon' => 'fa-gem',
            'description' => 'Высокоточная печать с невероятной детализацией для самых требовательных проектов.',
            'features' => ['Высокая точность', 'Гладкая поверхность', 'Сложная геометрия', 'Идеально для ювелирки'],
            'price' => 'от 200₽/г',
            'active' => 1,
            'featured' => 1,
            'sort_order' => 2
        ],
        [
            'name' => 'Post-обработка',
            'slug' => 'post',
            'icon' => 'fa-cogs',
            'description' => 'Шлифовка, покраска, сборка. Доводим изделия до идеального состояния.',
            'features' => ['Профессиональная покраска', 'Химическая обработка', 'Сборка узлов', 'Гарантия качества'],
            'price' => 'от 300₽',
            'active' => 1,
            'featured' => 0,
            'sort_order' => 3
        ],
        [
            'name' => '3D моделирование',
            'slug' => 'modeling',
            'icon' => 'fa-drafting-compass',
            'description' => 'Создание 3D моделей по вашим эскизам, чертежам или идеям.',
            'features' => ['Опытные дизайнеры', 'Любая сложность', 'Быстрые правки', 'Оптимизация для печати'],
            'price' => 'от 500₽/час',
            'active' => 1,
            'featured' => 0,
            'sort_order' => 4
        ],
        [
            'name' => '3D сканирование',
            'slug' => 'scanning',
            'icon' => 'fa-scanner',
            'description' => 'Создание точных цифровых копий физических объектов.',
            'features' => ['Точность до 0.05мм', 'Объекты любого размера', 'Обработка моделей', 'Быстрое выполнение'],
            'price' => 'от 1000₽',
            'active' => 1,
            'featured' => 0,
            'sort_order' => 5
        ],
        [
            'name' => 'Мелкосерийное производство',
            'slug' => 'production',
            'icon' => 'fa-industry',
            'description' => 'Изготовление партий деталей от 10 до 10000 штук.',
            'features' => ['Скидки на объем', 'Контроль качества', 'Быстрые сроки', 'Упаковка и доставка'],
            'price' => 'Индивидуально',
            'active' => 1,
            'featured' => 0,
            'sort_order' => 6
        ]
    ];
    
    $servicesCreated = 0;
    foreach ($services as $service) {
        try {
            $db->insertRecord('services', $service);
            $servicesCreated++;
        } catch (Exception $e) {
            // Skip if already exists
        }
    }
    
    // Initialize Testimonials
    $testimonials = [
        [
            'name' => 'Алексей Иванов',
            'position' => 'Директор, Tech Solutions',
            'avatar' => 'https://i.pravatar.cc/150?img=1',
            'text' => 'Отличное качество печати! Заказывали прототипы корпусов для нашего устройства. Все выполнено точно в срок, консультации на высшем уровне.',
            'rating' => 5,
            'approved' => 1,
            'active' => 1,
            'sort_order' => 1
        ],
        [
            'name' => 'Мария Петрова',
            'position' => 'Дизайнер',
            'avatar' => 'https://i.pravatar.cc/150?img=2',
            'text' => 'Работаю с этой компанией уже год. Печатают мои художественные проекты с невероятной детализацией. Рекомендую!',
            'rating' => 5,
            'approved' => 1,
            'active' => 1,
            'sort_order' => 2
        ],
        [
            'name' => 'Дмитрий Сидоров',
            'position' => 'Инженер-конструктор',
            'avatar' => 'https://i.pravatar.cc/150?img=3',
            'text' => 'Профессиональный подход к каждому заказу. Помогли с оптимизацией моделей, что сэкономило время и деньги.',
            'rating' => 5,
            'approved' => 1,
            'active' => 1,
            'sort_order' => 3
        ],
        [
            'name' => 'Елена Смирнова',
            'position' => 'Владелец бизнеса',
            'avatar' => 'https://i.pravatar.cc/150?img=4',
            'text' => 'Заказывала мелкую серию деталей - все изготовлено качественно, упаковано аккуратно. Очень довольна сотрудничеством!',
            'rating' => 5,
            'approved' => 1,
            'active' => 1,
            'sort_order' => 4
        ]
    ];
    
    $testimonialsCreated = 0;
    foreach ($testimonials as $testimonial) {
        try {
            $db->insertRecord('testimonials', $testimonial);
            $testimonialsCreated++;
        } catch (Exception $e) {
            // Skip if already exists
        }
    }
    
    // Initialize FAQ
    $faqs = [
        [
            'question' => 'Какие форматы файлов вы принимаете?',
            'answer' => 'Мы работаем с форматами STL, OBJ, 3MF, STEP. Если у вас файл в другом формате, свяжитесь с нами - мы найдем решение.',
            'active' => 1,
            'sort_order' => 1
        ],
        [
            'question' => 'Сколько времени занимает изготовление?',
            'answer' => 'Стандартный срок - 3-5 рабочих дней. Для небольших деталей возможна печать за 1 день. Есть услуга срочного изготовления (24 часа).',
            'active' => 1,
            'sort_order' => 2
        ],
        [
            'question' => 'Какая минимальная толщина стенок?',
            'answer' => 'Для FDM печати минимальная толщина - 1мм, для SLA/SLS - 0.5мм. Рекомендуем консультироваться перед печатью тонкостенных деталей.',
            'active' => 1,
            'sort_order' => 3
        ],
        [
            'question' => 'Можно ли заказать постобработку?',
            'answer' => 'Да, мы предлагаем шлифовку, покраску, химическую обработку, сборку. Все услуги можно выбрать в калькуляторе.',
            'active' => 1,
            'sort_order' => 4
        ],
        [
            'question' => 'Есть ли скидки на большие объемы?',
            'answer' => 'Да! При заказе от 10 деталей скидка 10%, от 50 деталей - 15%, от 100 деталей - индивидуальные условия.',
            'active' => 1,
            'sort_order' => 5
        ],
        [
            'question' => 'Как происходит оплата?',
            'answer' => 'Принимаем оплату по безналичному расчету, банковским картам, электронным кошелькам. Для юр.лиц работаем по договору с отсрочкой.',
            'active' => 1,
            'sort_order' => 6
        ]
    ];
    
    $faqsCreated = 0;
    foreach ($faqs as $faq) {
        try {
            $db->insertRecord('faq', $faq);
            $faqsCreated++;
        } catch (Exception $e) {
            // Skip if already exists
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database initialized successfully',
        'created' => [
            'services' => $servicesCreated,
            'testimonials' => $testimonialsCreated,
            'faq' => $faqsCreated
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$db->close();
