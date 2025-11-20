<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OrderNote;
use App\Models\AdminUser;
use App\Models\FormSubmission;
use App\Services\OrderExportService;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Orders Flow Integration Tests
 * 
 * Tests end-to-end order workflows:
 * - Order creation from form submissions
 * - Status history tracking
 * - Internal notes management
 * - Order archiving
 * - Export service (CSV/PDF with signed URLs)
 * - Filtering and search
 */
class OrdersFlowTest extends TestCase
{
    protected $adminUser;
    protected $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        
        cleanTestData();
        
        // Create test admin user
        $this->adminUser = AdminUser::create([
            'email' => 'admin@test.com',
            'name' => 'Test Admin',
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $this->exportService = new OrderExportService();
    }

    protected function tearDown(): void
    {
        cleanTestData();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_order_with_unique_number()
    {
        $order1 = Order::create([
            'order_number' => 'ORD-20240101-ABC123',
            'type' => 'contact',
            'name' => 'John Doe',
            'phone' => '+1234567890',
            'email' => 'john@test.com',
            'message' => 'Test order',
            'status' => 'new'
        ]);
        
        $this->assertNotNull($order1->order_number);
        $this->assertEquals('new', $order1->status);
        
        // Duplicate order number should fail
        $this->expectException(\Exception::class);
        
        Order::create([
            'order_number' => 'ORD-20240101-ABC123', // Duplicate
            'type' => 'contact',
            'name' => 'Jane Doe',
            'phone' => '+0987654321'
        ]);
    }

    /** @test */
    public function it_tracks_status_changes_with_history()
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'type' => 'order',
            'name' => 'Customer',
            'phone' => '+1234567890',
            'status' => 'new'
        ]);
        
        // Log status change
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => 'new',
            'new_status' => 'processing',
            'changed_by' => $this->adminUser->id,
            'comment' => 'Starting to process order',
            'ip_address' => '127.0.0.1'
        ]);
        
        $order->status = 'processing';
        $order->save();
        
        // Verify history
        $history = OrderStatusHistory::where('order_id', $order->id)->get();
        $this->assertCount(1, $history);
        $this->assertEquals('new', $history->first()->old_status);
        $this->assertEquals('processing', $history->first()->new_status);
        $this->assertEquals($this->adminUser->id, $history->first()->changed_by);
    }

    /** @test */
    public function it_manages_internal_notes()
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-002',
            'type' => 'order',
            'name' => 'Customer',
            'phone' => '+1234567890',
            'status' => 'new'
        ]);
        
        // Add note
        $note = OrderNote::create([
            'order_id' => $order->id,
            'note' => 'Customer called to confirm details',
            'created_by' => $this->adminUser->id,
            'ip_address' => '127.0.0.1'
        ]);
        
        $this->assertNotNull($note->id);
        $this->assertEquals($order->id, $note->order_id);
        
        // Update note
        $note->note = 'Customer confirmed all details';
        $note->save();
        
        $updated = OrderNote::find($note->id);
        $this->assertEquals('Customer confirmed all details', $updated->note);
        
        // Load order with notes
        $orderWithNotes = Order::with('notes')->find($order->id);
        $this->assertCount(1, $orderWithNotes->notes);
    }

    /** @test */
    public function it_supports_order_archiving()
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-003',
            'type' => 'contact',
            'name' => 'Old Customer',
            'phone' => '+1234567890',
            'status' => 'completed'
        ]);
        
        // Archive order
        $order->archived_at = now();
        $order->save();
        
        $this->assertNotNull($order->archived_at);
        
        // Test active scope
        $activeOrders = Order::whereNull('archived_at')->get();
        $this->assertCount(0, $activeOrders);
        
        // Test archived scope
        $archivedOrders = Order::whereNotNull('archived_at')->get();
        $this->assertCount(1, $archivedOrders);
    }

    /** @test */
    public function it_links_orders_to_form_submissions()
    {
        // Create form submission first (requires forms table)
        Capsule::table('forms')->insert([
            'name' => 'Contact Form',
            'slug' => 'contact',
            'description' => 'Test form',
            'active' => 1
        ]);
        
        $formId = Capsule::table('forms')->where('slug', 'contact')->first()->id;
        
        $submission = FormSubmission::create([
            'form_id' => $formId,
            'submitted_data' => json_encode(['name' => 'Test', 'email' => 'test@test.com']),
            'ip_address' => '127.0.0.1',
            'status' => 'pending'
        ]);
        
        $order = Order::create([
            'order_number' => 'ORD-TEST-004',
            'type' => 'contact',
            'name' => 'Test User',
            'phone' => '+1234567890',
            'form_submission_id' => $submission->id,
            'form_slug' => 'contact',
            'status' => 'new'
        ]);
        
        $this->assertEquals($submission->id, $order->form_submission_id);
        $this->assertEquals('contact', $order->form_slug);
        
        // Load order with submission
        $orderWithSubmission = Order::with('formSubmission')->find($order->id);
        $this->assertNotNull($orderWithSubmission->formSubmission);
    }

    /** @test */
    public function it_stores_calculator_data_as_json()
    {
        $calculatorData = [
            'material' => 'PLA',
            'weight' => 50,
            'quality' => 'high',
            'totalCost' => 1250.00
        ];
        
        $order = Order::create([
            'order_number' => 'ORD-TEST-005',
            'type' => 'order',
            'name' => 'Customer',
            'phone' => '+1234567890',
            'calculator_data' => json_encode($calculatorData),
            'amount' => 1250.00,
            'status' => 'new'
        ]);
        
        $retrieved = Order::find($order->id);
        $this->assertEquals($calculatorData, json_decode($retrieved->calculator_data, true));
        $this->assertEquals(1250.00, $retrieved->amount);
    }

    /** @test */
    public function it_filters_orders_by_status()
    {
        Order::create(['order_number' => 'ORD-NEW-1', 'type' => 'order', 'name' => 'A', 'phone' => '123', 'status' => 'new']);
        Order::create(['order_number' => 'ORD-PROC-1', 'type' => 'order', 'name' => 'B', 'phone' => '123', 'status' => 'processing']);
        Order::create(['order_number' => 'ORD-COMP-1', 'type' => 'order', 'name' => 'C', 'phone' => '123', 'status' => 'completed']);
        
        $newOrders = Order::where('status', 'new')->get();
        $this->assertCount(1, $newOrders);
        
        $processingOrders = Order::where('status', 'processing')->get();
        $this->assertCount(1, $processingOrders);
    }

    /** @test */
    public function it_filters_orders_by_type()
    {
        Order::create(['order_number' => 'ORD-1', 'type' => 'order', 'name' => 'A', 'phone' => '123']);
        Order::create(['order_number' => 'ORD-2', 'type' => 'contact', 'name' => 'B', 'phone' => '123']);
        Order::create(['order_number' => 'ORD-3', 'type' => 'order', 'name' => 'C', 'phone' => '123']);
        
        $orders = Order::where('type', 'order')->get();
        $this->assertCount(2, $orders);
        
        $contacts = Order::where('type', 'contact')->get();
        $this->assertCount(1, $contacts);
    }

    /** @test */
    public function it_searches_orders_by_customer_info()
    {
        Order::create([
            'order_number' => 'ORD-SEARCH-1',
            'type' => 'order',
            'name' => 'John Smith',
            'phone' => '+1234567890',
            'email' => 'john@example.com'
        ]);
        
        Order::create([
            'order_number' => 'ORD-SEARCH-2',
            'type' => 'order',
            'name' => 'Jane Doe',
            'phone' => '+0987654321',
            'email' => 'jane@example.com'
        ]);
        
        // Search by name
        $results = Order::where('name', 'LIKE', '%John%')->get();
        $this->assertCount(1, $results);
        
        // Search by email
        $results = Order::where('email', 'LIKE', '%jane@%')->get();
        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_filters_orders_by_date_range()
    {
        $order1 = Order::create([
            'order_number' => 'ORD-OLD-1',
            'type' => 'order',
            'name' => 'Customer',
            'phone' => '123',
            'created_at' => '2024-01-01 10:00:00'
        ]);
        
        $order2 = Order::create([
            'order_number' => 'ORD-NEW-1',
            'type' => 'order',
            'name' => 'Customer',
            'phone' => '123',
            'created_at' => '2024-06-01 10:00:00'
        ]);
        
        $filtered = Order::where('created_at', '>=', '2024-05-01')->get();
        $this->assertCount(1, $filtered);
        $this->assertEquals('ORD-NEW-1', $filtered->first()->order_number);
    }

    /** @test */
    public function it_generates_csv_export_with_filters()
    {
        // Create test orders
        Order::create(['order_number' => 'ORD-1', 'type' => 'order', 'name' => 'John', 'phone' => '123', 'status' => 'new', 'amount' => 100]);
        Order::create(['order_number' => 'ORD-2', 'type' => 'order', 'name' => 'Jane', 'phone' => '456', 'status' => 'processing', 'amount' => 200]);
        
        $orders = Order::all()->toArray();
        
        $csv = $this->exportService->generateCSV($orders, [
            'order_number', 'name', 'phone', 'status', 'amount'
        ]);
        
        $this->assertStringContainsString('order_number', $csv);
        $this->assertStringContainsString('ORD-1', $csv);
        $this->assertStringContainsString('ORD-2', $csv);
        $this->assertStringContainsString('John', $csv);
        $this->assertStringContainsString('Jane', $csv);
    }

    /** @test */
    public function it_generates_signed_export_urls()
    {
        $exportId = 'test-export-123';
        $format = 'csv';
        
        $signedUrl = $this->exportService->generateSignedUrl($exportId, $format);
        
        $this->assertStringContainsString($exportId, $signedUrl);
        $this->assertStringContainsString('signature=', $signedUrl);
        $this->assertStringContainsString('expires=', $signedUrl);
        
        // Verify signature is valid
        $this->assertTrue($this->exportService->verifySignedUrl($signedUrl));
    }

    /** @test */
    public function it_rejects_expired_export_urls()
    {
        $exportId = 'test-export-expired';
        $format = 'csv';
        $expiresAt = time() - 3600; // 1 hour ago
        
        $signature = hash_hmac('sha256', $exportId . $format . $expiresAt, 'test-secret');
        $expiredUrl = "/api/orders/export.php?id={$exportId}&format={$format}&expires={$expiresAt}&signature={$signature}";
        
        $this->assertFalse($this->exportService->verifySignedUrl($expiredUrl));
    }

    /** @test */
    public function it_cascades_delete_order_with_history_and_notes()
    {
        $order = Order::create([
            'order_number' => 'ORD-CASCADE-1',
            'type' => 'order',
            'name' => 'Customer',
            'phone' => '123',
            'status' => 'new'
        ]);
        
        // Add history and notes
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => 'new',
            'new_status' => 'processing',
            'changed_by' => $this->adminUser->id
        ]);
        
        OrderNote::create([
            'order_id' => $order->id,
            'note' => 'Test note',
            'created_by' => $this->adminUser->id
        ]);
        
        $orderId = $order->id;
        
        // Delete order
        $order->delete();
        
        // Verify cascading delete
        $this->assertNull(Order::find($orderId));
        $this->assertCount(0, OrderStatusHistory::where('order_id', $orderId)->get());
        $this->assertCount(0, OrderNote::where('order_id', $orderId)->get());
    }
}
