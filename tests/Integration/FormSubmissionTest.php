<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\FormSubmissionValue;
use App\Models\Order;

class FormSubmissionTest extends TestCase
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
    
    public function testCompleteFormSubmissionFlow()
    {
        $form = Form::create([
            'name' => 'Contact Form',
            'slug' => 'contact',
            'notification_email' => 'admin@example.com',
            'success_message' => 'Thank you for your message!',
            'active' => true,
        ]);
        
        $nameField = FormField::create([
            'form_id' => $form->id,
            'name' => 'name',
            'label' => 'Full Name',
            'type' => FormField::TYPE_TEXT,
            'required' => true,
            'sort_order' => 1,
            'active' => true,
        ]);
        
        $emailField = FormField::create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email Address',
            'type' => FormField::TYPE_EMAIL,
            'required' => true,
            'sort_order' => 2,
            'active' => true,
        ]);
        
        $messageField = FormField::create([
            'form_id' => $form->id,
            'name' => 'message',
            'label' => 'Message',
            'type' => FormField::TYPE_TEXTAREA,
            'required' => true,
            'sort_order' => 3,
            'active' => true,
        ]);
        
        $submittedData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message.',
        ];
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => $submittedData,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test',
            'status' => 'pending',
        ]);
        
        foreach ($submittedData as $fieldName => $value) {
            $field = FormField::where('form_id', $form->id)
                ->where('name', $fieldName)
                ->first();
            
            if ($field) {
                FormSubmissionValue::create([
                    'form_submission_id' => $submission->id,
                    'form_field_id' => $field->id,
                    'field_name' => $fieldName,
                    'field_value' => $value,
                ]);
            }
        }
        
        $this->assertNotNull($submission->id);
        $this->assertEquals('pending', $submission->status);
        $this->assertEquals('contact', $submission->form->slug);
        
        $values = FormSubmissionValue::where('form_submission_id', $submission->id)->get();
        $this->assertCount(3, $values);
    }
    
    public function testFormSubmissionWithLinkedOrder()
    {
        $form = Form::create([
            'name' => 'Order Form',
            'slug' => 'order',
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'customer_name',
            'label' => 'Name',
            'type' => FormField::TYPE_TEXT,
            'required' => true,
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'customer_email',
            'label' => 'Email',
            'type' => FormField::TYPE_EMAIL,
            'required' => true,
            'active' => true,
        ]);
        
        $submittedData = [
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
        ];
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => $submittedData,
            'ip_address' => '127.0.0.1',
            'status' => 'pending',
        ]);
        
        $order = Order::create([
            'form_submission_id' => $submission->id,
            'form_slug' => 'order',
            'customer_name' => $submittedData['customer_name'],
            'customer_email' => $submittedData['customer_email'],
            'status' => 'pending',
        ]);
        
        $this->assertNotNull($order->id);
        $this->assertEquals($submission->id, $order->form_submission_id);
        $this->assertEquals('order', $order->form_slug);
    }
    
    public function testMultipleSubmissionsForSameForm()
    {
        $form = Form::create([
            'name' => 'Newsletter Form',
            'slug' => 'newsletter',
            'active' => true,
        ]);
        
        FormField::create([
            'form_id' => $form->id,
            'name' => 'email',
            'label' => 'Email',
            'type' => FormField::TYPE_EMAIL,
            'required' => true,
            'active' => true,
        ]);
        
        $emails = ['user1@example.com', 'user2@example.com', 'user3@example.com'];
        
        foreach ($emails as $email) {
            FormSubmission::create([
                'form_id' => $form->id,
                'submitted_data' => ['email' => $email],
                'ip_address' => '127.0.0.1',
                'status' => 'pending',
            ]);
        }
        
        $submissions = $form->submissions;
        $this->assertCount(3, $submissions);
    }
    
    public function testSubmissionStatusUpdates()
    {
        $form = Form::create([
            'name' => 'Support Form',
            'slug' => 'support',
            'active' => true,
        ]);
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => ['message' => 'Help needed'],
            'status' => 'pending',
        ]);
        
        $this->assertEquals('pending', $submission->status);
        
        $submission->update(['status' => 'processing']);
        $this->assertEquals('processing', $submission->fresh()->status);
        
        $submission->update(['status' => 'completed']);
        $this->assertEquals('completed', $submission->fresh()->status);
    }
    
    public function testSubmissionValuesRelationships()
    {
        $form = Form::create([
            'name' => 'Feedback Form',
            'slug' => 'feedback',
            'active' => true,
        ]);
        
        $ratingField = FormField::create([
            'form_id' => $form->id,
            'name' => 'rating',
            'label' => 'Rating',
            'type' => FormField::TYPE_NUMBER,
            'active' => true,
        ]);
        
        $commentField = FormField::create([
            'form_id' => $form->id,
            'name' => 'comment',
            'label' => 'Comment',
            'type' => FormField::TYPE_TEXTAREA,
            'active' => true,
        ]);
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => ['rating' => 5, 'comment' => 'Great service!'],
            'status' => 'pending',
        ]);
        
        FormSubmissionValue::create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $ratingField->id,
            'field_name' => 'rating',
            'field_value' => '5',
        ]);
        
        FormSubmissionValue::create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $commentField->id,
            'field_name' => 'comment',
            'field_value' => 'Great service!',
        ]);
        
        $values = $submission->values;
        $this->assertCount(2, $values);
        
        $ratingValue = $values->where('form_field_id', $ratingField->id)->first();
        $this->assertEquals('5', $ratingValue->field_value);
        $this->assertEquals('rating', $ratingValue->field->name);
    }
    
    public function testSubmissionWithJsonData()
    {
        $form = Form::create([
            'name' => 'Calculator Form',
            'slug' => 'calculator',
            'active' => true,
        ]);
        
        $calculatorData = [
            'material' => 'PLA',
            'weight' => 100,
            'color' => 'red',
            'infill' => 20,
            'total_price' => 500,
        ];
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => [
                'customer_name' => 'Test User',
                'calculator_data' => $calculatorData,
            ],
            'status' => 'pending',
        ]);
        
        $retrieved = FormSubmission::find($submission->id);
        $this->assertIsArray($retrieved->submitted_data);
        $this->assertArrayHasKey('calculator_data', $retrieved->submitted_data);
        $this->assertEquals('PLA', $retrieved->submitted_data['calculator_data']['material']);
    }
    
    public function testBulkFormSubmissionProcessing()
    {
        $form = Form::create([
            'name' => 'Registration Form',
            'slug' => 'registration',
            'active' => true,
        ]);
        
        $field = FormField::create([
            'form_id' => $form->id,
            'name' => 'username',
            'label' => 'Username',
            'type' => FormField::TYPE_TEXT,
            'required' => true,
            'active' => true,
        ]);
        
        $submissions = [];
        for ($i = 1; $i <= 10; $i++) {
            $submission = FormSubmission::create([
                'form_id' => $form->id,
                'submitted_data' => ['username' => "user{$i}"],
                'status' => 'pending',
            ]);
            
            FormSubmissionValue::create([
                'form_submission_id' => $submission->id,
                'form_field_id' => $field->id,
                'field_name' => 'username',
                'field_value' => "user{$i}",
            ]);
            
            $submissions[] = $submission;
        }
        
        FormSubmission::where('form_id', $form->id)
            ->where('status', 'pending')
            ->update(['status' => 'processed']);
        
        $processedCount = FormSubmission::where('form_id', $form->id)
            ->where('status', 'processed')
            ->count();
        
        $this->assertEquals(10, $processedCount);
    }
    
    public function testFormSubmissionCascadeDelete()
    {
        $form = Form::create([
            'name' => 'Cascade Test',
            'slug' => 'cascade-test',
            'active' => true,
        ]);
        
        $field = FormField::create([
            'form_id' => $form->id,
            'name' => 'test_field',
            'label' => 'Test Field',
            'type' => FormField::TYPE_TEXT,
            'active' => true,
        ]);
        
        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'submitted_data' => ['test_field' => 'test value'],
            'status' => 'pending',
        ]);
        
        FormSubmissionValue::create([
            'form_submission_id' => $submission->id,
            'form_field_id' => $field->id,
            'field_name' => 'test_field',
            'field_value' => 'test value',
        ]);
        
        $submissionId = $submission->id;
        
        $submission->delete();
        
        $deletedSubmission = FormSubmission::find($submissionId);
        $this->assertNull($deletedSubmission);
    }
}
