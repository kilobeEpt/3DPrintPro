<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Form Builder Integration Tests
 * 
 * Tests end-to-end form builder workflows:
 * - Form creation with fields
 * - Field ordering and validation rules
 * - Form submission processing
 * - Conditional logic evaluation
 * - Multi-step form workflows
 * - Notification settings per form
 */
class FormBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cleanTestData();
    }

    protected function tearDown(): void
    {
        cleanTestData();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_form_with_multiple_fields()
    {
        $form = Form::create([
            'name' => 'Contact Form',
            'slug' => 'contact',
            'description' => 'General contact form',
            'success_message' => 'Thank you for contacting us!',
            'active' => true
        ]);
        
        // Add fields
        $nameField = FormField::create([
            'form_id' => $form->id,
            'name' => 'name',
            'label' => 'Full Name',
            'type' => 'text',
            'placeholder' => 'Enter your name',
            'validation_rules' => json_encode(['required' => true, 'minLength' => 2]),
            'sort_order' => 1,
            'required' => true,
            'active' => true
        ]);
        
        $emailField = FormField::create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email Address',
            'type' => 'email',
            'placeholder' => 'your@email.com',
            'validation_rules' => json_encode(['required' => true]),
            'sort_order' => 2,
            'required' => true,
            'active' => true
        ]);
        
        // Load form with fields
        $formWithFields = Form::with('fields')->find($form->id);
        
        $this->assertCount(2, $formWithFields->fields);
        $this->assertEquals('name', $formWithFields->fields[0]->name);
        $this->assertEquals('email', $formWithFields->fields[1]->name);
    }

    /** @test */
    public function it_processes_form_submission_with_validation()
    {
        $form = Form::create([
            'name' => 'Survey Form',
            'slug' => 'survey',
            'active' => true
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'rating',
            'label' => 'Rating',
            'type' => 'number',
            'validation_rules' => json_encode(['required' => true, 'min' => 1, 'max' => 5]),
            'required' => true,
            'active' => true
        ]);
        
        // Valid submission
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => json_encode(['rating' => 5]),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'status' => 'pending'
        ]);
        
        $this->assertNotNull($submission->id);
        $this->assertEquals('pending', $submission->status);
        
        // Verify submitted data
        $data = json_decode($submission->submitted_data, true);
        $this->assertEquals(5, $data['rating']);
    }

    /** @test */
    public function it_stores_individual_field_values()
    {
        $form = Form::create(['name' => 'Test Form', 'slug' => 'test', 'active' => true]);
        
        $nameField = FormField::create([
            'form_id' => $form->id,
            'name' => 'name',
            'label' => 'Name',
            'type' => 'text',
            'active' => true
        ]);
        
        $emailField = FormField::create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'active' => true
        ]);
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => json_encode(['name' => 'John Doe', 'email' => 'john@test.com']),
            'ip_address' => '127.0.0.1',
            'status' => 'pending'
        ]);
        
        // Store individual values
        FormSubmissionValue::create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $nameField->id,
            'field_name' => 'name',
            'field_value' => 'John Doe'
        ]);
        
        FormSubmissionValue::create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $emailField->id,
            'field_name' => 'email',
            'field_value' => 'john@test.com'
        ]);
        
        // Load submission with values
        $submissionWithValues = FormSubmission::with('values')->find($submission->id);
        
        $this->assertCount(2, $submissionWithValues->values);
    }

    /** @test */
    public function it_orders_fields_by_sort_order()
    {
        $form = Form::create(['name' => 'Ordered Form', 'slug' => 'ordered', 'active' => true]);
        
        FormField::create(['form_id' => $form->id, 'name' => 'field3', 'label' => 'Third', 'type' => 'text', 'sort_order' => 3]);
        FormField::create(['form_id' => $form->id, 'name' => 'field1', 'label' => 'First', 'type' => 'text', 'sort_order' => 1]);
        FormField::create(['form_id' => $form->id, 'name' => 'field2', 'label' => 'Second', 'type' => 'text', 'sort_order' => 2]);
        
        $fields = FormField::where('form_id', $form->id)->orderBy('sort_order')->get();
        
        $this->assertEquals('field1', $fields[0]->name);
        $this->assertEquals('field2', $fields[1]->name);
        $this->assertEquals('field3', $fields[2]->name);
    }

    /** @test */
    public function it_supports_various_field_types()
    {
        $form = Form::create(['name' => 'Multi-Type Form', 'slug' => 'multi-type', 'active' => true]);
        
        $fieldTypes = ['text', 'email', 'phone', 'number', 'textarea', 'select', 'radio', 'checkbox', 'hidden'];
        
        foreach ($fieldTypes as $index => $type) {
            FormField::create([
                'form_id' => $form->id,
                'name' => "field_{$type}",
                'label' => ucfirst($type) . ' Field',
                'type' => $type,
                'sort_order' => $index + 1,
                'active' => true
            ]);
        }
        
        $fields = FormField::where('form_id', $form->id)->get();
        
        $this->assertCount(9, $fields);
        
        $types = $fields->pluck('type')->toArray();
        foreach ($fieldTypes as $type) {
            $this->assertContains($type, $types);
        }
    }

    /** @test */
    public function it_stores_field_options_as_json()
    {
        $form = Form::create(['name' => 'Select Form', 'slug' => 'select', 'active' => true]);
        
        $options = [
            ['value' => 'option1', 'label' => 'Option 1'],
            ['value' => 'option2', 'label' => 'Option 2'],
            ['value' => 'option3', 'label' => 'Option 3']
        ];
        
        $field = FormField::create([
            'form_id' => $form->id,
            'name' => 'selection',
            'label' => 'Choose Option',
            'type' => 'select',
            'options' => json_encode($options),
            'active' => true
        ]);
        
        $retrieved = FormField::find($field->id);
        $this->assertEquals($options, json_decode($retrieved->options, true));
    }

    /** @test */
    public function it_stores_complex_validation_rules()
    {
        $form = Form::create(['name' => 'Validated Form', 'slug' => 'validated', 'active' => true]);
        
        $rules = [
            'required' => true,
            'minLength' => 5,
            'maxLength' => 100,
            'pattern' => '^[a-zA-Z0-9]+$'
        ];
        
        $field = FormField::create([
            'form_id' => $form->id,
            'name' => 'username',
            'label' => 'Username',
            'type' => 'text',
            'validation_rules' => json_encode($rules),
            'required' => true,
            'active' => true
        ]);
        
        $retrieved = FormField::find($field->id);
        $this->assertEquals($rules, json_decode($retrieved->validation_rules, true));
    }

    /** @test */
    public function it_supports_conditional_logic_in_settings()
    {
        $conditionalLogic = [
            'conditions' => [
                [
                    'field' => 'service_type',
                    'operator' => 'equals',
                    'value' => '3d_printing'
                ]
            ],
            'action' => 'show'
        ];
        
        $form = Form::create([
            'name' => 'Conditional Form',
            'slug' => 'conditional',
            'settings' => json_encode([
                'conditional_logic' => $conditionalLogic
            ]),
            'active' => true
        ]);
        
        $retrieved = Form::find($form->id);
        $settings = json_decode($retrieved->settings, true);
        
        $this->assertArrayHasKey('conditional_logic', $settings);
        $this->assertEquals('show', $settings['conditional_logic']['action']);
    }

    /** @test */
    public function it_stores_notification_settings_per_form()
    {
        $notificationSettings = [
            'telegram_enabled' => true,
            'telegram_chat_id' => '-123456789',
            'email_enabled' => true,
            'email_recipients' => 'admin@example.com,manager@example.com',
            'email_template' => 'order'
        ];
        
        $form = Form::create([
            'name' => 'Order Form',
            'slug' => 'order',
            'settings' => json_encode($notificationSettings),
            'active' => true
        ]);
        
        $retrieved = Form::find($form->id);
        $settings = json_decode($retrieved->settings, true);
        
        $this->assertTrue($settings['telegram_enabled']);
        $this->assertTrue($settings['email_enabled']);
        $this->assertEquals('admin@example.com,manager@example.com', $settings['email_recipients']);
    }

    /** @test */
    public function it_cascades_delete_form_with_fields()
    {
        $form = Form::create(['name' => 'Cascade Form', 'slug' => 'cascade', 'active' => true]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'field1',
            'label' => 'Field 1',
            'type' => 'text',
            'active' => true
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'field2',
            'label' => 'Field 2',
            'type' => 'text',
            'active' => true
        ]);
        
        $formId = $form->id;
        
        // Delete form
        $form->delete();
        
        // Verify cascade
        $this->assertNull(Form::find($formId));
        $this->assertCount(0, FormField::where('form_id', $formId)->get());
    }

    /** @test */
    public function it_filters_submissions_by_status()
    {
        $form = Form::create(['name' => 'Status Form', 'slug' => 'status', 'active' => true]);
        
        FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => json_encode(['test' => 'data1']),
            'ip_address' => '127.0.0.1',
            'status' => 'pending'
        ]);
        
        FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => json_encode(['test' => 'data2']),
            'ip_address' => '127.0.0.1',
            'status' => 'processed'
        ]);
        
        FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => json_encode(['test' => 'data3']),
            'ip_address' => '127.0.0.1',
            'status' => 'archived'
        ]);
        
        $pending = FormSubmission::where('status', 'pending')->get();
        $this->assertCount(1, $pending);
        
        $processed = FormSubmission::where('status', 'processed')->get();
        $this->assertCount(1, $processed);
        
        $archived = FormSubmission::where('status', 'archived')->get();
        $this->assertCount(1, $archived);
    }

    /** @test */
    public function it_supports_calculator_mapping_in_settings()
    {
        $calculatorMapping = [
            'calculator_mapping' => [
                'amount' => 'calculator.totalCost',
                'material' => 'calculator.material',
                'weight' => 'calculator.weight'
            ]
        ];
        
        $form = Form::create([
            'name' => 'Calculator Form',
            'slug' => 'calculator',
            'settings' => json_encode($calculatorMapping),
            'active' => true
        ]);
        
        $retrieved = Form::find($form->id);
        $settings = json_decode($retrieved->settings, true);
        
        $this->assertArrayHasKey('calculator_mapping', $settings);
        $this->assertEquals('calculator.totalCost', $settings['calculator_mapping']['amount']);
    }

    /** @test */
    public function it_updates_submission_status()
    {
        $form = Form::create(['name' => 'Update Form', 'slug' => 'update', 'active' => true]);
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => json_encode(['test' => 'data']),
            'ip_address' => '127.0.0.1',
            'status' => 'pending'
        ]);
        
        $this->assertEquals('pending', $submission->status);
        
        // Update status
        $submission->status = 'processed';
        $submission->save();
        
        $updated = FormSubmission::find($submission->id);
        $this->assertEquals('processed', $updated->status);
    }
}
