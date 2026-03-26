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
        'stock',
        'product_image',
    ];

    protected $casts = [
        'price' => 'double',
        'discount' => 'double',
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

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function colors()
    {
        // Specify the pivot table name and the foreign keys
        // Also, specify the additional columns from the pivot table you want to retrieve
        return $this->belongsToMany(Color::class, 'product_colors', 'product_id', 'color_id')
            ->withPivot('image_url') // Crucial: This tells Eloquent to retrieve the 'image_url' from the pivot table
            ->withTimestamps(); // If you want created_at/updated_at from pivot
    }

    // public function colors()
    // {
    //     // Specify the pivot table name and the foreign keys
    //     // Also, specify the additional columns from the pivot table you want to retrieve
    //     return $this->belongsToMany(Color::class, 'product_colors', 'product_id', 'color_id')
    //         ->withPivot('image_url') // Crucial: This tells Eloquent to retrieve the 'image_url' from the pivot table
    //         ->as('product_color')
    //         ->withTimestamps(); // If you want created_at/updated_at from pivot
    //     // ->afterQuery(fn($models) => $models->makeHidden(['created_at', 'updated_at', 'id']));
    // }

    public function sizes()
    {
        // Specify the pivot table (product_variants), the local key (product_id), and the foreign key (size_id)
        return $this->belongsToMany(Size::class, 'product_variants', 'product_id', 'size_id')
            ->withPivot('color_id')
            ->as('product_variant')
            ->withTimestamps();
    }

    // public function productColors()
    // {
    //     return $this->hasMany(ProductColor::class);
    // }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
