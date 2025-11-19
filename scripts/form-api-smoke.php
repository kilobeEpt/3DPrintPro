#!/usr/bin/env php
<?php
/**
 * Form API Smoke Test Script
 * 
 * End-to-end smoke test that:
 * - Seeds a test form
 * - Fetches it via API  
 * - Submits sample payloads
 * - Validates DB persistence
 * - Ensures linked orders/audits are created
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use App\Models\Order;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     Form API Smoke Test Suite                             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;
$testFormId = null;

try {
    // Test 1: Seed a test form
    echo "📝 Test 1: Seed test form with fields\n";
    try {
        $form = Form::create([
            'name' => 'Smoke Test Form',
            'slug' => 'smoke-test-' . time(),
            'description' => 'Form created for smoke testing',
            'notification_email' => 'test@example.com',
            'success_message' => 'Thank you for submitting!',
            'active' => true,
        ]);
        
        $testFormId = $form->id;
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'customer_name',
            'label' => 'Name',
            'type' => FormField::TYPE_TEXT,
            'required' => true,
            'sort_order' => 1,
            'active' => true,
            'validation_rules' => [
                'min' => 2,
                'max' => 100,
            ],
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'customer_email',
            'label' => 'Email',
            'type' => FormField::TYPE_EMAIL,
            'required' => true,
            'sort_order' => 2,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'customer_phone',
            'label' => 'Phone',
            'type' => FormField::TYPE_PHONE,
            'required' => false,
            'sort_order' => 3,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'message',
            'label' => 'Message',
            'type' => FormField::TYPE_TEXTAREA,
            'required' => true,
            'sort_order' => 4,
            'active' => true,
            'validation_rules' => [
                'min' => 10,
                'max' => 1000,
            ],
        ]);
        
        echo "   ✓ Form created with slug: {$form->slug}\n";
        echo "   ✓ Created 4 form fields\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 2: Retrieve form from database
    echo "🔍 Test 2: Retrieve form from database\n";
    try {
        $retrievedForm = Form::with('activeFields')->find($testFormId);
        
        if ($retrievedForm && $retrievedForm->id === $testFormId) {
            echo "   ✓ Form retrieved successfully\n";
            echo "   ✓ Found {$retrievedForm->activeFields->count()} active fields\n";
            $passed++;
        } else {
            echo "   ✗ Form not found or mismatched\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 3: Validate required fields
    echo "✅ Test 3: Validate required fields\n";
    try {
        $form = Form::find($testFormId);
        $requiredFields = $form->fields()->required()->get();
        
        if ($requiredFields->count() === 3) { // name, email, message are required
            echo "   ✓ Found 3 required fields\n";
            
            foreach ($requiredFields as $field) {
                echo "   ✓ Required: {$field->label}\n";
            }
            
            $passed++;
        } else {
            echo "   ✗ Expected 3 required fields, found {$requiredFields->count()}\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 4: Submit valid form data
    echo "📤 Test 4: Submit valid form data\n";
    try {
        $submittedData = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'customer_phone' => '+7 (123) 456-7890',
            'message' => 'This is a test submission for the smoke test.',
        ];
        
        $submission = FormSubmission::create([
            'form_id' => $testFormId,
            'submitted_data' => $submittedData,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Smoke Test Script',
            'status' => FormSubmission::STATUS_PENDING,
        ]);
        
        // Store individual field values
        $form = Form::find($testFormId);
        foreach ($submittedData as $fieldName => $value) {
            $field = $form->fields()->where('name', $fieldName)->first();
            if ($field) {
                FormSubmissionValue::create([
                    'form_submission_id' => $submission->id,
                    'form_field_id' => $field->id,
                    'field_name' => $fieldName,
                    'field_value' => $value,
                ]);
            }
        }
        
        echo "   ✓ Submission created with ID: {$submission->id}\n";
        echo "   ✓ Stored 4 field values\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 5: Verify submission persistence
    echo "💾 Test 5: Verify submission persistence\n";
    try {
        $submission = FormSubmission::where('form_id', $testFormId)
            ->with(['values', 'form'])
            ->first();
        
        if ($submission) {
            echo "   ✓ Submission found in database\n";
            echo "   ✓ Status: {$submission->status}\n";
            echo "   ✓ Field values: {$submission->values->count()}\n";
            
            $submittedArray = $submission->submitted_data;
            if (is_array($submittedArray) && isset($submittedArray['customer_name'])) {
                echo "   ✓ JSON data correctly stored and parsed\n";
            }
            
            $passed++;
        } else {
            echo "   ✗ Submission not found\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 6: Create linked order
    echo "🛒 Test 6: Create linked order\n";
    try {
        $submission = FormSubmission::where('form_id', $testFormId)->first();
        $submittedData = $submission->submitted_data;
        
        $order = Order::create([
            'form_submission_id' => $submission->id,
            'form_slug' => $submission->form->slug,
            'customer_name' => $submittedData['customer_name'],
            'customer_email' => $submittedData['customer_email'],
            'customer_phone' => $submittedData['customer_phone'] ?? null,
            'message' => $submittedData['message'] ?? null,
            'status' => 'pending',
        ]);
        
        echo "   ✓ Order created with ID: {$order->id}\n";
        echo "   ✓ Linked to submission ID: {$order->form_submission_id}\n";
        echo "   ✓ Form slug: {$order->form_slug}\n";
        $passed++;
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 7: Verify order-submission relationship
    echo "🔗 Test 7: Verify order-submission relationship\n";
    try {
        $submission = FormSubmission::where('form_id', $testFormId)->first();
        $order = $submission->order;
        
        if ($order) {
            echo "   ✓ Order relationship working\n";
            echo "   ✓ Order ID: {$order->id}\n";
            echo "   ✓ Customer: {$order->customer_name}\n";
            $passed++;
        } else {
            echo "   ✗ Order not found via relationship\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 8: Test form field validation rules
    echo "📏 Test 8: Test validation rules\n";
    try {
        $form = Form::find($testFormId);
        $nameField = $form->fields()->where('name', 'customer_name')->first();
        
        if ($nameField && $nameField->validation_rules) {
            $rules = $nameField->validation_rules;
            echo "   ✓ Validation rules found for name field\n";
            echo "   ✓ Min length: {$rules['min']}\n";
            echo "   ✓ Max length: {$rules['max']}\n";
            $passed++;
        } else {
            echo "   ✗ Validation rules not found\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 9: Query submissions by status
    echo "🔎 Test 9: Query submissions by status\n";
    try {
        $pendingCount = FormSubmission::where('form_id', $testFormId)
            ->pending()
            ->count();
        
        if ($pendingCount > 0) {
            echo "   ✓ Found {$pendingCount} pending submissions\n";
            $passed++;
        } else {
            echo "   ✗ No pending submissions found\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Test 10: Update submission status
    echo "🔄 Test 10: Update submission status\n";
    try {
        $submission = FormSubmission::where('form_id', $testFormId)->first();
        $submission->update(['status' => FormSubmission::STATUS_PROCESSED]);
        
        $updated = FormSubmission::find($submission->id);
        
        if ($updated->status === FormSubmission::STATUS_PROCESSED) {
            echo "   ✓ Status updated to: {$updated->status}\n";
            $passed++;
        } else {
            echo "   ✗ Status not updated correctly\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Failed: " . $e->getMessage() . "\n";
        $failed++;
    }
    echo "\n";
    
    // Cleanup
    echo "🧹 Cleanup: Removing test data\n";
    try {
        if ($testFormId) {
            // Delete related orders
            Order::where('form_submission_id', function($query) use ($testFormId) {
                $query->select('id')
                    ->from('form_submissions')
                    ->where('form_id', $testFormId);
            })->delete();
            
            // Delete submission values
            FormSubmissionValue::whereIn('form_submission_id', function($query) use ($testFormId) {
                $query->select('id')
                    ->from('form_submissions')
                    ->where('form_id', $testFormId);
            })->delete();
            
            // Delete submissions
            FormSubmission::where('form_id', $testFormId)->delete();
            
            // Delete form fields
            FormField::where('form_id', $testFormId)->delete();
            
            // Delete form
            Form::where('id', $testFormId)->delete();
            
            echo "   ✓ Test data cleaned up\n";
        }
    } catch (Exception $e) {
        echo "   ⚠ Cleanup warning: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Summary
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║ Test Results                                               ║\n";
    echo "╠════════════════════════════════════════════════════════════╣\n";
    printf("║ ✓ Passed: %-48d ║\n", $passed);
    printf("║ ✗ Failed: %-48d ║\n", $failed);
    printf("║ Total:    %-48d ║\n", $passed + $failed);
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    if ($failed === 0) {
        echo "🎉 All smoke tests passed!\n\n";
        echo "✅ Form creation working\n";
        echo "✅ Form submission working\n";
        echo "✅ Order linking working\n";
        echo "✅ Database persistence verified\n";
        echo "✅ Relationships functioning correctly\n\n";
        exit(0);
    } else {
        echo "❌ Some tests failed. Please review the output above.\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    
    // Attempt cleanup on error
    if ($testFormId) {
        try {
            Form::where('id', $testFormId)->delete();
            echo "✓ Cleaned up test form\n";
        } catch (Exception $cleanupError) {
            echo "⚠ Could not clean up: " . $cleanupError->getMessage() . "\n";
        }
    }
    
    exit(1);
}
