#!/usr/bin/env php
<?php
/**
 * Seed Forms and Form Fields
 * 
 * This script populates the forms and form_fields tables with default data.
 * Uses the seed-data.php configuration file.
 * 
 * Usage:
 *   php scripts/seed-forms.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/eloquent.php';

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Capsule\Manager as Capsule;

echo "========================================\n";
echo "Forms Seeding Script\n";
echo "========================================\n\n";

try {
    // Load seed data
    $seedData = require __DIR__ . '/../database/seed-data.php';
    
    if (!isset($seedData['forms']) || !isset($seedData['form_fields'])) {
        throw new Exception("Seed data does not contain forms or form_fields definitions");
    }
    
    Capsule::transaction(function () use ($seedData) {
        echo "Seeding forms...\n";
        
        $formIdMap = [];
        
        foreach ($seedData['forms'] as $formData) {
            // Check if form already exists
            $existingForm = Form::where('slug', $formData['slug'])->first();
            
            if ($existingForm) {
                echo "  ⚠️  Form '{$formData['slug']}' already exists (ID: {$existingForm->id})\n";
                $formIdMap[$formData['slug']] = $existingForm->id;
                continue;
            }
            
            // Convert arrays to JSON for settings field
            if (isset($formData['settings']) && is_array($formData['settings'])) {
                $formData['settings'] = $formData['settings'];
            }
            
            $form = Form::create($formData);
            $formIdMap[$formData['slug']] = $form->id;
            
            echo "  ✓ Created form '{$formData['slug']}' (ID: {$form->id})\n";
        }
        
        echo "\nSeeding form fields...\n";
        
        $fieldCount = 0;
        
        foreach ($seedData['form_fields'] as $fieldData) {
            // Resolve form_id from form_slug
            if (!isset($fieldData['form_slug'])) {
                echo "  ✗ Field '{$fieldData['name']}' missing form_slug\n";
                continue;
            }
            
            $formSlug = $fieldData['form_slug'];
            unset($fieldData['form_slug']);
            
            if (!isset($formIdMap[$formSlug])) {
                echo "  ✗ Form '{$formSlug}' not found for field '{$fieldData['name']}'\n";
                continue;
            }
            
            $fieldData['form_id'] = $formIdMap[$formSlug];
            
            // Check if field already exists
            $existingField = FormField::where('form_id', $fieldData['form_id'])
                ->where('name', $fieldData['name'])
                ->first();
            
            if ($existingField) {
                echo "  ⚠️  Field '{$fieldData['name']}' already exists in form '{$formSlug}'\n";
                continue;
            }
            
            FormField::create($fieldData);
            $fieldCount++;
            
            echo "  ✓ Created field '{$fieldData['name']}' in form '{$formSlug}'\n";
        }
        
        echo "\n========================================\n";
        echo "Seeding Complete\n";
        echo "========================================\n";
        echo "Forms created/found: " . count($formIdMap) . "\n";
        echo "Fields created: {$fieldCount}\n\n";
    });
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
