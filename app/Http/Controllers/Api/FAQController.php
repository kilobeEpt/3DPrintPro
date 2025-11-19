<?php

namespace App\Http\Controllers\Api;

use App\Models\FAQ;

/**
 * FAQ API Controller
 * 
 * Handles CRUD operations for FAQ items using Eloquent ORM.
 */
class FAQController extends BaseApiController
{
    /**
     * Handle GET request - retrieve FAQ items
     * 
     * @return void
     */
    protected function handleGet()
    {
        // Get single FAQ item by ID
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'FAQ item');
            $item = FAQ::findOrFail($id);
            
            $this->success(['item' => $item->toArray()]);
        }
        
        // Get all FAQ items with filters
        $query = FAQ::query();
        
        // Apply filters
        if ($this->query('active') !== null) {
            $active = $this->query('active') === 'true' || $this->query('active') === '1';
            $query->where('active', $active);
        }
        
        // Order by sort_order
        $query->ordered();
        
        // Apply pagination
        $result = $this->paginate($query, $this->query);
        
        $this->success(
            ['items' => $result['data']],
            $result['meta']
        );
    }
    
    /**
     * Handle POST request - create FAQ item
     * 
     * @return void
     */
    protected function handlePost()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('faq_create');
        
        // Validate required fields
        $errors = $this->validate($this->input, [
            'question' => 'required|string|min:1',
            'answer' => 'required|string|min:1'
        ]);
        
        if (!empty($errors)) {
            \ApiLogger::validationError('POST /api/faq.php', $errors);
            $this->validationError('Validation failed', $errors);
        }
        
        // Create FAQ item
        $item = FAQ::create($this->input);
        
        \ApiLogger::info("FAQ item created successfully", [
            'faq_id' => $item->id,
            'question' => substr($item->question, 0, 50)
        ]);
        
        $this->created([
            'id' => $item->id,
            'message' => 'FAQ item created successfully'
        ]);
    }
    
    /**
     * Handle PUT request - update FAQ item
     * 
     * @return void
     */
    protected function handlePut()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('faq_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('FAQ item ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'FAQ item');
        
        // Find FAQ item
        $item = FAQ::findOrFail($id);
        
        // Remove ID from update data
        $data = $this->input;
        unset($data['id']);
        
        // Update FAQ item
        $item->update($data);
        
        \ApiLogger::info("FAQ item updated successfully", [
            'faq_id' => $id,
            'updated_fields' => array_keys($data)
        ]);
        
        $this->success([
            'message' => 'FAQ item updated successfully',
            'faq_id' => $id
        ]);
    }
    
    /**
     * Handle DELETE request - delete FAQ item
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('faq_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('FAQ item ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'FAQ item');
        
        // Find and delete FAQ item
        $item = FAQ::findOrFail($id);
        $item->delete();
        
        \ApiLogger::info("FAQ item deleted successfully", ['faq_id' => $id]);
        
        $this->success([
            'message' => 'FAQ item deleted successfully',
            'faq_id' => $id
        ]);
    }
    
    /**
     * Get resource name for logging
     * 
     * @return string
     */
    protected function getResourceName()
    {
        return 'faq';
    }
}
