<?php
/**
 * Forms API End-to-End Test Script
 * 
 * Tests the complete forms API workflow:
 * 1. Load form definitions
 * 2. Validate submissions
 * 3. Process submissions
 * 4. Verify database records
 * 
 * Usage: php scripts/test-forms-api.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';
require_once __DIR__ . '/../api/helpers/form_service.php';
require_once __DIR__ . '/../api/helpers/logger.php';

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Order;

echo "=================================================\n";
echo "Forms API End-to-End Test\n";
echo "=================================================\n\n";

$errors = 0;
$warnings = 0;

// Test 1: Load form by slug
echo "Test 1: Loading form by slug...\n";
try {
    $contactForm = FormService::loadForm('contact', true);
    if ($contactForm) {
        echo "  ✓ Contact form loaded successfully\n";
        echo "    - Form ID: {$contactForm['id']}\n";
        echo "    - Fields count: " . count($contactForm['fields']) . "\n";
    } else {
        echo "  ✗ Contact form not found\n";
        $errors++;
    }
    
    $orderForm = FormService::loadForm('order', true);
    if ($orderForm) {
        echo "  ✓ Order form loaded successfully\n";
        echo "    - Form ID: {$orderForm['id']}\n";
        echo "    - Fields count: " . count($orderForm['fields']) . "\n";
    } else {
        echo "  ✗ Order form not found\n";
        $errors++;
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 2: Validate valid submission
echo "Test 2: Validating valid contact form submission...\n";
try {
    if ($contactForm) {
        $validData = [
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@example.com',
            'service' => 'Консультация',
            'message' => 'Test message',
        ];
        
        $validation = FormService::validateSubmission($contactForm, $validData);
        if ($validation['valid']) {
            echo "  ✓ Valid data passed validation\n";
        } else {
            echo "  ✗ Valid data failed validation:\n";
            print_r($validation['errors']);
            $errors++;
        }
    } else {
        echo "  ⊘ Skipped (form not loaded)\n";
        $warnings++;
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 3: Validate invalid submission
echo "Test 3: Validating invalid contact form submission...\n";
try {
    if ($contactForm) {
        $invalidData = [
            'name' => '',  // Empty required field
            'phone' => 'invalid',  // Invalid phone
            'email' => 'not-an-email',  // Invalid email
        ];
        
        $validation = FormService::validateSubmission($contactForm, $invalidData);
        if (!$validation['valid']) {
            echo "  ✓ Invalid data correctly rejected\n";
            echo "    - Validation errors: " . count($validation['errors']) . "\n";
            foreach ($validation['errors'] as $field => $error) {
                echo "      • {$field}: {$error}\n";
            }
        } else {
            echo "  ✗ Invalid data incorrectly passed validation\n";
            $errors++;
        }
    } else {
        echo "  ⊘ Skipped (form not loaded)\n";
        $warnings++;
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 4: Process test submission (dry run - won't send Telegram)
echo "Test 4: Processing test form submission...\n";
try {
    if ($contactForm) {
        $testData = [
            'name' => 'Test User ' . time(),
            'phone' => '+12345678901',
            'email' => 'test' . time() . '@example.com',
            'service' => 'Тестовая заявка',
            'message' => 'This is a test submission from the Forms API test script.',
        ];
        
        $metadata = [
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Forms API Test Script',
        ];
        
        // Process without DB instance to skip Telegram
        $result = FormService::processSubmission($contactForm, $testData, $metadata, null);
        
        if ($result['success']) {
            echo "  ✓ Submission processed successfully\n";
            echo "    - Submission ID: {$result['submission_id']}\n";
            echo "    - Order ID: " . ($result['order_id'] ?? 'N/A') . "\n";
            
            // Verify submission in database
            $submission = FormSubmission::find($result['submission_id']);
            if ($submission) {
                echo "  ✓ Submission found in database\n";
                echo "    - Status: {$submission->status}\n";
                echo "    - Values count: " . $submission->values()->count() . "\n";
            } else {
                echo "  ✗ Submission not found in database\n";
                $errors++;
            }
            
            // Verify order if created
            if ($result['order_id']) {
                $order = Order::find($result['order_id']);
                if ($order) {
                    echo "  ✓ Order found in database\n";
                    echo "    - Order number: {$order->order_number}\n";
                    echo "    - Type: {$order->type}\n";
                } else {
                    echo "  ✗ Order not found in database\n";
                    $errors++;
                }
            }
        } else {
            echo "  ✗ Submission processing failed: {$result['error']}\n";
            $errors++;
        }
    } else {
        echo "  ⊘ Skipped (form not loaded)\n";
        $warnings++;
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 5: Check form models
echo "Test 5: Checking form models and relationships...\n";
try {
    $formsCount = Form::count();
    echo "  ✓ Forms in database: {$formsCount}\n";
    
    $submissionsCount = FormSubmission::count();
    echo "  ✓ Submissions in database: {$submissionsCount}\n";
    
    if ($submissionsCount > 0) {
        $recentSubmission = FormSubmission::with(['form', 'values'])->latest('submitted_at')->first();
        if ($recentSubmission) {
            echo "  ✓ Latest submission:\n";
            echo "    - ID: {$recentSubmission->id}\n";
            echo "    - Form: " . ($recentSubmission->form ? $recentSubmission->form->name : 'N/A') . "\n";
            echo "    - Status: {$recentSubmission->status}\n";
            echo "    - Values: " . $recentSubmission->values->count() . "\n";
        }
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 6: Field type validation
echo "Test 6: Testing field type validations...\n";
try {
    $testForm = [
        'id' => 999,
        'slug' => 'test',
        'name' => 'Test Form',
        'fields' => [
            [
                'id' => 1,
                'name' => 'test_email',
                'label' => 'Test Email',
                'type' => 'email',
                'required' => true,
                'validation_rules' => [],
            ],
            [
                'id' => 2,
                'name' => 'test_phone',
                'label' => 'Test Phone',
                'type' => 'phone',
                'required' => true,
                'validation_rules' => [],
            ],
            [
                'id' => 3,
                'name' => 'test_number',
                'label' => 'Test Number',
                'type' => 'number',
                'required' => false,
                'validation_rules' => ['min' => 10, 'max' => 100],
            ],
        ]
    ];
    
    // Test invalid email
    $validation = FormService::validateSubmission($testForm, [
        'test_email' => 'invalid-email',
        'test_phone' => '+1234567890',
    ]);
    if (!$validation['valid'] && isset($validation['errors']['test_email'])) {
        echo "  ✓ Email validation working\n";
    } else {
        echo "  ✗ Email validation not working\n";
        $errors++;
    }
    
    // Test invalid phone
    $validation = FormService::validateSubmission($testForm, [
        'test_email' => 'valid@email.com',
        'test_phone' => 'abc',
    ]);
    if (!$validation['valid'] && isset($validation['errors']['test_phone'])) {
        echo "  ✓ Phone validation working\n";
    } else {
        echo "  ✗ Phone validation not working\n";
        $errors++;
    }
    
    // Test number min/max
    $validation = FormService::validateSubmission($testForm, [
        'test_email' => 'valid@email.com',
        'test_phone' => '+1234567890',
        'test_number' => '5',
    ]);
    if (!$validation['valid'] && isset($validation['errors']['test_number'])) {
        echo "  ✓ Number min validation working\n";
    } else {
        echo "  ✗ Number min validation not working\n";
        $errors++;
    }
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Summary
echo "=================================================\n";
echo "Test Summary\n";
echo "=================================================\n";
echo "Errors: {$errors}\n";
echo "Warnings: {$warnings}\n";

if ($errors === 0) {
    echo "\n✓ All tests passed successfully!\n\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the output above.\n\n";
    exit(1);
}
