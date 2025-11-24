<?php

namespace App\Models;

/**
 * Service Model
 * 
 * Represents a service offering in the system.
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property array|null $features
 * @property string|null $price
 * @property string|null $category
 * @property int $sort_order
 * @property bool $active
 * @property bool $featured
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Service extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'services';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'features',
        'price',
        'category',
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
        'features' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];
    
    /**
     * Scope a query to only include featured services.
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
