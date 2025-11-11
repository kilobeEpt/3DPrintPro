<?php
// ========================================
// Database Initialization Script
// Fills empty tables with default data
// ========================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$response = [
    'status' => 'OK',
    'actions' => []
];

try {
    $db = new Database();
    $pdo = $db->getPDO();
    
    // ========================================
    // PORTFOLIO
    // ========================================
    $portfolio_count = $db->getCount('portfolio');
    
    if ($portfolio_count == 0) {
        $portfolio_items = [
            [
                'title' => 'Визуализация архитектурного проекта',
                'description' => 'Профессиональная 3D визуализация архитектурного комплекса с использованием современных материалов и текстур',
                'category' => 'architecture',
                'sort_order' => 1
            ],
            [
                'title' => 'Прототип изделия из пластика',
                'description' => 'Быстрое прототипирование изделия с помощью FDM печати. Позволило заказчику оценить эргономику и внести коррективы',
                'category' => 'prototyping',
                'sort_order' => 2
            ],
            [
                'title' => 'Детальная статуэтка',
                'description' => 'Высокодетальная фигурка, напечатанная на SLA принтере с последующей постобработкой и раскраской',
                'category' => 'decorative',
                'sort_order' => 3
            ],
            [
                'title' => 'Промышленная деталь',
                'description' => 'Сложная техническая деталь для производственного оборудования. Печать выполнена из прочного полимера',
                'category' => 'industrial',
                'sort_order' => 4
            ]
        ];
        
        foreach ($portfolio_items as $item) {
            $db->insertRecord('portfolio', [
                'title' => $item['title'],
                'description' => $item['description'],
                'category' => $item['category'],
                'sort_order' => $item['sort_order'],
                'active' => 1
            ]);
        }
        
        $response['actions'][] = 'Portfolio заполнен 4 проектами';
    } else {
        $response['actions'][] = 'Portfolio уже содержит данные (' . $portfolio_count . ' записей)';
    }
    
    // ========================================
    // CONTENT BLOCKS
    // ========================================
    $content_count = $db->getCount('content_blocks');
    
    if ($content_count == 0) {
        $content_blocks = [
            [
                'block_name' => 'home_hero',
                'title' => 'Профессиональная 3D печать в Омске',
                'content' => 'Высококачественные услуги 3D печати с использованием современного оборудования',
                'page' => 'index',
                'sort_order' => 1
            ],
            [
                'block_name' => 'home_features',
                'title' => 'Наши преимущества',
                'content' => 'Быстрая доставка, качество работ, профессиональный подход',
                'page' => 'index',
                'sort_order' => 2
            ],
            [
                'block_name' => 'about_intro',
                'title' => 'О нас',
                'content' => 'Компания 3D PrintPro специализируется на высокоточной 3D печати',
                'page' => 'about',
                'sort_order' => 1
            ]
        ];
        
        foreach ($content_blocks as $block) {
            $db->insertRecord('content_blocks', [
                'block_name' => $block['block_name'],
                'title' => $block['title'],
                'content' => $block['content'],
                'page' => $block['page'],
                'sort_order' => $block['sort_order'],
                'active' => 1
            ]);
        }
        
        $response['actions'][] = 'Content blocks заполнены 3 блоками';
    } else {
        $response['actions'][] = 'Content blocks уже содержат данные (' . $content_count . ' записей)';
    }
    
    // ========================================
    // SETTINGS
    // ========================================
    $required_settings = [
        'site_name' => '3D PrintPro',
        'site_description' => 'Профессиональные услуги 3D печати в Омске',
        'company_name' => '3D PrintPro',
        'company_address' => 'Омск',
        'company_phone' => '+7 (383) 000-00-00',
        'company_email' => 'info@3dprintpro.ru',
        'company_hours' => 'Пн-Пт: 10:00-18:00, Сб-Вс: 10:00-16:00',
        'telegram_token' => '',
        'telegram_chat_id' => ''
    ];
    
    $settings_updated = 0;
    foreach ($required_settings as $key => $value) {
        $existing = $db->getSetting($key);
        
        if ($existing === null) {
            $db->saveSetting($key, $value);
            $settings_updated++;
        }
    }
    
    if ($settings_updated > 0) {
        $response['actions'][] = "Settings добавлено $settings_updated новых ключей";
    } else {
        $response['actions'][] = 'Settings уже содержат все необходимые ключи';
    }
    
    // ========================================
    // ACTIVATE ALL RECORDS
    // ========================================
    $tables_to_activate = ['services', 'portfolio', 'testimonials', 'faq', 'content_blocks'];
    $activated_count = 0;
    
    foreach ($tables_to_activate as $table) {
        $stmt = $pdo->prepare("UPDATE `$table` SET active = 1 WHERE active = 0 OR active IS NULL");
        $stmt->execute();
        $activated_count += $stmt->rowCount();
    }
    
    if ($activated_count > 0) {
        $response['actions'][] = "Активировано $activated_count записей";
    } else {
        $response['actions'][] = 'Все записи уже активны';
    }
    
    $response['summary'] = 'БД инициализирована успешно ✅';
    
    $db->close();
    
} catch (Exception $e) {
    http_response_code(500);
    $response['status'] = 'Error';
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
