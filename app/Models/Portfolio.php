<?php

namespace App\Models;

/**
 * Portfolio Model
 * 
 * Represents a portfolio project/case study in the system.
 * 
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $image_url
 * @property string|null $image_path
 * @property int|null $image_size
 * @property string|null $image_mime
 * @property string|null $category
 * @property array|null $tags
 * @property int $sort_order
 * @property bool $active
 * @property bool $featured
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Portfolio extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'portfolio';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'image_url',
        'image_path',
        'image_size',
        'image_mime',
        'category',
        'tags',
        'sort_order',
        'active',
        'featured',
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'featured' => 'boolean',
        'sort_order' => 'integer',
        'image_size' => 'integer',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Scope a query to only include featured portfolio items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
    
    /**
     * Scope a query to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
