<?php
/**
 * Services API Endpoint
 * 
 * Handles CRUD operations for services.
 * Uses Eloquent ORM via ServiceController.
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\Controllers\Api\ServiceController;

$controller = new ServiceController();
$controller->handle();
