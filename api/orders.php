<?php
/**
 * Orders API Endpoint
 * 
 * Handles CRUD operations for orders.
 * Uses Eloquent ORM via OrderController.
 * Order creation uses FormService for validation and processing.
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\Controllers\Api\OrderController;

$controller = new OrderController();
$controller->handle();
