<?php

namespace App\Http\Controllers\Api;

use App\Services\SettingsService;
use App\Services\FormulaValidatorService;

/**
 * Calculator Settings Controller
 * 
 * Manages calculator configuration including materials, services,
 * quality multipliers, discounts, and formulas.
 * 
 * Public endpoints for reading configuration.
 * Admin-only endpoints for updating configuration.
 */
class CalculatorSettingsController extends BaseApiController
{
    private $settingsService;
    private $formulaValidator;
    
    public function __construct()
    {
        $this->settingsService = new SettingsService();
        $this->formulaValidator = new FormulaValidatorService();
    }
    
    /**
     * Get complete calculator configuration (PUBLIC)
     * 
     * Returns all calculator settings including materials, services,
     * quality multipliers, discounts, formulas, and validation rules.
     * 
     * @return void (outputs JSON)
     */
    public function getConfig()
    {
        try {
            $config = [
                'materials' => $this->settingsService->get('calculator.materials', []),
                'services' => $this->settingsService->get('calculator.services', []),
                'quality_multipliers' => $this->settingsService->get('calculator.quality_multipliers', []),
                'discounts' => $this->settingsService->get('calculator.discounts', []),
                'formulas' => $this->settingsService->get('calculator.formulas', []),
                'validation' => $this->settingsService->get('calculator.validation', []),
            ];
            
            // Filter out inactive items
            $config['materials'] = array_values(array_filter($config['materials'], function($item) {
                return isset($item['active']) ? $item['active'] : true;
            }));
            
            $config['services'] = array_values(array_filter($config['services'], function($item) {
                return isset($item['active']) ? $item['active'] : true;
            }));
            
            $config['discounts'] = array_values(array_filter($config['discounts'], function($item) {
                return isset($item['active']) ? $item['active'] : true;
            }));
            
            // Filter quality multipliers
            $activeQualities = [];
            foreach ($config['quality_multipliers'] as $key => $quality) {
                if (isset($quality['active']) && $quality['active']) {
                    $activeQualities[$key] = $quality;
                } elseif (!isset($quality['active'])) {
                    $activeQualities[$key] = $quality;
                }
            }
            $config['quality_multipliers'] = $activeQualities;
            
            // Filter formulas
            $activeFormulas = [];
            foreach ($config['formulas'] as $key => $formula) {
                if (isset($formula['active']) && $formula['active']) {
                    $activeFormulas[$key] = $formula;
                } elseif (!isset($formula['active'])) {
                    $activeFormulas[$key] = $formula;
                }
            }
            $config['formulas'] = $activeFormulas;
            
            $this->success($config, [
                'cache_ttl' => 300, // 5 minutes
                'version' => '1.0'
            ]);
            
        } catch (\Exception $e) {
            $this->serverError('Failed to load calculator configuration');
        }
    }
    
    /**
     * Get admin configuration (includes inactive items) (ADMIN)
     * 
     * @return void (outputs JSON)
     */
    public function getAdminConfig()
    {
        $this->requireAuth();
        
        try {
            $config = [
                'materials' => $this->settingsService->get('calculator.materials', []),
                'services' => $this->settingsService->get('calculator.services', []),
                'quality_multipliers' => $this->settingsService->get('calculator.quality_multipliers', []),
                'discounts' => $this->settingsService->get('calculator.discounts', []),
                'formulas' => $this->settingsService->get('calculator.formulas', []),
                'validation' => $this->settingsService->get('calculator.validation', []),
            ];
            
            $this->success($config);
            
        } catch (\Exception $e) {
            $this->serverError('Failed to load calculator configuration');
        }
    }
    
    /**
     * Update materials configuration (ADMIN)
     * 
     * @return void (outputs JSON)
     */
    public function updateMaterials()
    {
        $this->requireAuth();
        verifyCsrfToken();
        
        $data = $this->input();
        
        if (!isset($data['materials']) || !is_array($data['materials'])) {
            $this->validationError('Materials must be an array');
        }
        
        // Validate each material
        foreach ($data['materials'] as $index => $material) {
            $errors = $this->validateMaterial($material);
            if (!empty($errors)) {
                $this->validationError('Material #' . ($index + 1) . ': ' . implode(', ', $errors));
            }
        }
        
        try {
            $user = getAuthenticatedUser();
            $changedBy = $user ? $user->email : 'admin';
            $this->settingsService->set('calculator.materials', $data['materials'], $changedBy);
            
            $this->success([
                'message' => 'Materials updated successfully',
                'materials' => $data['materials']
            ]);
            
        } catch (\Exception $e) {
            $this->serverError('Failed to update materials');
        }
    }
    
    /**
     * Update services configuration (ADMIN)
     * 
     * @return void (outputs JSON)
     */
    public function updateServices()
    {
        $this->requireAuth();
        verifyCsrfToken();
        
        $data = $this->input();
        
        if (!isset($data['services']) || !is_array($data['services'])) {
            $this->validationError('Services must be an array');
        }
        
        // Validate each service
        foreach ($data['services'] as $index => $service) {
            $errors = $this->validateService($service);
            if (!empty($errors)) {
                $this->validationError('Service #' . ($index + 1) . ': ' . implode(', ', $errors));
            }
        }
        
        try {
            $user = getAuthenticatedUser();
            $changedBy = $user ? $user->email : 'admin';
            $this->settingsService->set('calculator.services', $data['services'], $changedBy);
            
            $this->success([
                'message' => 'Services updated successfully',
                'services' => $data['services']
            ]);
            
        } catch (\Exception $e) {
            $this->serverError('Failed to update services');
        }
    }
    
    /**
     * Update quality multipliers (ADMIN)
     * 
     * @return void (outputs JSON)
     */
    public function updateQualityMultipliers()
    {
        $this->requireAuth();
        verifyCsrfToken();
        
        $data = $this->input();
        
        if (!isset($data['quality_multipliers']) || !is_array($data['quality_multipliers'])) {
            $this->validationError('Quality multipliers must be an object');
        }
        
        // Validate each quality level
        foreach ($data['quality_multipliers'] as $key => $quality) {
            $errors = $this->validateQuality($quality);
            if (!empty($errors)) {
                $this->validationError("Quality '{$key}': " . implode(', ', $errors));
            }
        }
        
        try {
            $user = getAuthenticatedUser();
            $changedBy = $user ? $user->email : 'admin';
            $this->settingsService->set('calculator.quality_multipliers', $data['quality_multipliers'], $changedBy);
            
            $this->success([
                'message' => 'Quality multipliers updated successfully',
                'quality_multipliers' => $data['quality_multipliers']
            ]);
            
        } catch (\Exception $e) {
            $this->serverError('Failed to update quality multipliers');
        }
    }
    
    /**
     * Update discounts (ADMIN)
     * 
     * @return void (outputs JSON)
     */
    public function updateDiscounts()
    {
        $this->requireAuth();
        verifyCsrfToken();
        
        $data = $this->input();
        
        if (!isset($data['discounts']) || !is_array($data['discounts'])) {
            $this->validationError('Discounts must be an array');
        }
        
        // Validate each discount
        foreach ($data['discounts'] as $index => $discount) {
            $errors = $this->validateDiscount($discount);
            if (!empty($errors)) {
                $this->validationError('Discount #' . ($index + 1) . ': ' . implode(', ', $errors));
            }
        }
        
        try {
            $user = getAuthenticatedUser();
            $changedBy = $user ? $user->email : 'admin';
            $this->settingsService->set('calculator.discounts', $data['discounts'], $changedBy);
            
            $this->success([
                'message' => 'Discounts updated successfully',
                'discounts' => $data['discounts']
            ]);
            
        } catch (\Exception $e) {
            $this->serverError('Failed to update discounts');
        }
    }
    
    /**
     * Update formulas (ADMIN)
     * 
     * @return void (outputs JSON)
     */
    public function updateFormulas()
    {
        $this->requireAuth();
        verifyCsrfToken();
        
        $data = $this->input();
        
        if (!isset($data['formulas']) || !is_array($data['formulas'])) {
            $this->validationError('Formulas must be an object');
        }
        
        // Validate each formula
        foreach ($data['formulas'] as $key => $formula) {
            if (!isset($formula['formula'])) {
                $this->validationError("Formula '{$key}' is missing formula field");
            }
            
            $variables = $formula['variables'] ?? [];
            $validation = $this->formulaValidator->validate($formula['formula'], $variables);
            
            if (!$validation['valid']) {
                $this->validationError("Formula '{$key}': " . implode(', ', $validation['errors']));
            }
        }
        
        try {
            $user = getAuthenticatedUser();
            $changedBy = $user ? $user->email : 'admin';
            $this->settingsService->set('calculator.formulas', $data['formulas'], $changedBy);
            
            $this->success([
                'message' => 'Formulas updated successfully',
                'formulas' => $data['formulas']
            ]);
            
        } catch (\Exception $e) {
            $this->serverError('Failed to update formulas');
        }
    }
    
    /**
     * Validate a formula (ADMIN)
     * 
     * @return void (outputs JSON)
     */
    public function validateFormula()
    {
        $this->requireAuth();
        
        $data = $this->input();
        
        if (!isset($data['formula'])) {
            $this->validationError('Formula is required');
        }
        
        $variables = $data['variables'] ?? [];
        $testValues = $data['test_values'] ?? [];
        
        $validation = $this->formulaValidator->validate($data['formula'], $variables);
        
        $response = [
            'valid' => $validation['valid'],
            'errors' => $validation['errors']
        ];
        
        // If valid and test values provided, evaluate
        if ($validation['valid'] && !empty($testValues)) {
            $result = $this->formulaValidator->evaluate($data['formula'], $testValues);
            $response['test_result'] = $result;
        }
        
        $this->success($response);
    }
    
    /**
     * Test calculator with given inputs (ADMIN)
     * 
     * @return void (outputs JSON)
     */
    public function testCalculation()
    {
        $this->requireAuth();
        
        $data = $this->input();
        
        // Get current configuration
        $materials = $this->settingsService->get('calculator.materials', []);
        $services = $this->settingsService->get('calculator.services', []);
        $qualities = $this->settingsService->get('calculator.quality_multipliers', []);
        $discounts = $this->settingsService->get('calculator.discounts', []);
        $formulas = $this->settingsService->get('calculator.formulas', []);
        
        // Extract test inputs
        $weight = $data['weight'] ?? 100;
        $quantity = $data['quantity'] ?? 1;
        $infill = $data['infill'] ?? 20;
        $materialKey = $data['material'] ?? 'pla';
        $qualityKey = $data['quality'] ?? 'normal';
        $additionalServices = $data['additional_services'] ?? [];
        
        // Find material
        $material = null;
        foreach ($materials as $mat) {
            if ($mat['key'] === $materialKey) {
                $material = $mat;
                break;
            }
        }
        
        if (!$material) {
            $this->validationError('Material not found');
        }
        
        // Find quality
        $quality = $qualities[$qualityKey] ?? null;
        if (!$quality) {
            $this->validationError('Quality level not found');
        }
        
        try {
            // Calculate using formulas
            $calculation = $this->performCalculation(
                $weight, $quantity, $infill,
                $material, $quality, $additionalServices,
                $services, $discounts, $formulas
            );
            
            $this->success($calculation);
            
        } catch (\Exception $e) {
            $this->serverError('Calculation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate material data
     */
    private function validateMaterial($material)
    {
        $errors = [];
        
        if (empty($material['key'])) {
            $errors[] = 'Key is required';
        }
        if (empty($material['name'])) {
            $errors[] = 'Name is required';
        }
        if (!isset($material['price']) || !is_numeric($material['price']) || $material['price'] < 0) {
            $errors[] = 'Price must be a non-negative number';
        }
        if (empty($material['technology']) || !in_array($material['technology'], ['fdm', 'sla', 'sls'])) {
            $errors[] = 'Technology must be fdm, sla, or sls';
        }
        
        return $errors;
    }
    
    /**
     * Validate service data
     */
    private function validateService($service)
    {
        $errors = [];
        
        if (empty($service['key'])) {
            $errors[] = 'Key is required';
        }
        if (empty($service['name'])) {
            $errors[] = 'Name is required';
        }
        if (!isset($service['price']) || !is_numeric($service['price']) || $service['price'] < 0) {
            $errors[] = 'Price must be a non-negative number';
        }
        if (empty($service['unit'])) {
            $errors[] = 'Unit is required';
        }
        
        return $errors;
    }
    
    /**
     * Validate quality data
     */
    private function validateQuality($quality)
    {
        $errors = [];
        
        if (empty($quality['name'])) {
            $errors[] = 'Name is required';
        }
        if (!isset($quality['multiplier']) || !is_numeric($quality['multiplier']) || $quality['multiplier'] <= 0) {
            $errors[] = 'Multiplier must be a positive number';
        }
        if (!isset($quality['time']) || !is_numeric($quality['time']) || $quality['time'] <= 0) {
            $errors[] = 'Time must be a positive number';
        }
        
        return $errors;
    }
    
    /**
     * Validate discount data
     */
    private function validateDiscount($discount)
    {
        $errors = [];
        
        if (!isset($discount['minQuantity']) || !is_numeric($discount['minQuantity']) || $discount['minQuantity'] < 1) {
            $errors[] = 'Min quantity must be at least 1';
        }
        if (!isset($discount['percent']) || !is_numeric($discount['percent']) || $discount['percent'] < 0 || $discount['percent'] > 100) {
            $errors[] = 'Percent must be between 0 and 100';
        }
        
        return $errors;
    }
    
    /**
     * Perform calculation using formulas
     */
    private function performCalculation($weight, $quantity, $infill, $material, $quality, $additionalServices, $services, $discounts, $formulas)
    {
        // Calculate infill factor
        $infillFactor = 0.3 + ($infill / 100 * 0.7);
        
        // Calculate material cost
        $materialCost = $weight * $material['price'] * $infillFactor * $quantity;
        
        // Calculate labor cost
        $laborCostBase = 500 + ($weight * 2);
        $laborCost = $laborCostBase * $quality['multiplier'] * $quantity;
        
        // Calculate additional services cost
        $servicesCost = 0;
        $servicesUsed = [];
        foreach ($additionalServices as $serviceKey) {
            foreach ($services as $service) {
                if ($service['key'] === $serviceKey && isset($service['active']) && $service['active']) {
                    $price = $service['price'];
                    if ($service['unit'] === 'шт') {
                        $servicesCost += $price * $quantity;
                    } else {
                        $servicesCost += $price;
                    }
                    $servicesUsed[] = $service['name'];
                    break;
                }
            }
        }
        
        // Calculate subtotal
        $subtotal = $materialCost + $laborCost;
        
        // Calculate discount
        $discount = 0;
        $discountPercent = 0;
        $sortedDiscounts = $discounts;
        usort($sortedDiscounts, function($a, $b) {
            return $b['minQuantity'] - $a['minQuantity'];
        });
        
        foreach ($sortedDiscounts as $discountTier) {
            if (isset($discountTier['active']) && !$discountTier['active']) {
                continue;
            }
            if ($quantity >= $discountTier['minQuantity']) {
                $discountPercent = $discountTier['percent'];
                $discount = $subtotal * ($discountPercent / 100);
                break;
            }
        }
        
        // Calculate total
        $total = $subtotal + $servicesCost - $discount;
        
        // Calculate time
        $hours = ($weight / 10) * $quality['time'] * $quantity;
        $days = max(1, ceil($hours / 8));
        
        if (in_array('express', $additionalServices)) {
            $hours = min($hours, 24);
            $timeEstimate = '24 часа';
        } else {
            $timeEstimate = $days === 1 ? '1 день' : "{$days} дня";
        }
        
        return [
            'breakdown' => [
                'material_cost' => round($materialCost),
                'labor_cost' => round($laborCost),
                'services_cost' => round($servicesCost),
                'subtotal' => round($subtotal),
                'discount' => round($discount),
                'discount_percent' => $discountPercent,
                'total' => round($total)
            ],
            'time_estimate' => $timeEstimate,
            'time_hours' => round($hours, 1),
            'time_days' => $days,
            'services_used' => $servicesUsed,
            'details' => [
                'material' => $material['name'],
                'weight' => $weight,
                'quantity' => $quantity,
                'infill' => $infill,
                'quality' => $quality['name'],
                'infill_factor' => round($infillFactor, 3)
            ]
        ];
    }
}
