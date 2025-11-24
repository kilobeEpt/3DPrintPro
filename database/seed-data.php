<?php
// ========================================
// Centralized Seed Data
// Default content for database initialization
// ========================================

return [
    // ========================================
    // SERVICES - Default service offerings
    // ========================================
    'services' => [
        [
            'name' => 'FDM печать',
            'slug' => 'fdm-printing',
            'icon' => 'fa-cube',
            'description' => 'Технология послойного наплавления пластика. Идеально подходит для прототипирования и функциональных изделий.',
            'features' => ['Быстрое изготовление', 'Доступная цена', 'Различные пластики', 'Крупные модели'],
            'price' => 'от 50₽/г',
            'category' => 'printing',
            'sort_order' => 1,
            'active' => 1,
            'featured' => 1
        ],
        [
            'name' => 'SLA печать',
            'slug' => 'sla-printing',
            'icon' => 'fa-diamond',
            'description' => 'Стереолитография - высокая детализация и гладкая поверхность. Для ювелирных изделий и сложных моделей.',
            'features' => ['Высокая детализация', 'Гладкая поверхность', 'Точность до 0.025 мм', 'Сложные формы'],
            'price' => 'от 100₽/г',
            'category' => 'printing',
            'sort_order' => 2,
            'active' => 1,
            'featured' => 1
        ],
        [
            'name' => '3D моделирование',
            'slug' => '3d-modeling',
            'icon' => 'fa-drafting-compass',
            'description' => 'Создание 3D моделей по чертежам, эскизам или фотографиям. Подготовка файлов для печати.',
            'features' => ['Моделирование по ТЗ', 'Оптимизация моделей', 'Подготовка к печати', 'Визуализация'],
            'price' => 'от 500₽/час',
            'category' => 'design',
            'sort_order' => 3,
            'active' => 1,
            'featured' => 0
        ],
        [
            'name' => 'Прототипирование',
            'slug' => 'prototyping',
            'icon' => 'fa-flask',
            'description' => 'Быстрое изготовление прототипов для тестирования конструкции и функциональности.',
            'features' => ['Быстрые итерации', 'Функциональные тесты', 'Оценка эргономики', 'Презентация инвесторам'],
            'price' => 'от 1000₽',
            'category' => 'engineering',
            'sort_order' => 4,
            'active' => 1,
            'featured' => 0
        ],
        [
            'name' => 'Постобработка',
            'slug' => 'post-processing',
            'icon' => 'fa-paint-brush',
            'description' => 'Шлифовка, грунтовка, покраска и другие виды обработки готовых изделий.',
            'features' => ['Шлифовка', 'Покраска', 'Лакировка', 'Сборка деталей'],
            'price' => 'от 300₽',
            'category' => 'finishing',
            'sort_order' => 5,
            'active' => 1,
            'featured' => 0
        ],
        [
            'name' => 'Консультация',
            'slug' => 'consultation',
            'icon' => 'fa-comments',
            'description' => 'Профессиональная консультация по выбору технологии, материалов и оптимизации проекта.',
            'features' => ['Выбор технологии', 'Подбор материала', 'Оценка стоимости', 'Рекомендации по дизайну'],
            'price' => 'Бесплатно',
            'category' => 'support',
            'sort_order' => 6,
            'active' => 1,
            'featured' => 0
        ]
    ],

    // ========================================
    // PORTFOLIO - Example projects
    // ========================================
    'portfolio' => [
        [
            'title' => 'Визуализация архитектурного проекта',
            'description' => 'Профессиональная 3D визуализация архитектурного комплекса с использованием современных материалов и текстур',
            'image_url' => null,
            'category' => 'architecture',
            'tags' => ['архитектура', 'визуализация', 'моделирование'],
            'sort_order' => 1,
            'active' => 1
        ],
        [
            'title' => 'Прототип изделия из пластика',
            'description' => 'Быстрое прототипирование изделия с помощью FDM печати. Позволило заказчику оценить эргономику и внести коррективы',
            'image_url' => null,
            'category' => 'prototyping',
            'tags' => ['прототип', 'FDM', 'функциональная модель'],
            'sort_order' => 2,
            'active' => 1
        ],
        [
            'title' => 'Детальная статуэтка',
            'description' => 'Высокодетальная фигурка, напечатанная на SLA принтере с последующей постобработкой и раскраской',
            'image_url' => null,
            'category' => 'decorative',
            'tags' => ['SLA', 'постобработка', 'покраска'],
            'sort_order' => 3,
            'active' => 1
        ],
        [
            'title' => 'Промышленная деталь',
            'description' => 'Сложная техническая деталь для производственного оборудования. Печать выполнена из прочного полимера',
            'image_url' => null,
            'category' => 'industrial',
            'tags' => ['промышленность', 'техническая деталь', 'прочный пластик'],
            'sort_order' => 4,
            'active' => 1
        ]
    ],

    // ========================================
    // TESTIMONIALS - Customer reviews
    // ========================================
    'testimonials' => [
        [
            'name' => 'Алексей Петров',
            'position' => 'Директор ООО "ТехноПром"',
            'avatar' => null,
            'text' => 'Отличное качество печати и быстрые сроки. Заказывали прототипы деталей для производства - всё сделано точно в срок.',
            'rating' => 5,
            'sort_order' => 1,
            'approved' => 1,
            'active' => 1
        ],
        [
            'name' => 'Мария Соколова',
            'position' => 'Архитектор',
            'avatar' => null,
            'text' => 'Профессиональный подход и внимание к деталям. Помогли с 3D моделированием и визуализацией проекта. Рекомендую!',
            'rating' => 5,
            'sort_order' => 2,
            'approved' => 1,
            'active' => 1
        ],
        [
            'name' => 'Игорь Васильев',
            'position' => 'Предприниматель',
            'avatar' => null,
            'text' => 'Сделали прототип нового изделия за 3 дня. Качество превзошло ожидания. Продолжим сотрудничество!',
            'rating' => 5,
            'sort_order' => 3,
            'approved' => 1,
            'active' => 1
        ],
        [
            'name' => 'Елена Кузнецова',
            'position' => 'Дизайнер',
            'avatar' => null,
            'text' => 'Заказывала декоративные элементы для проекта. SLA печать - просто волшебство! Детализация потрясающая.',
            'rating' => 5,
            'sort_order' => 4,
            'approved' => 1,
            'active' => 1
        ]
    ],

    // ========================================
    // FAQ - Frequently Asked Questions
    // ========================================
    'faq' => [
        [
            'question' => 'Какие материалы вы используете для печати?',
            'answer' => 'Мы работаем с широким спектром материалов: PLA, ABS, PETG, TPU, Nylon для FDM печати; фотополимерные смолы различных типов для SLA печати. Поможем подобрать оптимальный материал для вашего проекта.',
            'sort_order' => 1,
            'active' => 1
        ],
        [
            'question' => 'Каковы сроки изготовления?',
            'answer' => 'Сроки зависят от сложности и размера модели. Простые изделия - 1-2 дня, сложные проекты - до недели. Возможна срочная печать за дополнительную плату.',
            'sort_order' => 2,
            'active' => 1
        ],
        [
            'question' => 'Какой максимальный размер изделия можно напечатать?',
            'answer' => 'FDM: до 300×300×400 мм, SLA: до 192×120×200 мм. Крупные модели можем печатать по частям с последующей склейкой.',
            'sort_order' => 3,
            'active' => 1
        ],
        [
            'question' => 'У меня нет 3D модели. Можете создать?',
            'answer' => 'Да, оказываем услуги 3D моделирования. Можем создать модель по чертежам, эскизам, фотографиям или устному описанию.',
            'sort_order' => 4,
            'active' => 1
        ],
        [
            'question' => 'Как рассчитывается стоимость?',
            'answer' => 'Стоимость зависит от веса изделия, типа материала, технологии печати и сложности постобработки. Используйте калькулятор на сайте для предварительного расчёта.',
            'sort_order' => 5,
            'active' => 1
        ],
        [
            'question' => 'Предоставляете ли вы гарантию?',
            'answer' => 'Да, гарантируем качество печати. Если изделие не соответствует согласованным параметрам - переделаем бесплатно.',
            'sort_order' => 6,
            'active' => 1
        ],
        [
            'question' => 'Можно ли заказать постобработку?',
            'answer' => 'Да, предлагаем шлифовку, грунтовку, покраску, лакировку изделий. Цена рассчитывается индивидуально.',
            'sort_order' => 7,
            'active' => 1
        ],
        [
            'question' => 'Работаете с юридическими лицами?',
            'answer' => 'Да, работаем как с физическими, так и с юридическими лицами. Предоставляем все необходимые документы.',
            'sort_order' => 8,
            'active' => 1
        ]
    ],

    // ========================================
    // CONTENT BLOCKS - Text content for pages
    // ========================================
    'content_blocks' => [
        [
            'block_name' => 'home_hero',
            'title' => 'Профессиональная 3D печать в Омске',
            'content' => 'Высококачественные услуги 3D печати с использованием современного оборудования. FDM и SLA технологии. Быстрое изготовление прототипов и готовых изделий.',
            'data' => null,
            'page' => 'index',
            'sort_order' => 1,
            'active' => 1
        ],
        [
            'block_name' => 'home_features',
            'title' => 'Наши преимущества',
            'content' => 'Профессиональное оборудование • Опытные специалисты • Быстрые сроки • Доступные цены • Качественная постобработка',
            'data' => [
                'features' => [
                    ['icon' => 'fa-clock', 'title' => 'Быстрое изготовление', 'text' => 'От 1 дня'],
                    ['icon' => 'fa-dollar-sign', 'title' => 'Доступные цены', 'text' => 'От 50₽/г'],
                    ['icon' => 'fa-certificate', 'title' => 'Гарантия качества', 'text' => '100%'],
                    ['icon' => 'fa-headset', 'title' => 'Поддержка 24/7', 'text' => 'Всегда на связи']
                ]
            ],
            'page' => 'index',
            'sort_order' => 2,
            'active' => 1
        ],
        [
            'block_name' => 'about_intro',
            'title' => 'О нас',
            'content' => 'Компания 3D PrintPro специализируется на высокоточной 3D печати и моделировании. Мы используем современное оборудование и профессиональные материалы для создания качественных изделий любой сложности.',
            'data' => null,
            'page' => 'about',
            'sort_order' => 1,
            'active' => 1
        ]
    ],

    // ========================================
    // SETTINGS - Configuration keys with default values
    // ========================================
    'settings' => [
        'site_name' => '3D PrintPro',
        'site_description' => 'Профессиональные услуги 3D печати в Омске',
        'site_keywords' => '3D печать, Омск, FDM, SLA, прототипирование, 3D моделирование',
        'company_name' => '3D PrintPro',
        'company_address' => 'г. Омск',
        'company_phone' => '+7 (383) 000-00-00',
        'company_email' => 'info@3dprint-omsk.ru',
        'company_hours' => 'Пн-Пт: 10:00-18:00, Сб-Вс: 10:00-16:00',
        
        // Telegram Settings (managed via admin panel)
        'telegram_bot_token' => '',
        'telegram_chat_id' => '',
        'telegram_contact_url' => 'https://t.me/PrintPro_Omsk',
        'telegram_notify_new_order' => '1',
        'telegram_notify_status_change' => '1',
        
        // Email Settings
        'admin_email' => 'info@3dprint-omsk.ru',
        'email_notifications_enabled' => '0',
        
        // Calculator Settings
        'calculator_base_price' => '50',
        'calculator_currency' => '₽',
        'calculator_weight_unit' => 'г',
        
        // Legacy settings (deprecated, kept for backward compatibility)
        'telegram_token' => '',
    ],

    // ========================================
    // FORMS - Dynamic form definitions
    // ========================================
    'forms' => [
        [
            'name' => 'Контактная форма',
            'slug' => 'contact',
            'description' => 'Основная контактная форма для обращений клиентов',
            'settings' => [
                'enable_telegram_notification' => true,
                'enable_email_notification' => false,
                'rate_limit' => '10/hour',
            ],
            'notification_email' => 'info@3dprint-omsk.ru',
            'success_message' => 'Спасибо за обращение! Мы свяжемся с вами в ближайшее время.',
            'redirect_url' => null,
            'sort_order' => 1,
            'active' => 1
        ],
        [
            'name' => 'Форма заказа',
            'slug' => 'order',
            'description' => 'Форма для оформления заказа на 3D печать',
            'settings' => [
                'enable_telegram_notification' => true,
                'enable_email_notification' => false,
                'require_calculator_data' => true,
            ],
            'notification_email' => 'info@3dprint-omsk.ru',
            'success_message' => 'Заказ принят! Номер вашего заказа: {order_number}. Мы свяжемся с вами для уточнения деталей.',
            'redirect_url' => null,
            'sort_order' => 2,
            'active' => 1
        ]
    ],

    // ========================================
    // FORM FIELDS - Field definitions for forms
    // Note: form_id will be resolved at runtime based on form slug
    // ========================================
    'form_fields' => [
        // Contact Form Fields
        [
            'form_slug' => 'contact',
            'name' => 'name',
            'label' => 'Имя',
            'type' => 'text',
            'placeholder' => 'Введите ваше имя',
            'default_value' => null,
            'validation_rules' => [
                'required' => true,
                'minLength' => 2,
                'maxLength' => 255,
            ],
            'options' => null,
            'help_text' => null,
            'sort_order' => 1,
            'required' => 1,
            'active' => 1
        ],
        [
            'form_slug' => 'contact',
            'name' => 'phone',
            'label' => 'Телефон',
            'type' => 'phone',
            'placeholder' => '+7 (___) ___-__-__',
            'default_value' => null,
            'validation_rules' => [
                'required' => true,
                'pattern' => '^\\+?[0-9\\s\\-\\(\\)]{10,20}
,
            ],
            'options' => null,
            'help_text' => 'Укажите номер телефона для связи',
            'sort_order' => 2,
            'required' => 1,
            'active' => 1
        ],
        [
            'form_slug' => 'contact',
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'placeholder' => 'your@email.com',
            'default_value' => null,
            'validation_rules' => [
                'required' => false,
                'email' => true,
            ],
            'options' => null,
            'help_text' => 'Необязательно',
            'sort_order' => 3,
            'required' => 0,
            'active' => 1
        ],
        [
            'form_slug' => 'contact',
            'name' => 'telegram',
            'label' => 'Telegram',
            'type' => 'text',
            'placeholder' => '@username',
            'default_value' => null,
            'validation_rules' => [
                'required' => false,
                'pattern' => '^@?[a-zA-Z0-9_]{5,32}
,
            ],
            'options' => null,
            'help_text' => 'Необязательно, для быстрой связи',
            'sort_order' => 4,
            'required' => 0,
            'active' => 1
        ],
        [
            'form_slug' => 'contact',
            'name' => 'subject',
            'label' => 'Тема обращения',
            'type' => 'text',
            'placeholder' => 'О чём вы хотите узнать?',
            'default_value' => null,
            'validation_rules' => [
                'required' => false,
                'maxLength' => 255,
            ],
            'options' => null,
            'help_text' => null,
            'sort_order' => 5,
            'required' => 0,
            'active' => 1
        ],
        [
            'form_slug' => 'contact',
            'name' => 'message',
            'label' => 'Сообщение',
            'type' => 'textarea',
            'placeholder' => 'Опишите ваш вопрос или задачу...',
            'default_value' => null,
            'validation_rules' => [
                'required' => true,
                'minLength' => 10,
                'maxLength' => 5000,
            ],
            'options' => null,
            'help_text' => 'Минимум 10 символов',
            'sort_order' => 6,
            'required' => 1,
            'active' => 1
        ],
        
        // Order Form Fields (inherits contact fields + adds service and calculator data)
        [
            'form_slug' => 'order',
            'name' => 'name',
            'label' => 'Имя',
            'type' => 'text',
            'placeholder' => 'Введите ваше имя',
            'default_value' => null,
            'validation_rules' => [
                'required' => true,
                'minLength' => 2,
                'maxLength' => 255,
            ],
            'options' => null,
            'help_text' => null,
            'sort_order' => 1,
            'required' => 1,
            'active' => 1
        ],
        [
            'form_slug' => 'order',
            'name' => 'phone',
            'label' => 'Телефон',
            'type' => 'phone',
            'placeholder' => '+7 (___) ___-__-__',
            'default_value' => null,
            'validation_rules' => [
                'required' => true,
                'pattern' => '^\\+?[0-9\\s\\-\\(\\)]{10,20}
,
            ],
            'options' => null,
            'help_text' => 'Укажите номер телефона для связи',
            'sort_order' => 2,
            'required' => 1,
            'active' => 1
        ],
        [
            'form_slug' => 'order',
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'placeholder' => 'your@email.com',
            'default_value' => null,
            'validation_rules' => [
                'required' => false,
                'email' => true,
            ],
            'options' => null,
            'help_text' => 'Необязательно',
            'sort_order' => 3,
            'required' => 0,
            'active' => 1
        ],
        [
            'form_slug' => 'order',
            'name' => 'telegram',
            'label' => 'Telegram',
            'type' => 'text',
            'placeholder' => '@username',
            'default_value' => null,
            'validation_rules' => [
                'required' => false,
                'pattern' => '^@?[a-zA-Z0-9_]{5,32}
,
            ],
            'options' => null,
            'help_text' => 'Необязательно, для быстрой связи',
            'sort_order' => 4,
            'required' => 0,
            'active' => 1
        ],
        [
            'form_slug' => 'order',
            'name' => 'service',
            'label' => 'Услуга',
            'type' => 'text',
            'placeholder' => 'Тип услуги',
            'default_value' => null,
            'validation_rules' => [
                'required' => true,
                'maxLength' => 255,
            ],
            'options' => null,
            'help_text' => null,
            'sort_order' => 5,
            'required' => 1,
            'active' => 1
        ],
        [
            'form_slug' => 'order',
            'name' => 'message',
            'label' => 'Комментарий к заказу',
            'type' => 'textarea',
            'placeholder' => 'Дополнительная информация...',
            'default_value' => null,
            'validation_rules' => [
                'required' => false,
                'maxLength' => 5000,
            ],
            'options' => null,
            'help_text' => null,
            'sort_order' => 6,
            'required' => 0,
            'active' => 1
        ],
    ]
];
