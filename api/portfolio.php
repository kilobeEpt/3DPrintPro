<?php
/**
 * Portfolio API Endpoint
 * 
 * Handles CRUD operations for portfolio items.
 * Uses Eloquent ORM via PortfolioController.
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\Controllers\Api\PortfolioController;

$controller = new PortfolioController();
$controller->handle();
