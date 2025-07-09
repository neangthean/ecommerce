<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    protected $table = 'colors';

    protected $fillable = [
        'name',
    ];

    /**
     * Define the many-to-many relationship with Product.
     * A color can be associated with many products.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        // Include 'image_url' from the pivot table
        return $this->belongsToMany(Product::class, 'product_color', 'color_id', 'product_id')
                    ->withPivot('image_url') // Specify the pivot columns to retrieve
                    ->withTimestamps(); // If you want created_at/updated_at from pivot
    }
}