<?php
/**
 * Admin Users API Endpoint
 * 
 * Handles CRUD operations for admin users.
 * Requires super_admin role (except for initial onboarding).
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Http\Controllers\Api\AdminUserController;

$controller = new AdminUserController();
$controller->handle();
