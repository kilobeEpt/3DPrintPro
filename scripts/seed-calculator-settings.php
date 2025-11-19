<?php
/**
 * Seed Calculator Settings
 * 
 * Migrates calculator configuration from hardcoded config.js values
 * into structured settings managed by SettingsService.
 * 
 * Usage: php scripts/seed-calculator-settings.php [--force]
 *   --force    Overwrite existing calculator settings
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Services\SettingsService;

$forceOverwrite = in_array('--force', $argv);

echo "🔄 Seeding calculator settings...\n";

$settingsService = new SettingsService();

// Check if settings already exist
$existingMaterials = $settingsService->get('calculator.materials', null);
if ($existingMaterials !== null && !$forceOverwrite) {
    echo "⚠️  Calculator settings already exist. Use --force to overwrite.\n";
    exit(0);
}

// Define calculator settings from config.js
$calculatorSettings = [
    // Materials with pricing and technology
    'calculator.materials' => [
        [
            'key' => 'pla',
            'name' => 'PLA',
            'price' => 50,
            'technology' => 'fdm',
            'active' => true,
            'order' => 1
        ],
        [
            'key' => 'abs',
            'name' => 'ABS',
            'price' => 60,
            'technology' => 'fdm',
            'active' => true,
            'order' => 2
        ],
        [
            'key' => 'petg',
            'name' => 'PETG',
            'price' => 70,
            'technology' => 'fdm',
            'active' => true,
            'order' => 3
        ],
        [
            'key' => 'nylon',
            'name' => 'Nylon',
            'price' => 120,
            'technology' => 'fdm',
            'active' => true,
            'order' => 4
        ],
        [
            'key' => 'tpu',
            'name' => 'TPU (Flex)',
            'price' => 150,
            'technology' => 'fdm',
            'active' => true,
            'order' => 5
        ],
        [
            'key' => 'standard_resin',
            'name' => 'Standard Resin',
            'price' => 200,
            'technology' => 'sla',
            'active' => true,
            'order' => 6
        ],
        [
            'key' => 'tough_resin',
            'name' => 'Tough Resin',
            'price' => 250,
            'technology' => 'sla',
            'active' => true,
            'order' => 7
        ],
        [
            'key' => 'flexible_resin',
            'name' => 'Flexible Resin',
            'price' => 280,
            'technology' => 'sla',
            'active' => true,
            'order' => 8
        ],
        [
            'key' => 'pa12',
            'name' => 'PA12 Nylon',
            'price' => 150,
            'technology' => 'sls',
            'active' => true,
            'order' => 9
        ],
        [
            'key' => 'tpu_sls',
            'name' => 'TPU SLS',
            'price' => 180,
            'technology' => 'sls',
            'active' => true,
            'order' => 10
        ]
    ],
    
    // Additional services with pricing
    'calculator.services' => [
        [
            'key' => 'modeling',
            'name' => '3D моделирование',
            'price' => 500,
            'unit' => 'час',
            'active' => true,
            'order' => 1
        ],
        [
            'key' => 'postProcessing',
            'name' => 'Постобработка',
            'price' => 300,
            'unit' => 'шт',
            'active' => true,
            'order' => 2
        ],
        [
            'key' => 'painting',
            'name' => 'Покраска',
            'price' => 500,
            'unit' => 'шт',
            'active' => true,
            'order' => 3
        ],
        [
            'key' => 'express',
            'name' => 'Срочное изготовление',
            'price' => 1000,
            'unit' => 'заказ',
            'active' => true,
            'order' => 4
        ]
    ],
    
    // Quality multipliers affecting price and time
    'calculator.quality_multipliers' => [
        'draft' => [
            'name' => 'Черновое',
            'multiplier' => 0.8,
            'time' => 0.7,
            'active' => true,
            'order' => 1
        ],
        'normal' => [
            'name' => 'Нормальное',
            'multiplier' => 1.0,
            'time' => 1.0,
            'active' => true,
            'order' => 2
        ],
        'high' => [
            'name' => 'Высокое',
            'multiplier' => 1.3,
            'time' => 1.4,
            'active' => true,
            'order' => 3
        ],
        'ultra' => [
            'name' => 'Ультра',
            'multiplier' => 1.6,
            'time' => 2.0,
            'active' => true,
            'order' => 4
        ]
    ],
    
    // Volume discounts by quantity
    'calculator.discounts' => [
        [
            'minQuantity' => 10,
            'percent' => 10,
            'active' => true
        ],
        [
            'minQuantity' => 50,
            'percent' => 15,
            'active' => true
        ],
        [
            'minQuantity' => 100,
            'percent' => 20,
            'active' => true
        ]
    ],
    
    // Calculation formulas (editable math expressions)
    'calculator.formulas' => [
        'infill_factor' => [
            'name' => 'Infill Factor',
            'description' => 'Calculates material usage based on infill percentage',
            'formula' => '0.3 + (infill / 100 * 0.7)',
            'variables' => ['infill'],
            'active' => true
        ],
        'material_cost' => [
            'name' => 'Material Cost',
            'description' => 'Calculates cost of materials',
            'formula' => 'weight * material_price * infill_factor',
            'variables' => ['weight', 'material_price', 'infill_factor'],
            'active' => true
        ],
        'labor_cost_base' => [
            'name' => 'Labor Cost Base',
            'description' => 'Base labor cost calculation',
            'formula' => '500 + (weight * 2)',
            'variables' => ['weight'],
            'active' => true
        ],
        'labor_cost' => [
            'name' => 'Labor Cost',
            'description' => 'Labor cost with quality multiplier',
            'formula' => 'labor_cost_base * quality_multiplier',
            'variables' => ['labor_cost_base', 'quality_multiplier'],
            'active' => true
        ],
        'print_time_hours' => [
            'name' => 'Print Time (Hours)',
            'description' => 'Estimated print time in hours',
            'formula' => '(weight / 10) * time_multiplier * quantity',
            'variables' => ['weight', 'time_multiplier', 'quantity'],
            'active' => true
        ]
    ],
    
    // Validation rules for inputs
    'calculator.validation' => [
        'weight' => [
            'min' => 1,
            'max' => 10000,
            'label' => 'Вес (г)'
        ],
        'quantity' => [
            'min' => 1,
            'max' => 1000,
            'label' => 'Количество (шт)'
        ],
        'infill' => [
            'min' => 0,
            'max' => 100,
            'label' => 'Заполнение (%)'
        ]
    ]
];

// Save all settings
$successCount = 0;
$errors = [];

foreach ($calculatorSettings as $key => $value) {
    try {
        $settingsService->set($key, $value, 'system');
        echo "✅ Saved: {$key}\n";
        $successCount++;
    } catch (\Exception $e) {
        echo "❌ Failed to save {$key}: {$e->getMessage()}\n";
        $errors[$key] = $e->getMessage();
    }
}

echo "\n";
echo "✅ Successfully saved {$successCount} calculator settings\n";

if (!empty($errors)) {
    echo "❌ Failed to save " . count($errors) . " settings\n";
    foreach ($errors as $key => $error) {
        echo "   - {$key}: {$error}\n";
    }
    exit(1);
}

echo "\n";
echo "🎉 Calculator settings seeded successfully!\n";
echo "   You can now manage calculator configuration from the admin panel.\n";

exit(0);
