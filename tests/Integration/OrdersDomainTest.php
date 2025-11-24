<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OrderNote;
use App\Models\AdminUser;
use Illuminate\Database\Capsule\Manager as Capsule;

class OrdersDomainTest extends TestCase
{
    protected static $db;
    
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../../bootstrap/eloquent.php';
        
        self::$db = Capsule::connection()->getPdo();
        
        self::$db->exec("DROP TABLE IF EXISTS order_notes");
        self::$db->exec("DROP TABLE IF EXISTS order_status_history");
        self::$db->exec("ALTER TABLE orders DROP COLUMN IF EXISTS archived_at");
        
        self::$db->exec("
            CREATE TABLE IF NOT EXISTS order_status_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                old_status VARCHAR(50) NULL,
                new_status VARCHAR(50) NOT NULL,
                changed_by INTEGER NULL,
                comment TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        self::$db->exec("
            CREATE TABLE IF NOT EXISTS order_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                note TEXT NOT NULL,
                created_by INTEGER NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        self::$db->exec("ALTER TABLE orders ADD COLUMN archived_at TIMESTAMP NULL");
    }
    
    protected function setUp(): void
    {
        Capsule::table('order_notes')->truncate();
        Capsule::table('order_status_history')->truncate();
        Capsule::table('orders')->truncate();
    }
    
    public function testOrderStatusHistoryRelationship()
    {
        $order = Order::create([
            'order_number' => 'TEST-001',
            'type' => 'order',
            'name' => 'Test Customer',
            'phone' => '+79991234567',
            'email' => 'test@example.com',
            'status' => 'new',
        ]);
        
        OrderStatusHistory::logStatusChange(
            $order->id,
            null,
            'new',
            null,
            'Initial status'
        );
        
        $history = $order->statusHistory;
        
        $this->assertCount(1, $history);
        $this->assertEquals('new', $history[0]->new_status);
        $this->assertNull($history[0]->old_status);
        $this->assertEquals('Initial status', $history[0]->comment);
    }
    
    public function testOrderNotesRelationship()
    {
        $order = Order::create([
            'order_number' => 'TEST-002',
            'type' => 'order',
            'name' => 'Test Customer',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        OrderNote::addNote($order->id, 'First note');
        OrderNote::addNote($order->id, 'Second note');
        
        $notes = $order->notes;
        
        $this->assertCount(2, $notes);
        $this->assertEquals('Second note', $notes[0]->note);
        $this->assertEquals('First note', $notes[1]->note);
    }
    
    public function testStatusChangeTracking()
    {
        $admin = \seedTestData(1, 'admin_users', [
            'email' => 'admin@example.com',
            'name' => 'Admin User',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
        ])[0];
        
        $order = Order::create([
            'order_number' => 'TEST-003',
            'type' => 'order',
            'name' => 'Test Customer',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        OrderStatusHistory::logStatusChange($order->id, null, 'new', null, 'Initial');
        
        $order->status = 'processing';
        $order->save();
        
        OrderStatusHistory::logStatusChange(
            $order->id,
            'new',
            'processing',
            $admin['id'],
            'Started processing'
        );
        
        $history = $order->statusHistory;
        
        $this->assertCount(2, $history);
        $this->assertEquals('new', $history[0]->new_status);
        $this->assertEquals('processing', $history[1]->new_status);
        $this->assertEquals('new', $history[1]->old_status);
        $this->assertEquals($admin['id'], $history[1]->changed_by);
    }
    
    public function testOrderArchiving()
    {
        $order = Order::create([
            'order_number' => 'TEST-004',
            'type' => 'order',
            'name' => 'Test Customer',
            'phone' => '+79991234567',
            'status' => 'completed',
        ]);
        
        $this->assertFalse($order->isArchived());
        
        $order->archive();
        
        $this->assertTrue($order->isArchived());
        $this->assertNotNull($order->archived_at);
        
        $order->unarchive();
        
        $this->assertFalse($order->isArchived());
        $this->assertNull($order->archived_at);
    }
    
    public function testOrderActiveScope()
    {
        Order::create([
            'order_number' => 'TEST-005',
            'type' => 'order',
            'name' => 'Active Order',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        $archived = Order::create([
            'order_number' => 'TEST-006',
            'type' => 'order',
            'name' => 'Archived Order',
            'phone' => '+79991234568',
            'status' => 'completed',
        ]);
        $archived->archive();
        
        $activeOrders = Order::active()->get();
        $archivedOrders = Order::archived()->get();
        
        $this->assertCount(1, $activeOrders);
        $this->assertCount(1, $archivedOrders);
        $this->assertEquals('TEST-005', $activeOrders[0]->order_number);
        $this->assertEquals('TEST-006', $archivedOrders[0]->order_number);
    }
    
    public function testOrderDateRangeScope()
    {
        $order1 = Order::create([
            'order_number' => 'TEST-007',
            'type' => 'order',
            'name' => 'Customer 1',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        sleep(1);
        
        $order2 = Order::create([
            'order_number' => 'TEST-008',
            'type' => 'order',
            'name' => 'Customer 2',
            'phone' => '+79991234568',
            'status' => 'new',
        ]);
        
        $from = $order1->created_at->addSeconds(-5)->toDateTimeString();
        $to = $order1->created_at->addSeconds(5)->toDateTimeString();
        
        $orders = Order::dateRange($from, $to)->get();
        
        $this->assertGreaterThanOrEqual(1, $orders->count());
    }
    
    public function testOrderSearchScope()
    {
        Order::create([
            'order_number' => 'TEST-009',
            'type' => 'order',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        Order::create([
            'order_number' => 'TEST-010',
            'type' => 'order',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+79991234568',
            'status' => 'new',
        ]);
        
        $results = Order::search('John')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]->name);
        
        $results = Order::search('example.com')->get();
        $this->assertCount(2, $results);
        
        $results = Order::search('TEST-009')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('TEST-009', $results[0]->order_number);
    }
    
    public function testOrderStatusHistoryWithChangedBy()
    {
        $admin = \seedTestData(1, 'admin_users', [
            'email' => 'admin2@example.com',
            'name' => 'Admin User 2',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
        ])[0];
        
        $order = Order::create([
            'order_number' => 'TEST-011',
            'type' => 'order',
            'name' => 'Test Customer',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        OrderStatusHistory::logStatusChange(
            $order->id,
            'new',
            'processing',
            $admin['id'],
            'Started by admin'
        );
        
        $history = $order->statusHistory()->with('changedBy')->first();
        
        $this->assertNotNull($history->changedBy);
        $this->assertEquals($admin['id'], $history->changed_by);
        $this->assertEquals('admin2@example.com', $history->changedBy->email);
    }
    
    public function testOrderNotesCRUD()
    {
        $order = Order::create([
            'order_number' => 'TEST-012',
            'type' => 'order',
            'name' => 'Test Customer',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        $note = OrderNote::addNote($order->id, 'Original note');
        $this->assertEquals('Original note', $note->note);
        
        $note->note = 'Updated note';
        $note->save();
        
        $refreshed = OrderNote::find($note->id);
        $this->assertEquals('Updated note', $refreshed->note);
        
        $note->delete();
        
        $this->assertNull(OrderNote::find($note->id));
    }
    
    public function testMultipleStatusTransitions()
    {
        $order = Order::create([
            'order_number' => 'TEST-013',
            'type' => 'order',
            'name' => 'Test Customer',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        $statuses = ['new', 'processing', 'completed'];
        
        foreach ($statuses as $i => $status) {
            $oldStatus = $i > 0 ? $statuses[$i - 1] : null;
            OrderStatusHistory::logStatusChange(
                $order->id,
                $oldStatus,
                $status,
                null,
                "Transition to {$status}"
            );
        }
        
        $history = $order->statusHistory;
        
        $this->assertCount(3, $history);
        $this->assertNull($history[0]->old_status);
        $this->assertEquals('new', $history[0]->new_status);
        $this->assertEquals('new', $history[1]->old_status);
        $this->assertEquals('processing', $history[1]->new_status);
        $this->assertEquals('processing', $history[2]->old_status);
        $this->assertEquals('completed', $history[2]->new_status);
    }
    
    public function testOrderWithAllRelations()
    {
        $admin = \seedTestData(1, 'admin_users', [
            'email' => 'admin3@example.com',
            'name' => 'Admin User 3',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
        ])[0];
        
        $order = Order::create([
            'order_number' => 'TEST-014',
            'type' => 'order',
            'name' => 'Test Customer',
            'phone' => '+79991234567',
            'status' => 'new',
        ]);
        
        OrderStatusHistory::logStatusChange($order->id, null, 'new', $admin['id'], 'Created');
        OrderStatusHistory::logStatusChange($order->id, 'new', 'processing', $admin['id'], 'Started');
        
        OrderNote::addNote($order->id, 'Note 1', $admin['id']);
        OrderNote::addNote($order->id, 'Note 2', $admin['id']);
        
        $order = Order::with(['statusHistory.changedBy', 'notes.createdBy'])->find($order->id);
        
        $this->assertCount(2, $order->statusHistory);
        $this->assertCount(2, $order->notes);
        $this->assertNotNull($order->statusHistory[0]->changedBy);
        $this->assertNotNull($order->notes[0]->createdBy);
    }
}
