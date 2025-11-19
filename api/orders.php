<?php
// ========================================
// Orders API Endpoint - Full CRUD
// ========================================

require_once __DIR__ . '/helpers/security_headers.php';
require_once __DIR__ . '/helpers/rate_limiter.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/logger.php';
require_once __DIR__ . '/helpers/telegram.php';
require_once __DIR__ . '/helpers/admin_auth.php';
require_once __DIR__ . '/helpers/form_service.php';
require_once __DIR__ . '/db.php';

SecurityHeaders::apply();
SecurityHeaders::handlePreflight();

$db = new Database();
$method = $_SERVER['REQUEST_METHOD'];
$rateLimiter = new RateLimiter();

try {
    switch ($method) {
        case 'GET':
            // Require admin authentication for viewing orders
            requireAdminAuth();
            
            // Get all orders or single order
            if (isset($_GET['id'])) {
                $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
                if (!$id) {
                    ApiResponse::validationError('Invalid order ID');
                }
                
                $order = $db->getRecordById('orders', $id);
                
                if ($order) {
                    ApiResponse::success(['order' => $order]);
                } else {
                    ApiLogger::warning("Order not found", ['id' => $id]);
                    ApiResponse::notFound('Order not found');
                }
            } else {
                // Get all orders with optional filters
                $where = [];
                if (isset($_GET['status'])) {
                    $where['status'] = $_GET['status'];
                }
                if (isset($_GET['type'])) {
                    $where['type'] = $_GET['type'];
                }
                
                $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : 100;
                $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
                
                if ($limit === false || $limit < 1) $limit = 100;
                if ($offset === false || $offset < 0) $offset = 0;
                
                $orders = $db->getRecords('orders', $where, 'created_at', $limit, $offset);
                $total = $db->getCount('orders', $where);
                
                ApiResponse::success(
                    ['orders' => $orders],
                    [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                );
            }
            break;
            
        case 'POST':
            // Apply rate limiting for write operations
            $rateLimiter->apply('orders_create');
            
            // Create new order via form service
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in POST request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            // Determine form slug based on data
            $formSlug = 'contact';
            if (!empty($data['calculatorData']) || (isset($data['type']) && $data['type'] === 'order')) {
                $formSlug = 'order';
            }
            
            // Load form definition for validation
            $formData = FormService::loadForm($formSlug, true);
            
            if (!$formData) {
                ApiLogger::error("Form not found for order submission", ['slug' => $formSlug]);
                ApiResponse::serverError('Form configuration not found. Please contact support.');
            }
            
            // Normalize data for validation
            $submittedData = $data;
            
            // Validate submission using form service
            $validation = FormService::validateSubmission($formData, $submittedData);
            
            if (!$validation['valid']) {
                ApiLogger::validationError('POST /api/orders.php', $validation['errors']);
                ApiResponse::unprocessableEntity('Validation failed', $validation['errors']);
            }
            
            // Process submission through form service
            $metadata = [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ];
            
            $result = FormService::processSubmission($formData, $submittedData, $metadata, $db);
            
            if (!$result['success']) {
                ApiLogger::error("Failed to process order submission", [
                    'form_slug' => $formSlug,
                    'error' => $result['error']
                ]);
                ApiResponse::serverError('Failed to create order. Please try again.');
            }
            
            // Get order details
            $orderId = $result['order_id'];
            $orderNumber = 'Unknown';
            
            if ($orderId) {
                try {
                    $order = $db->getRecordById('orders', $orderId);
                    if ($order) {
                        $orderNumber = $order['order_number'];
                        $telegramSent = (bool)$order['telegram_sent'];
                        $telegramError = $order['telegram_error'] ?? null;
                    }
                } catch (Exception $e) {
                    ApiLogger::warning("Failed to retrieve order details", [
                        'order_id' => $orderId,
                        'exception' => $e
                    ]);
                    $telegramSent = false;
                    $telegramError = null;
                }
            }
            
            ApiLogger::info("Order created via form service", [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'submission_id' => $result['submission_id'],
                'form_slug' => $formSlug
            ]);
            
            ApiResponse::created([
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'submission_id' => $result['submission_id'],
                'message' => 'Order submitted successfully'
            ], [
                'telegram_sent' => $telegramSent ?? false,
                'telegram_error' => $telegramError ?? null
            ]);
            break;
            
        case 'PUT':
            // Require admin authentication and CSRF token
            requireAdminAuthWithCsrf();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('orders_update');
            
            // Update order
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                ApiLogger::warning('Invalid JSON in PUT request', ['raw_input' => substr($input, 0, 200)]);
                ApiResponse::validationError('Invalid JSON data');
            }
            
            if (empty($data['id'])) {
                ApiResponse::validationError('Order ID is required');
            }
            
            $id = filter_var($data['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid order ID');
            }
            
            // Check if order exists
            $existingOrder = $db->getRecordById('orders', $id);
            if (!$existingOrder) {
                ApiLogger::warning("Attempt to update non-existent order", ['id' => $id]);
                ApiResponse::notFound('Order not found');
            }
            
            // Check if status is changing
            $statusChanged = false;
            $oldStatus = null;
            $newStatus = null;
            if (isset($data['status']) && $data['status'] !== $existingOrder['status']) {
                $statusChanged = true;
                $oldStatus = $existingOrder['status'];
                $newStatus = $data['status'];
            }
            
            unset($data['id']);
            
            try {
                $db->updateRecord('orders', $id, $data);
                ApiLogger::info("Order updated successfully", [
                    'order_id' => $id,
                    'updated_fields' => array_keys($data),
                    'status_changed' => $statusChanged
                ]);
            } catch (PDOException $e) {
                ApiLogger::dbError('UPDATE', 'orders', $e, ['order_id' => $id]);
                ApiResponse::serverError('Failed to update order. Please try again.');
            }
            
            // Send Telegram notification if status changed
            $telegramSent = false;
            $telegramError = null;
            if ($statusChanged) {
                try {
                    $telegramResult = TelegramHelper::sendStatusChangeNotification(
                        $id,
                        $existingOrder['order_number'],
                        $oldStatus,
                        $newStatus,
                        $db
                    );
                    $telegramSent = $telegramResult['success'];
                    if (!$telegramSent) {
                        $telegramError = $telegramResult['error'];
                        ApiLogger::warning("Telegram status change notification failed", [
                            'order_id' => $id,
                            'error' => $telegramError
                        ]);
                    } else {
                        ApiLogger::info("Telegram status change notification sent", [
                            'order_id' => $id,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus
                        ]);
                    }
                } catch (Exception $e) {
                    $telegramError = $e->getMessage();
                    ApiLogger::error("Telegram status change notification exception", [
                        'order_id' => $id,
                        'exception' => $e
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
            
            ApiResponse::success($response);
            break;
            
        case 'DELETE':
            // Require admin authentication and CSRF token
            requireAdminAuthWithCsrf();
            
            // Apply rate limiting for write operations
            $rateLimiter->apply('orders_delete');
            
            // Delete order
            if (empty($_GET['id'])) {
                ApiResponse::validationError('Order ID is required');
            }
            
            $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            if (!$id) {
                ApiResponse::validationError('Invalid order ID');
            }
            
            // Check if order exists
            $existingOrder = $db->getRecordById('orders', $id);
            if (!$existingOrder) {
                ApiLogger::warning("Attempt to delete non-existent order", ['id' => $id]);
                ApiResponse::notFound('Order not found');
            }
            
            try {
                $db->deleteRecord('orders', $id);
                ApiLogger::info("Order deleted successfully", ['order_id' => $id]);
            } catch (PDOException $e) {
                ApiLogger::dbError('DELETE', 'orders', $e, ['order_id' => $id]);
                ApiResponse::serverError('Failed to delete order. Please try again.');
            }
            
            ApiResponse::success([
                'message' => 'Order deleted successfully',
                'order_id' => $id
            ]);
            break;
            
        default:
            ApiLogger::warning("Method not allowed", ['method' => $method]);
            ApiResponse::methodNotAllowed();
            break;
    }
    
} catch (PDOException $e) {
    ApiLogger::dbError('QUERY', 'orders', $e);
    ApiResponse::serverError('Database error occurred. Please try again later.');
    
} catch (Exception $e) {
    ApiLogger::error("Unexpected error in orders endpoint", ['exception' => $e]);
    ApiResponse::serverError('An unexpected error occurred. Please try again later.');
}

$db->close();
