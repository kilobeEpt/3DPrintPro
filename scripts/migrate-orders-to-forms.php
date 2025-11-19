#!/usr/bin/env php
<?php
/**
 * Migrate Existing Orders to Forms System
 * 
 * This script migrates existing order data to the new forms system by:
 * 1. Creating form submissions for each existing order
 * 2. Populating form_submission_values with individual field data
 * 3. Linking orders back to form submissions
 * 
 * IMPORTANT: This is a ONE-TIME migration script. Run it after:
 * - The new schema has been applied
 * - Forms and form_fields have been seeded
 * 
 * Usage:
 *   php scripts/migrate-orders-to-forms.php [--dry-run] [--limit=N]
 * 
 * Options:
 *   --dry-run    Show what would be migrated without making changes
 *   --limit=N    Only migrate N orders (for testing)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Order;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use Illuminate\Database\Capsule\Manager as Capsule;

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv);
$limit = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int)substr($arg, 8);
    }
}

echo "========================================\n";
echo "Order to Forms Migration Script\n";
echo "========================================\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No changes will be made\n\n";
}

if ($limit) {
    echo "ℹ️  Limiting migration to {$limit} orders\n\n";
}

try {
    // Get forms by slug
    $contactForm = Form::where('slug', 'contact')->first();
    $orderForm = Form::where('slug', 'order')->first();
    
    if (!$contactForm || !$orderForm) {
        throw new Exception("Required forms not found. Please run seed data first.");
    }
    
    echo "✓ Found forms:\n";
    echo "  - Contact form (ID: {$contactForm->id})\n";
    echo "  - Order form (ID: {$orderForm->id})\n\n";
    
    // Get form fields for mapping
    $contactFields = FormField::where('form_id', $contactForm->id)->get()->keyBy('name');
    $orderFields = FormField::where('form_id', $orderForm->id)->get()->keyBy('name');
    
    echo "✓ Loaded form fields:\n";
    echo "  - Contact form: {$contactFields->count()} fields\n";
    echo "  - Order form: {$orderFields->count()} fields\n\n";
    
    // Get orders that haven't been migrated yet
    $query = Order::whereNull('form_submission_id');
    
    if ($limit) {
        $query->limit($limit);
    }
    
    $orders = $query->get();
    $totalOrders = $orders->count();
    
    if ($totalOrders === 0) {
        echo "✓ No orders to migrate. All orders are already linked to form submissions.\n\n";
        exit(0);
    }
    
    echo "Found {$totalOrders} orders to migrate\n\n";
    
    $migratedCount = 0;
    $errorCount = 0;
    
    Capsule::transaction(function () use ($orders, $contactForm, $orderForm, $contactFields, $orderFields, $dryRun, &$migratedCount, &$errorCount) {
        foreach ($orders as $order) {
            try {
                echo "Processing order #{$order->id} ({$order->order_number})... ";
                
                // Determine which form to use based on order type
                $form = $order->type === 'order' ? $orderForm : $contactForm;
                $fields = $order->type === 'order' ? $orderFields : $contactFields;
                
                // Build submitted data from order
                $submittedData = [
                    'name' => $order->name,
                    'phone' => $order->phone,
                ];
                
                if ($order->email) {
                    $submittedData['email'] = $order->email;
                }
                
                if ($order->telegram) {
                    $submittedData['telegram'] = $order->telegram;
                }
                
                if ($order->type === 'order') {
                    if ($order->service) {
                        $submittedData['service'] = $order->service;
                    }
                    if ($order->message) {
                        $submittedData['message'] = $order->message;
                    }
                    if ($order->calculator_data) {
                        $submittedData['calculator_data'] = $order->calculator_data;
                    }
                } else {
                    if ($order->subject) {
                        $submittedData['subject'] = $order->subject;
                    }
                    if ($order->message) {
                        $submittedData['message'] = $order->message;
                    }
                }
                
                if (!$dryRun) {
                    // Create form submission
                    $submission = FormSubmission::create([
                        'form_id' => $form->id,
                        'form_slug' => $form->slug,
                        'submitted_data' => $submittedData,
                        'status' => 'processed',
                        'ip_address' => null,
                        'user_agent' => 'migration_script',
                        'submitted_at' => $order->created_at,
                        'created_at' => $order->created_at,
                        'updated_at' => $order->updated_at,
                    ]);
                    
                    // Create individual field values
                    foreach ($submittedData as $fieldName => $fieldValue) {
                        // Skip calculator_data for individual values
                        if ($fieldName === 'calculator_data') {
                            continue;
                        }
                        
                        $field = $fields->get($fieldName);
                        
                        FormSubmissionValue::create([
                            'form_submission_id' => $submission->id,
                            'form_field_id' => $field ? $field->id : null,
                            'field_name' => $fieldName,
                            'field_value' => is_array($fieldValue) ? json_encode($fieldValue) : $fieldValue,
                            'created_at' => $order->created_at,
                            'updated_at' => $order->updated_at,
                        ]);
                    }
                    
                    // Link order to submission
                    $order->form_submission_id = $submission->id;
                    $order->form_slug = $form->slug;
                    $order->save();
                }
                
                echo "✓\n";
                $migratedCount++;
                
            } catch (Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    });
    
    echo "\n========================================\n";
    echo "Migration Complete\n";
    echo "========================================\n";
    echo "Successfully migrated: {$migratedCount} orders\n";
    if ($errorCount > 0) {
        echo "Errors: {$errorCount}\n";
    }
    
    if ($dryRun) {
        echo "\n⚠️  This was a DRY RUN - no changes were made\n";
        echo "Run without --dry-run to perform the actual migration.\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "\n✗ Fatal Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
