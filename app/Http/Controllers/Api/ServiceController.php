<?php

namespace App\Http\Controllers\Api;

use App\Models\Service;

/**
 * Service API Controller
 * 
 * Handles CRUD operations for services using Eloquent ORM.
 */
class ServiceController extends BaseApiController
{
    /**
     * Handle GET request - retrieve services
     * 
     * @return void
     */
    protected function handleGet()
    {
        // Get single service by ID
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'service');
            $service = Service::findOrFail($id);
            
            $this->success(['service' => $service->toArray()]);
        }
        
        // Get all services with filters
        $query = Service::query();
        
        // Apply filters
        if ($this->query('active') !== null) {
            $active = $this->query('active') === 'true' || $this->query('active') === '1';
            $query->where('active', $active);
        }
        
        if ($this->query('featured') !== null) {
            $featured = $this->query('featured') === 'true' || $this->query('featured') === '1';
            $query->where('featured', $featured);
        }
        
        if ($this->query('category')) {
            $query->category($this->query('category'));
        }
        
        // Order by sort_order
        $query->ordered();
        
        // Apply pagination
        $result = $this->paginate($query, $this->query);
        
        $this->success(
            ['services' => $result['data']],
            $result['meta']
        );
    }
    
    /**
     * Handle POST request - create service
     * 
     * @return void
     */
    protected function handlePost()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('services_create');
        
        // Validate required fields
        $errors = $this->validate($this->input, [
            'name' => 'required|string|min:1|max:255'
        ]);
        
        if (!empty($errors)) {
            \ApiLogger::validationError('POST /api/services.php', $errors);
            $this->validationError('Validation failed', $errors);
        }
        
        // Generate slug if not provided
        $data = $this->input;
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        // Create service
        $service = Service::create($data);
        
        \ApiLogger::info("Service created successfully", [
            'service_id' => $service->id,
            'name' => $service->name
        ]);
        
        $this->created([
            'id' => $service->id,
            'message' => 'Service created successfully'
        ]);
    }
    
    /**
     * Handle PUT request - update service
     * 
     * @return void
     */
    protected function handlePut()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('services_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('Service ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'service');
        
        // Find service
        $service = Service::findOrFail($id);
        
        // Remove ID from update data
        $data = $this->input;
        unset($data['id']);
        
        // Update service
        $service->update($data);
        
        \ApiLogger::info("Service updated successfully", [
            'service_id' => $id,
            'updated_fields' => array_keys($data)
        ]);
        
        $this->success([
            'message' => 'Service updated successfully',
            'service_id' => $id
        ]);
    }
    
    /**
     * Handle DELETE request - delete service
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('services_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('Service ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'service');
        
        // Find and delete service
        $service = Service::findOrFail($id);
        $service->delete();
        
        \ApiLogger::info("Service deleted successfully", ['service_id' => $id]);
        
        $this->success([
            'message' => 'Service deleted successfully',
            'service_id' => $id
        ]);
    }
    
    /**
     * Get resource name for logging
     * 
     * @return string
     */
    protected function getResourceName()
    {
        return 'services';
    }
    
    /**
     * Generate a URL-friendly slug from text
     * 
     * @param string $text
     * @return string
     */
    private function generateSlug($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }
}
