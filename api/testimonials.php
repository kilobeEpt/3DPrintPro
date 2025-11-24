<?php
/**
 * Testimonials API Endpoint
 * 
 * Handles CRUD operations for testimonials.
 * Uses Eloquent ORM via TestimonialController.
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\Controllers\Api\TestimonialController;

$controller = new TestimonialController();
$controller->handle();
