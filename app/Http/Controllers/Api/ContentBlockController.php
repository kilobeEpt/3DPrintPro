<?php

namespace App\Http\Controllers\Api;

use App\Models\ContentBlock;

/**
 * Content Block API Controller
 * 
 * Handles CRUD operations for content blocks using Eloquent ORM.
 */
class ContentBlockController extends BaseApiController
{
    /**
     * Handle GET request - retrieve content blocks
     * 
     * @return void
     */
    protected function handleGet()
    {
        // Get single content block by ID
        if ($this->query('id')) {
            $id = $this->validateId($this->query('id'), 'content block');
            $block = ContentBlock::findOrFail($id);
            
            $this->success(['block' => $block->toArray()]);
        }
        
        // Get by block name
        if ($this->query('name')) {
            $block = ContentBlock::where('block_name', $this->query('name'))
                ->active()
                ->ordered()
                ->first();
            
            if (!$block) {
                \ApiLogger::warning("Content block not found", ['name' => $this->query('name')]);
                $this->notFound('Content block not found');
            }
            
            $this->success(['block' => $block->toArray()]);
        }
        
        // Get all content blocks with filters
        $query = ContentBlock::query();
        
        // Apply filters
        if ($this->query('active') !== null) {
            $active = $this->query('active') === 'true' || $this->query('active') === '1';
            $query->where('active', $active);
        }
        
        if ($this->query('page')) {
            $query->where('page', $this->query('page'));
        }
        
        // Order by sort_order
        $query->ordered();
        
        // Apply pagination
        $result = $this->paginate($query, $this->query);
        
        $this->success(
            ['blocks' => $result['data']],
            $result['meta']
        );
    }
    
    /**
     * Handle POST request - create content block
     * 
     * @return void
     */
    protected function handlePost()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('content_create');
        
        // Validate required fields
        $errors = $this->validate($this->input, [
            'block_name' => 'required|string|min:1|max:255',
            'content' => 'required|string|min:1'
        ]);
        
        if (!empty($errors)) {
            \ApiLogger::validationError('POST /api/content.php', $errors);
            $this->validationError('Validation failed', $errors);
        }
        
        // Create content block
        $block = ContentBlock::create($this->input);
        
        \ApiLogger::info("Content block created successfully", [
            'block_id' => $block->id,
            'block_name' => $block->block_name
        ]);
        
        $this->created([
            'id' => $block->id,
            'message' => 'Content block created successfully'
        ]);
    }
    
    /**
     * Handle PUT request - update content block
     * 
     * @return void
     */
    protected function handlePut()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('content_update');
        
        // Validate ID
        if (empty($this->input['id'])) {
            $this->validationError('Content block ID is required');
        }
        
        $id = $this->validateId($this->input['id'], 'content block');
        
        // Find content block
        $block = ContentBlock::findOrFail($id);
        
        // Remove ID from update data
        $data = $this->input;
        unset($data['id']);
        
        // Update content block
        $block->update($data);
        
        \ApiLogger::info("Content block updated successfully", [
            'block_id' => $id,
            'updated_fields' => array_keys($data)
        ]);
        
        $this->success([
            'message' => 'Content block updated successfully',
            'block_id' => $id
        ]);
    }
    
    /**
     * Handle DELETE request - delete content block
     * 
     * @return void
     */
    protected function handleDelete()
    {
        // Require authentication and CSRF
        $this->requireAuth(true);
        
        // Apply rate limiting
        $this->rateLimit('content_delete');
        
        // Validate ID
        if (empty($this->query('id'))) {
            $this->validationError('Content block ID is required');
        }
        
        $id = $this->validateId($this->query('id'), 'content block');
        
        // Find and delete content block
        $block = ContentBlock::findOrFail($id);
        $block->delete();
        
        \ApiLogger::info("Content block deleted successfully", ['block_id' => $id]);
        
        $this->success([
            'message' => 'Content block deleted successfully',
            'block_id' => $id
        ]);
    }
    
    /**
     * Get resource name for logging
     * 
     * @return string
     */
    protected function getResourceName()
    {
        return 'content_blocks';
    }
}
