<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;

/**
 * Order API Controller
 * 
 * Handles CRUD operations for orders using Eloquent ORM.
 * Note: Order creation uses FormService for validation and processing.
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
        // Require authentication for viewing orders
        $this->requireAuth(false);
        
        // Get single order by ID
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'order');
            $order = Order::findOrFail($id);
            
            $this->success(['order' => $order->toArray()]);
        }
        
        // Get all orders with filters
        $query = Order::query();
        
        // Apply filters
        if ($this->query('status')) {
            $query->status($this->query('status'));
        }
        
        if ($this->query('type')) {
            $query->where('type', $this->query('type'));
        }
        
        // Order by created_at DESC
        $query->orderBy('created_at', 'desc');
        
        // Apply pagination with default limit of 100
        $params = $this->query;
        if (!isset($params['limit'])) {
            $params['limit'] = 100;
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
        // Apply rate limiting
        $this->rateLimit('orders_create');
        
        // Determine form slug based on data
        $formSlug = 'contact';
        if (!empty($this->input['calculatorData']) || 
            (isset($this->input['type']) && $this->input['type'] === 'order')) {
            $formSlug = 'order';
        }
        
        // Load form definition for validation
        $formData = \FormService::loadForm($formSlug, true);
        
        if (!$formData) {
            \ApiLogger::error("Form not found for order submission", ['slug' => $formSlug]);
            $this->error('Form configuration not found. Please contact support.', 500);
        }
        
        // Validate submission using form service
        $validation = \FormService::validateSubmission($formData, $this->input);
        
        if (!$validation['valid']) {
            \ApiLogger::validationError('POST /api/orders.php', $validation['errors']);
            $this->validationError('Validation failed', $validation['errors']);
        }
        
        // Process submission through form service
        $metadata = [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];
        
        // For FormService, we need to pass a Database instance
        // This is one of the few places where we still need the legacy Database class
        // for compatibility with existing FormService
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
        
        // Get order details
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
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('orders_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('Order ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'order');
        
        // Find order
        $order = Order::findOrFail($id);
        
        // Check if status is changing
        $statusChanged = false;
        $oldStatus = null;
        $newStatus = null;
        if (isset($this->input['status']) && $this->input['status'] !== $order->status) {
            $statusChanged = true;
            $oldStatus = $order->status;
            $newStatus = $this->input['status'];
        }
        
        // Remove ID from update data
        $data = $this->input;
        unset($data['id']);
        
        // Update order
        $order->update($data);
        
        \ApiLogger::info("Order updated successfully", [
            'order_id' => $id,
            'updated_fields' => array_keys($data),
            'status_changed' => $statusChanged
        ]);
        
        // Send Telegram notification if status changed
        $telegramSent = false;
        $telegramError = null;
        if ($statusChanged) {
            try {
                require_once __DIR__ . '/../../../api/db.php';
                $db = new \Database();
                
                $telegramResult = \TelegramHelper::sendStatusChangeNotification(
                    $id,
                    $order->order_number,
                    $oldStatus,
                    $newStatus,
                    $db
                );
                $telegramSent = $telegramResult['success'];
                if (!$telegramSent) {
                    $telegramError = $telegramResult['error'];
                    \ApiLogger::warning("Telegram status change notification failed", [
                        'order_id' => $id,
                        'error' => $telegramError
                    ]);
                } else {
                    \ApiLogger::info("Telegram status change notification sent", [
                        'order_id' => $id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus
                    ]);
                }
                
                $db->close();
            } catch (\Exception $e) {
                $telegramError = $e->getMessage();
                \ApiLogger::error("Telegram status change notification exception", [
                    'order_id' => $id,
                    'exception' => $e->getMessage()
                ]);
            }
        }
        
        $response = [
            'message' => 'Order updated successfully',
            'order_id' => $id
        ];
        
        if ($statusChanged) {
            $response['status_changed'] = true;
            $response['telegram_sent'] = $telegramSent;
            if ($telegramError) {
                $response['telegram_error'] = $telegramError;
            }
        }
        
        $this->success($response);
    }
    
    /**
     * Handle DELETE request - delete order
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('orders_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('Order ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'order');
        
        // Find and delete order
        $order = Order::findOrFail($id);
        $order->delete();
        
        \ApiLogger::info("Order deleted successfully", ['order_id' => $id]);
        
        $this->success([
            'message' => 'Order deleted successfully',
            'order_id' => $id
        ]);
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
