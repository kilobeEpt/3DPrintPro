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
            
            $this->cacheService->setCacheHeaders($block->updated_at);
            $this->success(['block' => $block->toArray()]);
        }
        
        // Get by slug
        if ($this->query('slug')) {
            $block = ContentBlock::where('slug', $this->query('slug'))
                ->active()
                ->ordered()
                ->first();
            
            if (!$block) {
                \ApiLogger::warning("Content block not found", ['slug' => $this->query('slug')]);
                $this->notFound('Content block not found');
            }
            
            $this->cacheService->setCacheHeaders($block->updated_at);
            $this->success(['block' => $block->toArray()]);
        }
        
        // Get by block name (legacy support)
        if ($this->query('name')) {
            $block = ContentBlock::where('block_name', $this->query('name'))
                ->active()
                ->ordered()
                ->first();
            
            if (!$block) {
                \ApiLogger::warning("Content block not found", ['name' => $this->query('name')]);
                $this->notFound('Content block not found');
            }
            
            $this->cacheService->setCacheHeaders($block->updated_at);
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
        
        // Set cache headers
        if (!empty($result['data'])) {
            $collection = collect($result['data']);
            $latestTimestamp = $this->cacheService->getLatestTimestamp($collection);
            if ($latestTimestamp) {
                $this->cacheService->setCacheHeaders($latestTimestamp);
            }
        }
        
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
        
        // Generate unique slug if not provided
        $data = $this->input;
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['block_name'], ContentBlock::class);
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], ContentBlock::class);
        }
        
        // Create content block
        $block = ContentBlock::create($data);
        
        // Invalidate cache
        $this->cacheService->invalidateCache('content_blocks');
        
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
        
        // Handle slug update if provided
        if (isset($data['slug']) && $data['slug'] !== $block->slug) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], ContentBlock::class, $id);
        }
        
        // Update content block
        $block->update($data);
        
        // Invalidate cache
        $this->cacheService->invalidateCache('content_blocks');
        
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
        
        // Invalidate cache
        $this->cacheService->invalidateCache('content_blocks');
        
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
