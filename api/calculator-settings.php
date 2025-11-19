<?php
/**
 * Calculator Settings API Endpoint
 * 
 * Provides public and admin endpoints for calculator configuration.
 * 
 * Public endpoints:
 *   GET /api/calculator-settings.php                       - Get active configuration
 * 
 * Admin endpoints (require authentication):
 *   GET /api/calculator-settings.php?admin=1               - Get full configuration
 *   POST /api/calculator-settings.php?action=materials     - Update materials
 *   POST /api/calculator-settings.php?action=services      - Update services
 *   POST /api/calculator-settings.php?action=quality       - Update quality multipliers
 *   POST /api/calculator-settings.php?action=discounts     - Update discounts
 *   POST /api/calculator-settings.php?action=formulas      - Update formulas
 *   POST /api/calculator-settings.php?action=validate      - Validate a formula
 *   POST /api/calculator-settings.php?action=test          - Test calculation
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\Controllers\Api\CalculatorSettingsController;

$controller = new CalculatorSettingsController();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['admin']) && $_GET['admin'] === '1') {
                $controller->getAdminConfig();
            } else {
                $controller->getConfig();
            }
            break;
            
        case 'POST':
        case 'PUT':
            $action = $_GET['action'] ?? $_POST['action'] ?? null;
            
            switch ($action) {
                case 'materials':
                    $controller->updateMaterials();
                    break;
                    
                case 'services':
                    $controller->updateServices();
                    break;
                    
                case 'quality':
                    $controller->updateQualityMultipliers();
                    break;
                    
                case 'discounts':
                    $controller->updateDiscounts();
                    break;
                    
                case 'formulas':
                    $controller->updateFormulas();
                    break;
                    
                case 'validate':
                    $controller->validateFormula();
                    break;
                    
                case 'test':
                    $controller->testCalculation();
                    break;
                    
                default:
                    ApiResponse::validationError('Invalid action. Use: materials, services, quality, discounts, formulas, validate, or test');
            }
            break;
            
        default:
            ApiResponse::methodNotAllowed();
    }
    
} catch (\Exception $e) {
    ApiLogger::error('Calculator settings endpoint error', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    ApiResponse::serverError('An unexpected error occurred');
}
