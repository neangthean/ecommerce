<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    // Since you are using a composite primary key, 
    // extending 'Pivot' is often cleaner than 'Model'.

    protected $table = 'product_colors';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'color_id',
        'image_url',
    ];

    /**
     * Indicate if the IDs are auto-incrementing.
     * Set to false because of the composite primary key.
     */
    public $incrementing = false;

    /**
     * Define the relationships if you need to access 
     * the parent models directly from the pivot.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }
}
