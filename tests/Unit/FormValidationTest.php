<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Form;
use App\Models\FormField;

class FormValidationTest extends TestCase
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
    
    public function testCreateFormWithValidData()
    {
        $form = Form::create([
            'name' => 'Test Form',
            'slug' => 'test-form',
            'description' => 'A test form',
            'active' => true,
        ]);
        
        $this->assertNotNull($form->id);
        $this->assertEquals('Test Form', $form->name);
        $this->assertEquals('test-form', $form->slug);
        $this->assertTrue($form->active);
    }
    
    public function testFormWithJsonSettings()
    {
        $settings = [
            'theme' => 'dark',
            'show_labels' => true,
            'submit_text' => 'Send',
        ];
        
        $form = Form::create([
            'name' => 'Form with Settings',
            'slug' => 'form-with-settings',
            'settings' => $settings,
            'active' => true,
        ]);
        
        $retrieved = Form::find($form->id);
        $this->assertIsArray($retrieved->settings);
        $this->assertEquals('dark', $retrieved->settings['theme']);
        $this->assertTrue($retrieved->settings['show_labels']);
    }
    
    public function testFindFormBySlug()
    {
        Form::create([
            'name' => 'Slug Test Form',
            'slug' => 'slug-test',
            'active' => true,
        ]);
        
        $form = Form::bySlug('slug-test')->first();
        
        $this->assertNotNull($form);
        $this->assertEquals('Slug Test Form', $form->name);
    }
    
    public function testFormFieldCreation()
    {
        $form = Form::create([
            'name' => 'Contact Form',
            'slug' => 'contact',
            'active' => true,
        ]);
        
        $field = FormField::create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email Address',
            'type' => FormField::TYPE_EMAIL,
            'required' => true,
            'active' => true,
        ]);
        
        $this->assertNotNull($field->id);
        $this->assertEquals('email', $field->name);
        $this->assertEquals(FormField::TYPE_EMAIL, $field->type);
        $this->assertTrue($field->required);
    }
    
    public function testFormFieldValidationRules()
    {
        $form = Form::create([
            'name' => 'Test Form',
            'slug' => 'test',
            'active' => true,
        ]);
        
        $validationRules = [
            'min' => 3,
            'max' => 100,
            'pattern' => '^[a-zA-Z0-9]+$',
        ];
        
        $field = FormField::create([
            'form_id' => $form->id,
            'name' => 'username',
            'label' => 'Username',
            'type' => FormField::TYPE_TEXT,
            'validation_rules' => $validationRules,
            'required' => true,
            'active' => true,
        ]);
        
        $retrieved = FormField::find($field->id);
        $this->assertIsArray($retrieved->validation_rules);
        $this->assertEquals(3, $retrieved->validation_rules['min']);
        $this->assertEquals(100, $retrieved->validation_rules['max']);
    }
    
    public function testFormFieldOptions()
    {
        $form = Form::create([
            'name' => 'Survey Form',
            'slug' => 'survey',
            'active' => true,
        ]);
        
        $options = [
            ['value' => 'option1', 'label' => 'Option 1'],
            ['value' => 'option2', 'label' => 'Option 2'],
            ['value' => 'option3', 'label' => 'Option 3'],
        ];
        
        $field = FormField::create([
            'form_id' => $form->id,
            'name' => 'choice',
            'label' => 'Choose an option',
            'type' => FormField::TYPE_SELECT,
            'options' => $options,
            'required' => true,
            'active' => true,
        ]);
        
        $retrieved = FormField::find($field->id);
        $this->assertIsArray($retrieved->options);
        $this->assertCount(3, $retrieved->options);
        $this->assertEquals('option1', $retrieved->options[0]['value']);
    }
    
    public function testFormFieldTypes()
    {
        $types = [
            FormField::TYPE_TEXT,
            FormField::TYPE_EMAIL,
            FormField::TYPE_PHONE,
            FormField::TYPE_TEXTAREA,
            FormField::TYPE_NUMBER,
            FormField::TYPE_SELECT,
            FormField::TYPE_CHECKBOX,
            FormField::TYPE_RADIO,
            FormField::TYPE_FILE,
            FormField::TYPE_HIDDEN,
        ];
        
        $form = Form::create([
            'name' => 'All Types Form',
            'slug' => 'all-types',
            'active' => true,
        ]);
        
        foreach ($types as $type) {
            $field = FormField::create([
                'form_id' => $form->id,
                'name' => "field_{$type}",
                'label' => ucfirst($type) . ' Field',
                'type' => $type,
                'active' => true,
            ]);
            
            $this->assertEquals($type, $field->type);
        }
        
        $this->assertCount(count($types), $form->fields);
    }
    
    public function testFormFieldSortOrder()
    {
        $form = Form::create([
            'name' => 'Ordered Form',
            'slug' => 'ordered',
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'field3',
            'label' => 'Third Field',
            'type' => FormField::TYPE_TEXT,
            'sort_order' => 3,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'field1',
            'label' => 'First Field',
            'type' => FormField::TYPE_TEXT,
            'sort_order' => 1,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'field2',
            'label' => 'Second Field',
            'type' => FormField::TYPE_TEXT,
            'sort_order' => 2,
            'active' => true,
        ]);
        
        $fields = $form->activeFields()->get();
        
        $this->assertEquals('field1', $fields[0]->name);
        $this->assertEquals('field2', $fields[1]->name);
        $this->assertEquals('field3', $fields[2]->name);
    }
    
    public function testFormFieldScopeRequired()
    {
        $form = Form::create([
            'name' => 'Required Test',
            'slug' => 'required-test',
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'required_field',
            'label' => 'Required Field',
            'type' => FormField::TYPE_TEXT,
            'required' => true,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'optional_field',
            'label' => 'Optional Field',
            'type' => FormField::TYPE_TEXT,
            'required' => false,
            'active' => true,
        ]);
        
        $requiredFields = FormField::where('form_id', $form->id)->required()->get();
        
        $this->assertCount(1, $requiredFields);
        $this->assertEquals('required_field', $requiredFields[0]->name);
    }
    
    public function testFormFieldScopeByType()
    {
        $form = Form::create([
            'name' => 'Type Test',
            'slug' => 'type-test',
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'email1',
            'label' => 'Email 1',
            'type' => FormField::TYPE_EMAIL,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'email2',
            'label' => 'Email 2',
            'type' => FormField::TYPE_EMAIL,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'text1',
            'label' => 'Text 1',
            'type' => FormField::TYPE_TEXT,
            'active' => true,
        ]);
        
        $emailFields = FormField::where('form_id', $form->id)
            ->byType(FormField::TYPE_EMAIL)
            ->get();
        
        $this->assertCount(2, $emailFields);
    }
    
    public function testFormRelationships()
    {
        $form = Form::create([
            'name' => 'Relationship Test',
            'slug' => 'relationship-test',
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'field1',
            'label' => 'Field 1',
            'type' => FormField::TYPE_TEXT,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'field2',
            'label' => 'Field 2',
            'type' => FormField::TYPE_EMAIL,
            'active' => true,
        ]);
        
        $retrieved = Form::find($form->id);
        $this->assertCount(2, $retrieved->fields);
        
        $field = $retrieved->fields->first();
        $this->assertEquals($form->id, $field->form->id);
    }
    
    public function testFormActiveFieldsOnly()
    {
        $form = Form::create([
            'name' => 'Active Fields Test',
            'slug' => 'active-fields',
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'active_field',
            'label' => 'Active',
            'type' => FormField::TYPE_TEXT,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'inactive_field',
            'label' => 'Inactive',
            'type' => FormField::TYPE_TEXT,
            'active' => false,
        ]);
        
        $this->assertCount(2, $form->fields);
        $this->assertCount(1, $form->activeFields);
    }
    
    public function testValidateRequiredFields()
    {
        $form = Form::create([
            'name' => 'Validation Test',
            'slug' => 'validation-test',
            'active' => true,
        ]);
        
        $emailField = FormField::create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email',
            'type' => FormField::TYPE_EMAIL,
            'required' => true,
            'active' => true,
        ]);
        
        $nameField = FormField::create([
            'form_id' => $form->id,
            'name' => 'name',
            'label' => 'Name',
            'type' => FormField::TYPE_TEXT,
            'required' => true,
            'active' => true,
        ]);
        
        $submittedData = [
            'email' => 'test@example.com',
        ];
        
        $errors = [];
        $requiredFields = $form->fields()->required()->get();
        
        foreach ($requiredFields as $field) {
            if (!isset($submittedData[$field->name]) || empty($submittedData[$field->name])) {
                $errors[$field->name] = "{$field->label} is required";
            }
        }
        
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayNotHasKey('email', $errors);
    }
}
