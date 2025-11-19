<?php
/**
 * FAQ API Endpoint
 * 
 * Handles CRUD operations for FAQ items.
 * Uses Eloquent ORM via FAQController.
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\Controllers\Api\FAQController;

$controller = new FAQController();
$controller->handle();
