<?php

/**
 * CategoriesSeeder
 * 
 * Seeds categories for services, portfolio, and FAQ
 */
class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            // Service categories
            [
                'name' => 'Печать',
                'slug' => 'printing',
                'type' => 'service',
                'description' => '3D печать различными технологиями',
                'icon' => 'fa-cube',
                'sort_order' => 10,
                'active' => true
            ],
            [
                'name' => 'Дизайн',
                'slug' => 'design',
                'type' => 'service',
                'description' => '3D моделирование и дизайн',
                'icon' => 'fa-drafting-compass',
                'sort_order' => 20,
                'active' => true
            ],
            [
                'name' => 'Инжиниринг',
                'slug' => 'engineering',
                'type' => 'service',
                'description' => 'Инженерные услуги и прототипирование',
                'icon' => 'fa-flask',
                'sort_order' => 30,
                'active' => true
            ],
            [
                'name' => 'Постобработка',
                'slug' => 'finishing',
                'type' => 'service',
                'description' => 'Обработка и отделка готовых изделий',
                'icon' => 'fa-paint-brush',
                'sort_order' => 40,
                'active' => true
            ],
            [
                'name' => 'Поддержка',
                'slug' => 'support',
                'type' => 'service',
                'description' => 'Консультации и поддержка',
                'icon' => 'fa-comments',
                'sort_order' => 50,
                'active' => true
            ],
            
            // Portfolio categories
            [
                'name' => 'Архитектура',
                'slug' => 'architecture',
                'type' => 'portfolio',
                'description' => 'Архитектурные проекты и визуализации',
                'icon' => 'fa-building',
                'sort_order' => 10,
                'active' => true
            ],
            [
                'name' => 'Прототипирование',
                'slug' => 'prototyping',
                'type' => 'portfolio',
                'description' => 'Функциональные прототипы',
                'icon' => 'fa-flask',
                'sort_order' => 20,
                'active' => true
            ],
            [
                'name' => 'Декоративные изделия',
                'slug' => 'decorative',
                'type' => 'portfolio',
                'description' => 'Декоративные и художественные работы',
                'icon' => 'fa-palette',
                'sort_order' => 30,
                'active' => true
            ],
            [
                'name' => 'Промышленность',
                'slug' => 'industrial',
                'type' => 'portfolio',
                'description' => 'Промышленные детали и оборудование',
                'icon' => 'fa-cogs',
                'sort_order' => 40,
                'active' => true
            ],
            
            // FAQ categories
            [
                'name' => 'Общие вопросы',
                'slug' => 'general',
                'type' => 'faq',
                'description' => 'Общие вопросы о услугах',
                'icon' => 'fa-question-circle',
                'sort_order' => 10,
                'active' => true
            ],
            [
                'name' => 'Технологии',
                'slug' => 'technology',
                'type' => 'faq',
                'description' => 'Вопросы о технологиях печати',
                'icon' => 'fa-cog',
                'sort_order' => 20,
                'active' => true
            ],
            [
                'name' => 'Стоимость и оплата',
                'slug' => 'pricing',
                'type' => 'faq',
                'description' => 'Вопросы о ценах и оплате',
                'icon' => 'fa-dollar-sign',
                'sort_order' => 30,
                'active' => true
            ]
        ];

        foreach ($categories as $category) {
            $this->updateOrInsert(
                'categories', 
                ['type' => $category['type'], 'slug' => $category['slug']], 
                $category
            );
        }
    }
}
