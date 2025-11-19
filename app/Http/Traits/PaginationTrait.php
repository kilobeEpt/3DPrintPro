<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Pagination Trait
 * 
 * Provides consistent pagination functionality for API controllers.
 */
trait PaginationTrait
{
    /**
     * Apply pagination to a query builder
     * 
     * @param Builder $query
     * @param array $params Request parameters (limit, offset, page)
     * @return array ['data' => array, 'meta' => array]
     */
    protected function paginate(Builder $query, array $params = [])
    {
        // Get pagination parameters
        $limit = $this->getLimit($params);
        $offset = $this->getOffset($params);
        $page = isset($params['page']) ? max(1, (int)$params['page']) : null;
        
        // Get total count before applying limit/offset
        $total = $query->count();
        
        // Apply limit and offset
        if ($limit !== null) {
            if ($page !== null) {
                // Page-based pagination
                $offset = ($page - 1) * $limit;
            }
            $query->limit($limit)->offset($offset);
        }
        
        // Execute query
        $data = $query->get()->toArray();
        
        // Build meta information
        $meta = [
            'total' => $total
        ];
        
        if ($limit !== null) {
            $meta['limit'] = $limit;
            $meta['offset'] = $offset;
            
            if ($page !== null) {
                $meta['page'] = $page;
                $meta['pages'] = $limit > 0 ? (int)ceil($total / $limit) : 0;
            }
        }
        
        return [
            'data' => $data,
            'meta' => $meta
        ];
    }
    
    /**
     * Get limit from request parameters
     * 
     * @param array $params
     * @return int|null
     */
    protected function getLimit(array $params)
    {
        if (!isset($params['limit'])) {
            return null;
        }
        
        $limit = filter_var($params['limit'], FILTER_VALIDATE_INT);
        
        if ($limit === false || $limit < 1) {
            return null;
        }
        
        // Apply max limit to prevent abuse
        $maxLimit = 100;
        return min($limit, $maxLimit);
    }
    
    /**
     * Get offset from request parameters
     * 
     * @param array $params
     * @return int
     */
    protected function getOffset(array $params)
    {
        if (!isset($params['offset'])) {
            return 0;
        }
        
        $offset = filter_var($params['offset'], FILTER_VALIDATE_INT);
        
        if ($offset === false || $offset < 0) {
            return 0;
        }
        
        return $offset;
    }
}
