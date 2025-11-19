<?php

namespace App\Http\Controllers\Api;

use App\Models\Testimonial;

/**
 * Testimonial API Controller
 * 
 * Handles CRUD operations for testimonials using Eloquent ORM.
 */
class TestimonialController extends BaseApiController
{
    /**
     * Handle GET request - retrieve testimonials
     * 
     * @return void
     */
    protected function handleGet()
    {
        // Get single testimonial by ID
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'testimonial');
            $testimonial = Testimonial::findOrFail($id);
            
            $this->success(['testimonial' => $testimonial->toArray()]);
        }
        
        // Get all testimonials with filters
        $query = Testimonial::query();
        
        // Apply filters
        if ($this->query('active') !== null) {
            $active = $this->query('active') === 'true' || $this->query('active') === '1';
            $query->where('active', $active);
        }
        
        if ($this->query('approved') !== null) {
            $approved = $this->query('approved') === 'true' || $this->query('approved') === '1';
            $query->where('approved', $approved);
        }
        
        // Order by sort_order
        $query->ordered();
        
        // Apply pagination
        $result = $this->paginate($query, $this->query);
        
        $this->success(
            ['testimonials' => $result['data']],
            $result['meta']
        );
    }
    
    /**
     * Handle POST request - create testimonial
     * 
     * @return void
     */
    protected function handlePost()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('testimonials_create');
        
        // Validate required fields
        $errors = $this->validate($this->input, [
            'client_name' => 'required|string|min:1|max:255',
            'content' => 'required|string|min:1'
        ]);
        
        if (!empty($errors)) {
            \ApiLogger::validationError('POST /api/testimonials.php', $errors);
            $this->validationError('Validation failed', $errors);
        }
        
        // Create testimonial
        $testimonial = Testimonial::create($this->input);
        
        \ApiLogger::info("Testimonial created successfully", [
            'testimonial_id' => $testimonial->id,
            'client_name' => $testimonial->client_name
        ]);
        
        $this->created([
            'id' => $testimonial->id,
            'message' => 'Testimonial created successfully'
        ]);
    }
    
    /**
     * Handle PUT request - update testimonial
     * 
     * @return void
     */
    protected function handlePut()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('testimonials_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('Testimonial ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'testimonial');
        
        // Find testimonial
        $testimonial = Testimonial::findOrFail($id);
        
        // Remove ID from update data
        $data = $this->input;
        unset($data['id']);
        
        // Update testimonial
        $testimonial->update($data);
        
        \ApiLogger::info("Testimonial updated successfully", [
            'testimonial_id' => $id,
            'updated_fields' => array_keys($data)
        ]);
        
        $this->success([
            'message' => 'Testimonial updated successfully',
            'testimonial_id' => $id
        ]);
    }
    
    /**
     * Handle DELETE request - delete testimonial
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('testimonials_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('Testimonial ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'testimonial');
        
        // Find and delete testimonial
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->delete();
        
        \ApiLogger::info("Testimonial deleted successfully", ['testimonial_id' => $id]);
        
        $this->success([
            'message' => 'Testimonial deleted successfully',
            'testimonial_id' => $id
        ]);
    }
    
    /**
     * Get resource name for logging
     * 
     * @return string
     */
    protected function getResourceName()
    {
        return 'testimonials';
    }
}
