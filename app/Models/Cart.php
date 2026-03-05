<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'color_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'price' => 'double',
    ];

    /**
     * A cart belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A cart belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * A cart belongs to a color.
     */
    public function color()
    {
        return $this->belongsTo(Color::class);
    }
}
