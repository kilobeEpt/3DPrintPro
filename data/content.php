<?php
/**
 * Static Content Data
 * 
 * All site content is defined here as PHP arrays for easy management
 * and embedding directly into templates without database queries.
 */

return [
    // Site Information
    'site' => [
        'name' => '3D Print Pro',
        'tagline' => 'Профессиональная 3D печать в Омске',
        'description' => 'Профессиональная 3D печать в Омске: FDM, SLA, SLS технологии. 3D моделирование, постобработка, быстрое изготовление. Опыт 12 лет.',
        'founded_year' => 2011,
        'url' => 'https://3dprint-omsk.ru',
    ],
    
    // Contact Information
    'contact' => [
        'phone' => '+7 (999) 123-45-67',
        'email' => 'info@3dprint-omsk.ru',
        'telegram' => 'https://t.me/PrintPro_Omsk',
        'telegram_bot' => '@PrintPro_Omsk',
        'address' => [
            'street' => 'ул. Ленина, д. 15',
            'city' => 'Омск',
            'region' => 'Омская область',
            'postal_code' => '644000',
            'country' => 'RU',
        ],
        'geo' => [
            'latitude' => 54.9885,
            'longitude' => 73.3242,
        ],
        'working_hours' => [
            'weekdays' => 'Пн-Пт: 09:00 - 18:00',
            'weekend' => 'Сб-Вс: выходной',
            'structured' => [
                [
                    'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'opens' => '09:00',
                    'closes' => '18:00',
                ],
            ],
        ],
    ],
    
    // Statistics
    'stats' => [
        [
            'icon' => 'fas fa-project-diagram',
            'number' => 1500,
            'label' => 'Проектов выполнено',
        ],
        [
            'icon' => 'fas fa-users',
            'number' => 850,
            'label' => 'Довольных клиентов',
        ],
        [
            'icon' => 'fas fa-clock',
            'number' => 12,
            'label' => 'Лет опыта',
        ],
        [
            'icon' => 'fas fa-award',
            'number' => 25,
            'label' => 'Наград получено',
        ],
    ],
    
    // Services
    'services' => [
        [
            'id' => 'fdm',
            'name' => 'FDM печать',
            'slug' => 'fdm-pechat',
            'icon' => 'fas fa-print',
            'short_description' => 'Послойное наплавление пластика для прототипов и функциональных деталей',
            'description' => 'FDM (Fused Deposition Modeling) — самая популярная технология 3D печати. Идеально подходит для создания прототипов, функциональных изделий и крупногабаритных деталей.',
            'features' => [
                'Быстрое изготовление (от 1 часа)',
                'Большой выбор материалов (PLA, ABS, PETG, TPU)',
                'Высокая прочность изделий',
                'Печать крупных деталей (до 300x300x400 мм)',
            ],
            'materials' => ['PLA', 'ABS', 'PETG', 'TPU', 'Nylon'],
            'applications' => [
                'Прототипирование',
                'Функциональные детали',
                'Корпуса и кожухи',
                'Макеты и модели',
            ],
            'price_from' => 50,
            'price_unit' => '₽/час печати',
            'delivery_time' => 'от 1 часа',
        ],
        [
            'id' => 'sla',
            'name' => 'SLA печать',
            'slug' => 'sla-pechat',
            'icon' => 'fas fa-flask',
            'short_description' => 'Фотополимерная печать с высокой детализацией и гладкой поверхностью',
            'description' => 'SLA (Stereolithography) — технология фотополимерной 3D печати. Обеспечивает максимальную детализацию и гладкую поверхность. Идеально для ювелирных изделий, стоматологии и мелких деталей.',
            'features' => [
                'Высокая детализация (до 25 микрон)',
                'Гладкая поверхность без слоев',
                'Точность изготовления',
                'Сложные геометрические формы',
            ],
            'materials' => ['Standard Resin', 'Tough Resin', 'Flexible Resin', 'Castable Resin'],
            'applications' => [
                'Ювелирные изделия',
                'Стоматологические модели',
                'Миниатюры и фигурки',
                'Прототипы с высокой детализацией',
            ],
            'price_from' => 150,
            'price_unit' => '₽/час печати',
            'delivery_time' => 'от 3 часов',
        ],
        [
            'id' => 'sls',
            'name' => 'SLS печать',
            'slug' => 'sls-pechat',
            'icon' => 'fas fa-radiation',
            'short_description' => 'Лазерное спекание для прочных термостойких изделий без поддержек',
            'description' => 'SLS (Selective Laser Sintering) — технология лазерного спекания порошковых материалов. Обеспечивает максимальную прочность и термостойкость. Не требует поддержек.',
            'features' => [
                'Высокая прочность изделий',
                'Термостойкость до 180°C',
                'Не требует поддержек',
                'Сложные сборки в одной печати',
            ],
            'materials' => ['PA12 (Nylon)', 'PA11', 'TPU', 'Alumide'],
            'applications' => [
                'Функциональные детали',
                'Инженерные прототипы',
                'Серийное производство',
                'Термостойкие изделия',
            ],
            'price_from' => 300,
            'price_unit' => '₽/час печати',
            'delivery_time' => 'от 1 дня',
        ],
        [
            'id' => 'color-print',
            'name' => 'Цветная печать',
            'slug' => 'tsvetnaya-pechat',
            'icon' => 'fas fa-palette',
            'short_description' => 'Многоцветная печать для ярких и реалистичных моделей',
            'description' => 'Полноцветная 3D печать с использованием многоцветных материалов или окрашивания в процессе печати. Создавайте яркие и реалистичные модели.',
            'features' => [
                'До 16 миллионов цветов',
                'Реалистичные текстуры',
                'Градиенты и переходы',
                'Полноцветные прототипы',
            ],
            'materials' => ['Multi-color PLA', 'Full-color sandstone'],
            'applications' => [
                'Архитектурные макеты',
                'Дизайнерские изделия',
                'Подарки и сувениры',
                'Презентационные модели',
            ],
            'price_from' => 200,
            'price_unit' => '₽/час печати',
            'delivery_time' => 'от 2 часов',
        ],
        [
            'id' => '3d-modeling',
            'name' => '3D моделирование',
            'slug' => '3d-modelirovanie',
            'icon' => 'fas fa-cube',
            'short_description' => 'Создание 3D моделей по вашим эскизам, чертежам или идеям',
            'description' => 'Профессиональное 3D моделирование для любых задач. Создадим модель по вашим эскизам, чертежам, фотографиям или устному описанию.',
            'features' => [
                'CAD моделирование',
                'Reverse engineering',
                'Оптимизация для печати',
                'Подготовка файлов',
            ],
            'materials' => null,
            'applications' => [
                'Создание моделей с нуля',
                'Доработка существующих моделей',
                '3D сканирование и обработка',
                'Инженерное проектирование',
            ],
            'price_from' => 1500,
            'price_unit' => '₽/час работы',
            'delivery_time' => 'от 1 дня',
        ],
        [
            'id' => 'post-processing',
            'name' => 'Постобработка',
            'slug' => 'postobrabotka',
            'icon' => 'fas fa-paint-brush',
            'short_description' => 'Шлифовка, покраска, химическая обработка и сборка изделий',
            'description' => 'Полный комплекс услуг по постобработке 3D печатных изделий. Придадим вашим моделям финальный профессиональный вид.',
            'features' => [
                'Шлифовка и полировка',
                'Покраска и декорирование',
                'Химическая обработка',
                'Склейка и сборка',
            ],
            'materials' => null,
            'applications' => [
                'Улучшение внешнего вида',
                'Повышение прочности',
                'Придание гладкости',
                'Финальная доработка',
            ],
            'price_from' => 500,
            'price_unit' => '₽/изделие',
            'delivery_time' => 'от 1 дня',
        ],
    ],
    
    // Portfolio Items
    'portfolio' => [
        [
            'id' => 1,
            'title' => 'Архитектурный макет жилого комплекса',
            'slug' => 'arhitekturnyy-maket',
            'category' => 'architecture',
            'technology' => 'FDM + SLA',
            'description' => 'Детализированный макет жилого комплекса в масштабе 1:200 с проработкой фасадов, ландшафта и инфраструктуры.',
            'image' => '/images/portfolio/architecture-1.jpg',
            'duration' => '5 дней',
            'materials' => ['PLA', 'Transparent Resin'],
            'client' => 'Строительная компания "ОмскСтрой"',
            'year' => 2024,
            'tags' => ['архитектура', 'макет', 'недвижимость'],
        ],
        [
            'id' => 2,
            'title' => 'Функциональный прототип медицинского прибора',
            'slug' => 'meditsinskiy-prototip',
            'category' => 'medical',
            'technology' => 'SLA',
            'description' => 'Прототип медицинского диагностического устройства с высокой точностью изготовления для клинических испытаний.',
            'image' => '/images/portfolio/medical-1.jpg',
            'duration' => '3 дня',
            'materials' => ['Biocompatible Resin'],
            'client' => 'МедТех Инновации',
            'year' => 2024,
            'tags' => ['медицина', 'прототип', 'инновации'],
        ],
        [
            'id' => 3,
            'title' => 'Ювелирные изделия и восковки',
            'slug' => 'yuvelirnye-izdeliya',
            'category' => 'jewelry',
            'technology' => 'SLA',
            'description' => 'Серия восковок для ювелирного литья: кольца, подвески, серьги с мельчайшими деталями.',
            'image' => '/images/portfolio/jewelry-1.jpg',
            'duration' => '2 дня',
            'materials' => ['Castable Resin'],
            'client' => 'Ювелирная мастерская "Золотой ключ"',
            'year' => 2023,
            'tags' => ['ювелирка', 'кастинг', 'детали'],
        ],
        [
            'id' => 4,
            'title' => 'Корпус для электронного устройства',
            'slug' => 'korpus-elektroniki',
            'category' => 'electronics',
            'technology' => 'FDM',
            'description' => 'Эргономичный корпус для IoT устройства с креплениями для плат, вентиляцией и разъемами.',
            'image' => '/images/portfolio/electronics-1.jpg',
            'duration' => '1 день',
            'materials' => ['ABS', 'TPU'],
            'client' => 'Tech Startup "SmartHome"',
            'year' => 2024,
            'tags' => ['электроника', 'корпус', 'iot'],
        ],
        [
            'id' => 5,
            'title' => 'Коллекционные миниатюры',
            'slug' => 'kollektsionnye-miniatyury',
            'category' => 'figurines',
            'technology' => 'SLA',
            'description' => 'Серия детализированных миниатюр для настольных игр с высокой проработкой деталей.',
            'image' => '/images/portfolio/figurines-1.jpg',
            'duration' => '4 дня',
            'materials' => ['Grey Resin'],
            'client' => 'Клуб настольных игр "Dice & Glory"',
            'year' => 2023,
            'tags' => ['миниатюры', 'игры', 'детали'],
        ],
        [
            'id' => 6,
            'title' => 'Запчасти для промышленного оборудования',
            'slug' => 'promyshlennye-zapchasti',
            'category' => 'industrial',
            'technology' => 'SLS',
            'description' => 'Изготовление редких запчастей для промышленного оборудования, снятого с производства.',
            'image' => '/images/portfolio/industrial-1.jpg',
            'duration' => '7 дней',
            'materials' => ['PA12'],
            'client' => 'ОмскНефтехим',
            'year' => 2024,
            'tags' => ['промышленность', 'запчасти', 'прочность'],
        ],
    ],
    
    // Testimonials
    'testimonials' => [
        [
            'id' => 1,
            'name' => 'Алексей Иванов',
            'position' => 'Директор ООО "ТехноПроект"',
            'avatar' => '/images/testimonials/avatar-1.jpg',
            'rating' => 5,
            'text' => 'Отличное качество печати и быстрое выполнение заказа! Печатали функциональные прототипы для нашего проекта. Все детали идеально подошли с первого раза.',
            'date' => '2024-11-15',
            'project' => 'Прототипирование механизма',
        ],
        [
            'id' => 2,
            'name' => 'Мария Петрова',
            'position' => 'Архитектор',
            'avatar' => '/images/testimonials/avatar-2.jpg',
            'rating' => 5,
            'text' => 'Заказывала макет жилого комплекса. Результат превзошел ожидания! Детализация на высоте, все фасады и ландшафт выполнены точно по проекту.',
            'date' => '2024-10-28',
            'project' => 'Архитектурный макет',
        ],
        [
            'id' => 3,
            'name' => 'Дмитрий Сидоров',
            'position' => 'Владелец ювелирной мастерской',
            'avatar' => '/images/testimonials/avatar-3.jpg',
            'rating' => 5,
            'text' => 'Печатаем восковки для ювелирных изделий уже полгода. Качество всегда стабильное, детализация отличная. Рекомендую!',
            'date' => '2024-09-10',
            'project' => 'Ювелирные восковки',
        ],
        [
            'id' => 4,
            'name' => 'Елена Смирнова',
            'position' => 'Инженер-конструктор',
            'avatar' => '/images/testimonials/avatar-4.jpg',
            'rating' => 4,
            'text' => 'Хороший сервис и квалифицированная консультация. Помогли подобрать материал и технологию для нашей задачи. Сроки соблюдены.',
            'date' => '2024-08-22',
            'project' => 'Промышленная деталь',
        ],
    ],
    
    // FAQ
    'faq' => [
        [
            'id' => 1,
            'question' => 'Какие форматы файлов вы принимаете?',
            'answer' => 'Мы работаем с форматами STL, OBJ, STEP, IGES, 3MF. Если у вас другой формат — свяжитесь с нами, мы поможем с конвертацией.',
            'category' => 'files',
        ],
        [
            'id' => 2,
            'question' => 'Сколько времени занимает печать?',
            'answer' => 'Время печати зависит от размера, сложности модели и выбранной технологии. Простые детали на FDM печатаются от 1 часа, сложные проекты на SLS могут занять несколько дней.',
            'category' => 'timing',
        ],
        [
            'id' => 3,
            'question' => 'Можно ли заказать 3D моделирование?',
            'answer' => 'Да, мы предоставляем услуги 3D моделирования. Создадим модель по вашим эскизам, чертежам, фотографиям или устному описанию.',
            'category' => 'services',
        ],
        [
            'id' => 4,
            'question' => 'Какие материалы вы используете?',
            'answer' => 'Мы работаем с широким спектром материалов: PLA, ABS, PETG, TPU, Nylon для FDM; различные фотополимерные смолы для SLA; PA12, PA11 для SLS. Поможем подобрать оптимальный материал для вашей задачи.',
            'category' => 'materials',
        ],
        [
            'id' => 5,
            'question' => 'Предоставляете ли постобработку?',
            'answer' => 'Да, мы предлагаем полный комплекс постобработки: шлифовку, полировку, покраску, химическую обработку, склейку и сборку изделий.',
            'category' => 'services',
        ],
        [
            'id' => 6,
            'question' => 'Какова минимальная стоимость заказа?',
            'answer' => 'Минимальная стоимость заказа зависит от технологии и материала. Обычно от 500₽ для простых FDM деталей. Используйте наш онлайн калькулятор для предварительной оценки.',
            'category' => 'pricing',
        ],
        [
            'id' => 7,
            'question' => 'Как происходит оплата и доставка?',
            'answer' => 'Оплата наличными или безналичным переводом. Доставка по Омску курьером или самовывоз из нашего офиса. Возможна отправка в другие города транспортными компаниями.',
            'category' => 'delivery',
        ],
        [
            'id' => 8,
            'question' => 'Даете ли гарантию на изделия?',
            'answer' => 'Мы гарантируем качество печати согласно выбранной технологии. Если деталь не соответствует техническим требованиям — перепечатаем бесплатно.',
            'category' => 'warranty',
        ],
    ],
    
    // Calculator Configuration
    'calculator' => [
        'technologies' => [
            'fdm' => 'FDM (послойное наплавление)',
            'sla' => 'SLA (фотополимерная)',
            'sls' => 'SLS (лазерное спекание)',
        ],
        
        'materials' => [
            // FDM Materials
            'pla' => [
                'name' => 'PLA (полилактид)',
                'price' => 3.5,
                'technology' => 'fdm',
            ],
            'abs' => [
                'name' => 'ABS (прочный пластик)',
                'price' => 4.0,
                'technology' => 'fdm',
            ],
            'petg' => [
                'name' => 'PETG (ударопрочный)',
                'price' => 4.5,
                'technology' => 'fdm',
            ],
            'tpu' => [
                'name' => 'TPU (гибкий)',
                'price' => 6.0,
                'technology' => 'fdm',
            ],
            'nylon' => [
                'name' => 'Nylon (прочный)',
                'price' => 7.0,
                'technology' => 'fdm',
            ],
            
            // SLA Materials
            'standard_resin' => [
                'name' => 'Standard Resin',
                'price' => 8.0,
                'technology' => 'sla',
            ],
            'tough_resin' => [
                'name' => 'Tough Resin (прочная)',
                'price' => 10.0,
                'technology' => 'sla',
            ],
            'flexible_resin' => [
                'name' => 'Flexible Resin (гибкая)',
                'price' => 12.0,
                'technology' => 'sla',
            ],
            'castable_resin' => [
                'name' => 'Castable Resin (для литья)',
                'price' => 15.0,
                'technology' => 'sla',
            ],
            
            // SLS Materials
            'pa12' => [
                'name' => 'PA12 (Nylon)',
                'price' => 12.0,
                'technology' => 'sls',
            ],
            'pa11' => [
                'name' => 'PA11 (био-нейлон)',
                'price' => 15.0,
                'technology' => 'sls',
            ],
            'tpu_sls' => [
                'name' => 'TPU (гибкий)',
                'price' => 18.0,
                'technology' => 'sls',
            ],
        ],
        
        'quality' => [
            'draft' => [
                'name' => 'Черновое качество',
                'multiplier' => 0.7,
                'time' => 0.8,
            ],
            'normal' => [
                'name' => 'Стандартное качество',
                'multiplier' => 1.0,
                'time' => 1.0,
            ],
            'high' => [
                'name' => 'Высокое качество',
                'multiplier' => 1.5,
                'time' => 1.5,
            ],
        ],
        
        'services' => [
            'modeling' => [
                'name' => '3D моделирование',
                'price' => 1500,
                'unit' => 'за модель',
            ],
            'postProcessing' => [
                'name' => 'Постобработка',
                'price' => 500,
                'unit' => 'за изделие',
            ],
            'painting' => [
                'name' => 'Покраска',
                'price' => 800,
                'unit' => 'за изделие',
            ],
            'express' => [
                'name' => 'Срочное выполнение',
                'price' => 1000,
                'unit' => 'за заказ',
            ],
        ],
        
        'discounts' => [
            [
                'minQuantity' => 10,
                'percent' => 5,
                'label' => '5% скидка',
            ],
            [
                'minQuantity' => 50,
                'percent' => 10,
                'label' => '10% скидка',
            ],
            [
                'minQuantity' => 100,
                'percent' => 15,
                'label' => '15% скидка',
            ],
        ],
    ],
];
