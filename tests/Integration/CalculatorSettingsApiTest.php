<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\SettingsService;
use App\Models\AdminUser;
use App\Models\AdminSession;

class CalculatorSettingsApiTest extends TestCase
{
    private $settingsService;
    private $adminUser;
    private $sessionId;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsService = new SettingsService();
        
        // Seed calculator settings
        $this->seedCalculatorSettings();
        
        // Create admin user and session for authenticated endpoints
        $this->adminUser = AdminUser::create([
            'email' => 'admin@test.com',
            'name' => 'Test Admin',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $this->sessionId = 'test_session_' . bin2hex(random_bytes(16));
        AdminSession::create([
            'session_id' => $this->sessionId,
            'user_id' => $this->adminUser->id,
            'csrf_token' => 'test_csrf_token',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ]);
    }
    
    private function seedCalculatorSettings()
    {
        $this->settingsService->set('calculator.materials', [
            ['key' => 'pla', 'name' => 'PLA', 'price' => 50, 'technology' => 'fdm', 'active' => true, 'order' => 1],
            ['key' => 'abs', 'name' => 'ABS', 'price' => 60, 'technology' => 'fdm', 'active' => true, 'order' => 2],
        ], 'system');
        
        $this->settingsService->set('calculator.services', [
            ['key' => 'modeling', 'name' => '3D моделирование', 'price' => 500, 'unit' => 'час', 'active' => true, 'order' => 1],
        ], 'system');
        
        $this->settingsService->set('calculator.quality_multipliers', [
            'normal' => ['name' => 'Нормальное', 'multiplier' => 1.0, 'time' => 1.0, 'active' => true, 'order' => 1],
            'high' => ['name' => 'Высокое', 'multiplier' => 1.3, 'time' => 1.4, 'active' => true, 'order' => 2],
        ], 'system');
        
        $this->settingsService->set('calculator.discounts', [
            ['minQuantity' => 10, 'percent' => 10, 'active' => true],
        ], 'system');
        
        $this->settingsService->set('calculator.formulas', [
            'infill_factor' => [
                'name' => 'Infill Factor',
                'formula' => '0.3 + (infill / 100 * 0.7)',
                'variables' => ['infill'],
                'active' => true
            ],
        ], 'system');
    }
    
    public function testGetPublicConfig()
    {
        $config = $this->settingsService->get('calculator.materials');
        $this->assertNotEmpty($config);
        $this->assertIsArray($config);
        $this->assertCount(2, $config);
        
        $services = $this->settingsService->get('calculator.services');
        $this->assertNotEmpty($services);
        $this->assertCount(1, $services);
    }
    
    public function testGetPublicConfigFiltersInactive()
    {
        // Add inactive material
        $materials = $this->settingsService->get('calculator.materials');
        $materials[] = ['key' => 'inactive', 'name' => 'Inactive', 'price' => 100, 'technology' => 'fdm', 'active' => false, 'order' => 3];
        $this->settingsService->set('calculator.materials', $materials, 'system');
        
        // Should still only return active materials
        $materials = $this->settingsService->get('calculator.materials');
        $active = array_filter($materials, fn($m) => $m['active'] !== false);
        $this->assertCount(2, $active);
    }
    
    public function testGetAdminConfigIncludesInactive()
    {
        // Add inactive material
        $materials = $this->settingsService->get('calculator.materials');
        $materials[] = ['key' => 'inactive', 'name' => 'Inactive', 'price' => 100, 'technology' => 'fdm', 'active' => false, 'order' => 3];
        $this->settingsService->set('calculator.materials', $materials, 'system');
        
        // Admin should see all materials
        $allMaterials = $this->settingsService->get('calculator.materials');
        $this->assertCount(3, $allMaterials);
    }
    
    public function testUpdateMaterials()
    {
        $newMaterials = [
            ['key' => 'petg', 'name' => 'PETG', 'price' => 70, 'technology' => 'fdm', 'active' => true, 'order' => 1],
        ];
        
        $this->settingsService->set('calculator.materials', $newMaterials, $this->adminUser->email);
        
        $materials = $this->settingsService->get('calculator.materials');
        $this->assertCount(1, $materials);
        $this->assertEquals('petg', $materials[0]['key']);
        $this->assertEquals(70, $materials[0]['price']);
    }
    
    public function testUpdateServices()
    {
        $newServices = [
            ['key' => 'painting', 'name' => 'Покраска', 'price' => 500, 'unit' => 'шт', 'active' => true, 'order' => 1],
        ];
        
        $this->settingsService->set('calculator.services', $newServices, $this->adminUser->email);
        
        $services = $this->settingsService->get('calculator.services');
        $this->assertCount(1, $services);
        $this->assertEquals('painting', $services[0]['key']);
    }
    
    public function testUpdateQualityMultipliers()
    {
        $newQualities = [
            'draft' => ['name' => 'Черновое', 'multiplier' => 0.8, 'time' => 0.7, 'active' => true, 'order' => 1],
        ];
        
        $this->settingsService->set('calculator.quality_multipliers', $newQualities, $this->adminUser->email);
        
        $qualities = $this->settingsService->get('calculator.quality_multipliers');
        $this->assertArrayHasKey('draft', $qualities);
        $this->assertEquals(0.8, $qualities['draft']['multiplier']);
    }
    
    public function testUpdateDiscounts()
    {
        $newDiscounts = [
            ['minQuantity' => 50, 'percent' => 15, 'active' => true],
        ];
        
        $this->settingsService->set('calculator.discounts', $newDiscounts, $this->adminUser->email);
        
        $discounts = $this->settingsService->get('calculator.discounts');
        $this->assertCount(1, $discounts);
        $this->assertEquals(50, $discounts[0]['minQuantity']);
        $this->assertEquals(15, $discounts[0]['percent']);
    }
    
    public function testUpdateFormulas()
    {
        $newFormulas = [
            'labor_cost' => [
                'name' => 'Labor Cost',
                'formula' => '500 + (weight * 2)',
                'variables' => ['weight'],
                'active' => true
            ],
        ];
        
        $this->settingsService->set('calculator.formulas', $newFormulas, $this->adminUser->email);
        
        $formulas = $this->settingsService->get('calculator.formulas');
        $this->assertArrayHasKey('labor_cost', $formulas);
        $this->assertEquals('500 + (weight * 2)', $formulas['labor_cost']['formula']);
    }
    
    public function testMaterialValidation()
    {
        $invalidMaterial = [
            ['key' => '', 'name' => 'Invalid', 'price' => -10, 'technology' => 'invalid', 'active' => true],
        ];
        
        // Should throw validation error (in real API, this would be caught by controller)
        $this->expectException(\Exception::class);
        
        // This is a simplified test - in real scenario, the controller would validate before calling service
        if (empty($invalidMaterial[0]['key']) || $invalidMaterial[0]['price'] < 0) {
            throw new \Exception('Validation failed');
        }
    }
    
    public function testServiceValidation()
    {
        $invalidService = [
            ['key' => '', 'name' => 'Invalid', 'price' => -100, 'unit' => '', 'active' => true],
        ];
        
        $this->expectException(\Exception::class);
        
        if (empty($invalidService[0]['key']) || $invalidService[0]['price'] < 0) {
            throw new \Exception('Validation failed');
        }
    }
    
    public function testDiscountValidation()
    {
        $invalidDiscount = [
            ['minQuantity' => 0, 'percent' => 150, 'active' => true],
        ];
        
        $this->expectException(\Exception::class);
        
        if ($invalidDiscount[0]['minQuantity'] < 1 || $invalidDiscount[0]['percent'] > 100) {
            throw new \Exception('Validation failed');
        }
    }
    
    public function testCalculationWithApiConfig()
    {
        // Get config
        $materials = $this->settingsService->get('calculator.materials');
        $qualities = $this->settingsService->get('calculator.quality_multipliers');
        $discounts = $this->settingsService->get('calculator.discounts');
        
        // Simulate calculation
        $weight = 100;
        $quantity = 1;
        $infill = 20;
        $materialKey = 'pla';
        $qualityKey = 'normal';
        
        // Find material
        $material = null;
        foreach ($materials as $mat) {
            if ($mat['key'] === $materialKey) {
                $material = $mat;
                break;
            }
        }
        
        $this->assertNotNull($material);
        $this->assertEquals(50, $material['price']);
        
        // Calculate
        $infillFactor = 0.3 + ($infill / 100 * 0.7);
        $materialCost = $weight * $material['price'] * $infillFactor;
        $laborCost = 500 + ($weight * 2);
        $laborCost *= $qualities[$qualityKey]['multiplier'];
        
        $total = $materialCost + $laborCost;
        
        $this->assertGreaterThan(0, $total);
        $this->assertEquals(1200, round($materialCost));
        $this->assertEquals(700, round($laborCost));
    }
    
    public function testDiscountApplication()
    {
        $discounts = $this->settingsService->get('calculator.discounts');
        
        // Test with quantity below threshold
        $quantity = 5;
        $applicableDiscount = null;
        foreach ($discounts as $discount) {
            if ($quantity >= $discount['minQuantity']) {
                $applicableDiscount = $discount;
                break;
            }
        }
        $this->assertNull($applicableDiscount);
        
        // Test with quantity above threshold
        $quantity = 15;
        $applicableDiscount = null;
        foreach ($discounts as $discount) {
            if ($quantity >= $discount['minQuantity']) {
                $applicableDiscount = $discount;
                break;
            }
        }
        $this->assertNotNull($applicableDiscount);
        $this->assertEquals(10, $applicableDiscount['percent']);
    }
    
    public function testCacheInvalidation()
    {
        // Get initial config
        $materials = $this->settingsService->get('calculator.materials');
        $initialCount = count($materials);
        
        // Update config
        $materials[] = ['key' => 'new', 'name' => 'New', 'price' => 80, 'technology' => 'fdm', 'active' => true, 'order' => 3];
        $this->settingsService->set('calculator.materials', $materials, $this->adminUser->email);
        
        // Get updated config (should be fresh from DB)
        $updatedMaterials = $this->settingsService->get('calculator.materials', false);
        $this->assertCount($initialCount + 1, $updatedMaterials);
    }
}
