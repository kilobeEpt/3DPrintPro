<?php

namespace App\Models;

/**
 * ContentBlock Model
 * 
 * Represents a dynamic content block for pages.
 * 
 * @property int $id
 * @property string $block_name
 * @property string|null $title
 * @property string|null $content
 * @property array|null $data
 * @property string|null $page
 * @property int $sort_order
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ContentBlock extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'content_blocks';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'block_name',
        'title',
        'content',
        'data',
        'page',
        'sort_order',
        'active',
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Scope a query to filter by page.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $page
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePage($query, $page)
    {
        return $query->where('page', $page);
    }
    
    /**
     * Get a content block by name.
     *
     * @param  string  $blockName
     * @return static|null
     */
    public static function findByName($blockName)
    {
        return static::where('block_name', $blockName)->first();
    }
}
