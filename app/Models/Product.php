<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // name of table, but it not define name of table is we know by name model 
    // Product to products. products is name of table
    // User to users. users is name of table
    // Category to categories. categories is name of table
    protected $table = 'products';

    protected $fillable = [
        'title',
        'sub_title',
        'discount',
        'category_id',
        'price',
        'product_image',
    ];

    /**
     * Define a relationship with the Category model.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Define the many-to-many relationship with Color.
     * A product can have many colors, each with a specific image.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function colors()
    {
        // Specify the pivot table name and the foreign keys
        // Also, specify the additional columns from the pivot table you want to retrieve
        return $this->belongsToMany(Color::class, 'product_colors', 'product_id', 'color_id')
            ->withPivot('image_url') // Crucial: This tells Eloquent to retrieve the 'image_url' from the pivot table
            ->withTimestamps(); // If you want created_at/updated_at from pivot
    }
}
