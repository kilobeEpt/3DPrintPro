<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\OrderNote;
use App\Models\AdminActionLog;
use App\Services\OrderExportService;

/**
 * Order API Controller (v2.0)
 * 
 * Enhanced CRUD operations for orders with:
 * - Advanced filtering (status, type, date range, search, form_slug, archived)
 * - Status history tracking
 * - Internal notes management
 * - CSV/PDF exports with signed URLs
 * - Archiving support
 * - RBAC and audit logging
 */
class OrderController extends BaseApiController
{
    /**
     * Handle GET request - retrieve orders
     * 
     * @return void
     */
    protected function handleGet()
    {
        $this->requireAuth(false);
        
        $id = $this->query('id');
        
        if ($id) {
            $this->getOrder($id);
            return;
        }
        
        $this->listOrders();
    }
    
    protected function getOrder($id)
    {
        $id = $this->validateId($id, 'order');
        $order = Order::with(['formSubmission', 'statusHistory.changedBy', 'notes.createdBy'])
            ->findOrFail($id);
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            AdminActionLog::ACTION_VIEW,
            'order',
            $id
        );
        
        $this->success([
            'order' => $order->toArray(),
        ]);
    }
    
    protected function listOrders()
    {
        $query = Order::query();
        
        if ($this->query('status')) {
            $query->status($this->query('status'));
        }
        
        if ($this->query('type')) {
            $query->type($this->query('type'));
        }
        
        if ($this->query('form_slug')) {
            $query->where('form_slug', $this->query('form_slug'));
        }
        
        if ($this->query('search')) {
            $query->search($this->query('search'));
        }
        
        $dateFrom = $this->query('date_from');
        $dateTo = $this->query('date_to');
        if ($dateFrom || $dateTo) {
            $query->dateRange($dateFrom, $dateTo);
        }
        
        $archived = $this->query('archived');
        if ($archived === 'true' || $archived === '1') {
            $query->archived();
        } elseif ($archived === 'false' || $archived === '0') {
            $query->active();
        }
        
        $sortBy = $this->query('sort_by') ?? 'created_at';
        $sortOrder = $this->query('sort_order') ?? 'desc';
        
        $allowedSorts = ['created_at', 'updated_at', 'amount', 'status', 'order_number'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        $params = $this->query;
        if (!isset($params['limit'])) {
            $params['limit'] = 100;
        }
        
        $withRelations = $this->query('with_relations');
        if ($withRelations === 'true' || $withRelations === '1') {
            $query->with(['formSubmission', 'statusHistory', 'notes']);
        }
        
        $result = $this->paginate($query, $params);
        
        $meta = $result['meta'];
        $meta['has_more'] = isset($meta['offset']) && isset($meta['limit']) 
            ? ($meta['offset'] + $meta['limit']) < $meta['total']
            : false;
        
        $this->success(
            ['orders' => $result['data']],
            $meta
        );
    }
    
    /**
     * Handle POST request - create order via FormService
     * 
     * @return void
     */
    protected function handlePost()
    {
        $this->rateLimit('orders_create');
        
        $formSlug = 'contact';
        if (!empty($this->input['calculatorData']) || 
            (isset($this->input['type']) && $this->input['type'] === 'order')) {
            $formSlug = 'order';
        }
        
        $formData = \FormService::loadForm($formSlug, true);
        
        if (!$formData) {
            \ApiLogger::error("Form not found for order submission", ['slug' => $formSlug]);
            $this->error('Form configuration not found. Please contact support.', 500);
        }
        
        $validation = \FormService::validateSubmission($formData, $this->input);
        
        if (!$validation['valid']) {
            \ApiLogger::validationError('POST /api/orders.php', $validation['errors']);
            $this->validationError('Validation failed', $validation['errors']);
        }
        
        $metadata = [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];
        
        require_once __DIR__ . '/../../../api/db.php';
        $db = new \Database();
        
        $result = \FormService::processSubmission($formData, $this->input, $metadata, $db);
        
        if (!$result['success']) {
            \ApiLogger::error("Failed to process order submission", [
                'form_slug' => $formSlug,
                'error' => $result['error']
            ]);
            $this->error('Failed to create order. Please try again.', 500);
        }
        
        $orderId = $result['order_id'];
        $orderNumber = 'Unknown';
        $telegramSent = false;
        $telegramError = null;
        
        if ($orderId) {
            try {
                $order = Order::find($orderId);
                if ($order) {
                    $orderNumber = $order->order_number;
                    $telegramSent = (bool)$order->telegram_sent;
                    $telegramError = $order->telegram_error;
                    
                    OrderStatusHistory::logStatusChange(
                        $orderId,
                        null,
                        $order->status,
                        null,
                        'Initial status on order creation'
                    );
                }
            } catch (\Exception $e) {
                \ApiLogger::warning("Failed to retrieve order details", [
                    'order_id' => $orderId,
                    'exception' => $e->getMessage()
                ]);
            }
        }
        
        $db->close();
        
        \ApiLogger::info("Order created via form service", [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'submission_id' => $result['submission_id'],
            'form_slug' => $formSlug
        ]);
        
        $this->created([
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'submission_id' => $result['submission_id'],
            'message' => 'Order submitted successfully'
        ], [
            'telegram_sent' => $telegramSent,
            'telegram_error' => $telegramError
        ]);
    }
    
    /**
     * Handle PUT request - update order
     * 
     * @return void
     */
    protected function handlePut()
    {
        $this->requireAuth(true);
        $this->rateLimit('orders_update');
        
        if (empty($this->input['id'])) {
            $this->validationError('Order ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'order');
        $order = Order::findOrFail($id);
        
        $statusChanged = false;
        $oldStatus = null;
        $newStatus = null;
        
        if (isset($this->input['status']) && $this->input['status'] !== $order->status) {
            $statusChanged = true;
            $oldStatus = $order->status;
            $newStatus = $this->input['status'];
        }
        
        $data = $this->input;
        unset($data['id']);
        
        $order->update($data);
        
        if ($statusChanged) {
            $userId = $_SESSION['ADMIN_USER_ID'] ?? null;
            $comment = $this->input['status_comment'] ?? null;
            
            OrderStatusHistory::logStatusChange(
                $id,
                $oldStatus,
                $newStatus,
                $userId,
                $comment
            );
            
            AdminActionLog::log(
                $userId,
                'status_change',
                'order',
                $id,
                [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'comment' => $comment,
                ]
            );
            
            $this->sendStatusChangeNotifications($order, $oldStatus, $newStatus);
        }
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            AdminActionLog::ACTION_UPDATE,
            'order',
            $id,
            ['updated_fields' => array_keys($data)]
        );
        
        \ApiLogger::info("Order updated successfully", [
            'order_id' => $id,
            'updated_fields' => array_keys($data),
            'status_changed' => $statusChanged
        ]);
        
        $response = [
            'message' => 'Order updated successfully',
            'order_id' => $id
        ];
        
        if ($statusChanged) {
            $response['status_changed'] = true;
        }
        
        $this->success($response);
    }
    
    /**
     * Handle PATCH request - partial updates (status, archive, notes)
     * 
     * @return void
     */
    protected function handlePatch()
    {
        $this->requireAuth(true);
        $this->rateLimit('orders_update');
        
        $action = $this->query('action');
        $id = $this->query('id') ?? $this->input['id'] ?? null;
        
        if (!$id) {
            $this->validationError('Order ID is required');
        }
        
        $id = $this->validateId($id, 'order');
        
        switch ($action) {
            case 'status':
                $this->updateStatus($id);
                break;
            case 'archive':
                $this->archiveOrder($id);
                break;
            case 'unarchive':
                $this->unarchiveOrder($id);
                break;
            case 'add_note':
                $this->addNote($id);
                break;
            case 'update_note':
                $this->updateNote($id);
                break;
            case 'delete_note':
                $this->deleteNote($id);
                break;
            default:
                $this->error('Invalid action', 400);
        }
    }
    
    protected function updateStatus($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        if (empty($this->input['status'])) {
            $this->validationError('Status is required');
        }
        
        $validStatuses = [Order::STATUS_NEW, Order::STATUS_PROCESSING, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];
        if (!in_array($this->input['status'], $validStatuses)) {
            $this->validationError('Invalid status value');
        }
        
        $oldStatus = $order->status;
        $newStatus = $this->input['status'];
        $comment = $this->input['comment'] ?? null;
        
        if ($oldStatus === $newStatus) {
            $this->error('Status is already ' . $newStatus, 400);
        }
        
        $order->status = $newStatus;
        $order->save();
        
        $userId = $_SESSION['ADMIN_USER_ID'] ?? null;
        
        OrderStatusHistory::logStatusChange(
            $orderId,
            $oldStatus,
            $newStatus,
            $userId,
            $comment
        );
        
        AdminActionLog::log(
            $userId,
            'status_change',
            'order',
            $orderId,
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
            ]
        );
        
        $this->sendStatusChangeNotifications($order, $oldStatus, $newStatus);
        
        $this->success([
            'message' => 'Order status updated successfully',
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }
    
    protected function archiveOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        if ($order->isArchived()) {
            $this->error('Order is already archived', 400);
        }
        
        $order->archive();
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            'archive',
            'order',
            $orderId
        );
        
        $this->success([
            'message' => 'Order archived successfully',
            'order_id' => $orderId,
        ]);
    }
    
    protected function unarchiveOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        if (!$order->isArchived()) {
            $this->error('Order is not archived', 400);
        }
        
        $order->unarchive();
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            'unarchive',
            'order',
            $orderId
        );
        
        $this->success([
            'message' => 'Order unarchived successfully',
            'order_id' => $orderId,
        ]);
    }
    
    protected function addNote($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        if (empty($this->input['note'])) {
            $this->validationError('Note is required');
        }
        
        $note = OrderNote::addNote(
            $orderId,
            $this->input['note'],
            $_SESSION['ADMIN_USER_ID'] ?? null
        );
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            'add_note',
            'order',
            $orderId,
            ['note_id' => $note->id]
        );
        
        $this->success([
            'message' => 'Note added successfully',
            'note' => $note->toArray(),
        ]);
    }
    
    protected function updateNote($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        $noteId = $this->input['note_id'] ?? null;
        if (!$noteId) {
            $this->validationError('Note ID is required');
        }
        
        $note = OrderNote::where('order_id', $orderId)
            ->where('id', $noteId)
            ->firstOrFail();
        
        if (empty($this->input['note'])) {
            $this->validationError('Note content is required');
        }
        
        $note->note = $this->input['note'];
        $note->save();
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            'update_note',
            'order',
            $orderId,
            ['note_id' => $note->id]
        );
        
        $this->success([
            'message' => 'Note updated successfully',
            'note' => $note->toArray(),
        ]);
    }
    
    protected function deleteNote($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        $noteId = $this->input['note_id'] ?? $this->query('note_id');
        if (!$noteId) {
            $this->validationError('Note ID is required');
        }
        
        $note = OrderNote::where('order_id', $orderId)
            ->where('id', $noteId)
            ->firstOrFail();
        
        $note->delete();
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            'delete_note',
            'order',
            $orderId,
            ['note_id' => $noteId]
        );
        
        $this->success([
            'message' => 'Note deleted successfully',
            'note_id' => $noteId,
        ]);
    }
    
    /**
     * Handle DELETE request - delete order
     * 
     * @return void
     */
    protected function handleDelete()
    {
        $this->requireAuth(true);
        $this->rateLimit('orders_delete');
        
        if (empty($this->query('id'))) {
            $this->validationError('Order ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'order');
        
        $order = Order::findOrFail($id);
        $order->delete();
        
        AdminActionLog::log(
            $_SESSION['ADMIN_USER_ID'] ?? null,
            AdminActionLog::ACTION_DELETE,
            'order',
            $id
        );
        
        \ApiLogger::info("Order deleted successfully", ['order_id' => $id]);
        
        $this->success([
            'message' => 'Order deleted successfully',
            'order_id' => $id
        ]);
    }
    
    protected function sendStatusChangeNotifications($order, $oldStatus, $newStatus)
    {
        $settingsService = new \App\Services\SettingsService();
        
        $telegramEnabled = $settingsService->get('notifications_telegram_status_change', 'false') === 'true';
        $emailEnabled = $settingsService->get('notifications_email_status_change', 'false') === 'true';
        
        if ($telegramEnabled) {
            try {
                require_once __DIR__ . '/../../../api/db.php';
                $db = new \Database();
                
                $result = \TelegramHelper::sendStatusChangeNotification(
                    $order->id,
                    $order->order_number,
                    $oldStatus,
                    $newStatus,
                    $db
                );
                
                if (!$result['success']) {
                    \ApiLogger::warning("Telegram status notification failed", [
                        'order_id' => $order->id,
                        'error' => $result['error']
                    ]);
                }
                
                $db->close();
            } catch (\Exception $e) {
                \ApiLogger::error("Telegram status notification exception", [
                    'order_id' => $order->id,
                    'exception' => $e->getMessage()
                ]);
            }
        }
        
        if ($emailEnabled) {
            $emailTo = $settingsService->get('notifications_email_address');
            if ($emailTo) {
                $subject = "Order Status Changed: {$order->order_number}";
                $message = "Order #{$order->order_number} status changed from '{$oldStatus}' to '{$newStatus}'.\n\n";
                $message .= "Customer: {$order->name}\n";
                $message .= "Email: {$order->email}\n";
                $message .= "Phone: {$order->phone}\n";
                
                $headers = "From: noreply@3dprint-omsk.ru\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                
                @mail($emailTo, $subject, $message, $headers);
            }
        }
    }
    
    /**
     * Get resource name for logging
     * 
     * @return string
     */
    protected function getResourceName()
    {
        return 'orders';
    }
}
