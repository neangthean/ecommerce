<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'color_id',
        'size_id',
        'quantity',
        'price',
        'product_name',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'double',
        'quantity' => 'integer',
    ];

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the original product.
     * Note: This will return null if the product was deleted 
     * because of your 'onDelete(set null)' migration setting.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the color selected for this item.
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * Get the size selected for this item.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
