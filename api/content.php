<?php
/**
 * Content Blocks API Endpoint
 * 
 * Handles CRUD operations for content blocks.
 * Uses Eloquent ORM via ContentBlockController.
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\Controllers\Api\ContentBlockController;

$controller = new ContentBlockController();
$controller->handle();
