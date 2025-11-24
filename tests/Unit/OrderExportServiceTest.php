<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\OrderExportService;
use App\Models\Order;
use Illuminate\Database\Capsule\Manager as Capsule;

class OrderExportServiceTest extends TestCase
{
    protected $service;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../../bootstrap/eloquent.php';
        
        $this->service = new OrderExportService();
        
        Capsule::table('orders')->truncate();
    }
    
    public function testServiceInstantiates()
    {
        $this->assertInstanceOf(OrderExportService::class, $this->service);
    }
    
    public function testGetAvailableFields()
    {
        $fields = $this->service->getAvailableFields();
        
        $this->assertIsArray($fields);
        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('order_number', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('status', $fields);
    }
    
    public function testExportCsvWithNoOrders()
    {
        $csv = $this->service->exportCsv();
        
        $this->assertIsString($csv);
        $this->assertStringContainsString('Order Number', $csv);
    }
    
    public function testExportCsvWithOrders()
    {
        Order::create([
            'order_number' => 'TEST-CSV-001',
            'type' => 'order',
            'name' => 'CSV Test Customer',
            'phone' => '+79991234567',
            'email' => 'csv@test.com',
            'status' => 'new',
            'amount' => 500.00,
        ]);
        
        $csv = $this->service->exportCsv();
        
        $this->assertStringContainsString('TEST-CSV-001', $csv);
        $this->assertStringContainsString('CSV Test Customer', $csv);
    }
    
    public function testExportCsvWithFilters()
    {
        Order::create([
            'order_number' => 'TEST-NEW-001',
            'type' => 'order',
            'name' => 'New Order',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        Order::create([
            'order_number' => 'TEST-COMPLETED-001',
            'type' => 'order',
            'name' => 'Completed Order',
            'phone' => '+79991234568',
            'status' => 'completed',
        ]);
        
        $csv = $this->service->exportCsv(['status' => 'new']);
        
        $this->assertStringContainsString('TEST-NEW-001', $csv);
        $this->assertStringNotContainsString('TEST-COMPLETED-001', $csv);
    }
    
    public function testExportCsvWithCustomFields()
    {
        Order::create([
            'order_number' => 'TEST-FIELDS-001',
            'type' => 'order',
            'name' => 'Fields Test',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        $fields = ['order_number', 'name'];
        $csv = $this->service->exportCsv([], $fields);
        
        $lines = explode("\n", trim($csv));
        $headers = str_getcsv($lines[0]);
        
        $this->assertCount(2, $headers);
        $this->assertEquals('Order Number', $headers[0]);
        $this->assertEquals('Name', $headers[1]);
    }
    
    public function testGenerateSignedUrl()
    {
        $result = $this->service->generateSignedUrl('csv', [], null, 60);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('expires_at', $result);
        $this->assertStringContainsString('token=', $result['url']);
        $this->assertStringContainsString('sig=', $result['url']);
    }
    
    public function testValidateSignedUrlSuccess()
    {
        $signedUrl = $this->service->generateSignedUrl('csv', ['status' => 'new'], ['id', 'name'], 60);
        
        $urlParts = parse_url($signedUrl['url']);
        parse_str($urlParts['query'], $params);
        
        $validation = $this->service->validateSignedUrl($params['token'], $params['sig']);
        
        $this->assertTrue($validation['valid']);
        $this->assertEquals('csv', $validation['type']);
        $this->assertEquals(['status' => 'new'], $validation['filters']);
        $this->assertEquals(['id', 'name'], $validation['fields']);
    }
    
    public function testValidateSignedUrlInvalidSignature()
    {
        $signedUrl = $this->service->generateSignedUrl('csv', [], null, 60);
        
        $urlParts = parse_url($signedUrl['url']);
        parse_str($urlParts['query'], $params);
        
        $validation = $this->service->validateSignedUrl($params['token'], 'invalid-signature');
        
        $this->assertFalse($validation['valid']);
        $this->assertEquals('Invalid signature', $validation['error']);
    }
    
    public function testValidateSignedUrlExpired()
    {
        $data = [
            'type' => 'csv',
            'filters' => [],
            'fields' => null,
            'expires' => time() - 3600,
        ];
        
        $payload = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $payload, $this->getSecretKey());
        
        $validation = $this->service->validateSignedUrl($payload, $signature);
        
        $this->assertFalse($validation['valid']);
        $this->assertEquals('Token expired', $validation['error']);
    }
    
    public function testExportCsvWithDateRange()
    {
        $order1 = Order::create([
            'order_number' => 'TEST-DATE-001',
            'type' => 'order',
            'name' => 'Old Order',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        sleep(1);
        
        $order2 = Order::create([
            'order_number' => 'TEST-DATE-002',
            'type' => 'order',
            'name' => 'New Order',
            'phone' => '+79991234568',
            'status' => 'new',
        ]);
        
        $from = $order2->created_at->subSeconds(5)->toDateTimeString();
        
        $csv = $this->service->exportCsv([
            'date_from' => $from,
        ]);
        
        $this->assertStringContainsString('TEST-DATE-002', $csv);
    }
    
    public function testExportCsvWithSearch()
    {
        Order::create([
            'order_number' => 'TEST-SEARCH-001',
            'type' => 'order',
            'name' => 'John Doe',
            'phone' => '+79991234567',
            'email' => 'john@example.com',
            'status' => 'new',
        ]);
        
        Order::create([
            'order_number' => 'TEST-SEARCH-002',
            'type' => 'order',
            'name' => 'Jane Smith',
            'phone' => '+79991234568',
            'email' => 'jane@example.com',
            'status' => 'new',
        ]);
        
        $csv = $this->service->exportCsv(['search' => 'John']);
        
        $this->assertStringContainsString('John Doe', $csv);
        $this->assertStringNotContainsString('Jane Smith', $csv);
    }
    
    public function testExportCsvWithLimit()
    {
        for ($i = 1; $i <= 5; $i++) {
            Order::create([
                'order_number' => "TEST-LIMIT-{$i}",
                'type' => 'order',
                'name' => "Customer {$i}",
                'phone' => '+7999123456' . $i,
                'status' => 'new',
            ]);
        }
        
        $csv = $this->service->exportCsv(['limit' => 3]);
        
        $lines = explode("\n", trim($csv));
        
        $this->assertLessThanOrEqual(4, count($lines));
    }
    
    protected function getSecretKey()
    {
        return $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? 'fallback-secret-key';
    }
}
