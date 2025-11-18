<?php

namespace App\Models;

/**
 * Testimonial Model
 * 
 * Represents a customer testimonial/review in the system.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $position
 * @property string|null $avatar
 * @property string $text
 * @property int $rating
 * @property int $sort_order
 * @property bool $approved
 * @property bool $active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Testimonial extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'testimonials';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'position',
        'avatar',
        'text',
        'rating',
        'sort_order',
        'approved',
        'active',
    ];
    
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'rating' => 'integer',
        'sort_order' => 'integer',
        'approved' => 'boolean',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Scope a query to only include approved testimonials.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }
    
    /**
     * Scope a query to filter by minimum rating.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minRating
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMinRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }
}
