<?php

namespace App\Http\Controllers\Api;

use App\Models\Portfolio;

/**
 * Portfolio API Controller
 * 
 * Handles CRUD operations for portfolio items using Eloquent ORM.
 */
class PortfolioController extends BaseApiController
{
    /**
     * Handle GET request - retrieve portfolio items
     * 
     * @return void
     */
    protected function handleGet()
    {
        // Get single portfolio item by ID
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'portfolio item');
            $item = Portfolio::findOrFail($id);
            
            $this->success(['item' => $item->toArray()]);
        }
        
        // Get all portfolio items with filters
        $query = Portfolio::query();
        
        // Apply filters
        if ($this->query('active') !== null) {
            $active = $this->query('active') === 'true' || $this->query('active') === '1';
            $query->where('active', $active);
        }
        
        if ($this->query('category')) {
            $query->where('category', $this->query('category'));
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
     * Handle POST request - create portfolio item
     * 
     * @return void
     */
    protected function handlePost()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('portfolio_create');
        
        // Validate required fields
        $errors = $this->validate($this->input, [
            'title' => 'required|string|min:1|max:255'
        ]);
        
        if (!empty($errors)) {
            \ApiLogger::validationError('POST /api/portfolio.php', $errors);
            $this->validationError('Validation failed', $errors);
        }
        
        // Create portfolio item
        $item = Portfolio::create($this->input);
        
        \ApiLogger::info("Portfolio item created successfully", [
            'item_id' => $item->id,
            'title' => $item->title
        ]);
        
        $this->created([
            'id' => $item->id,
            'message' => 'Portfolio item created successfully'
        ]);
    }
    
    /**
     * Handle PUT request - update portfolio item
     * 
     * @return void
     */
    protected function handlePut()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('portfolio_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('Portfolio item ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'portfolio item');
        
        // Find portfolio item
        $item = Portfolio::findOrFail($id);
        
        // Remove ID from update data
        $data = $this->input;
        unset($data['id']);
        
        // Update portfolio item
        $item->update($data);
        
        \ApiLogger::info("Portfolio item updated successfully", [
            'item_id' => $id,
            'updated_fields' => array_keys($data)
        ]);
        
        $this->success([
            'message' => 'Portfolio item updated successfully',
            'item_id' => $id
        ]);
    }
    
    /**
     * Handle DELETE request - delete portfolio item
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('portfolio_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('Portfolio item ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'portfolio item');
        
        // Find and delete portfolio item
        $item = Portfolio::findOrFail($id);
        $item->delete();
        
        \ApiLogger::info("Portfolio item deleted successfully", ['item_id' => $id]);
        
        $this->success([
            'message' => 'Portfolio item deleted successfully',
            'item_id' => $id
        ]);
    }
    
    /**
     * Get resource name for logging
     * 
     * @return string
     */
    protected function getResourceName()
    {
        return 'portfolio';
    }
}
