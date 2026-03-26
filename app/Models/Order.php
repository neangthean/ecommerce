<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'shipping_address',
        'payment_method',
        'payment_status',
    ];

    /**
     * The attributes that should be cast.
     * * This is the "magic" that allows you to treat the JSON 
     * column as a PHP array automatically.
     */
    protected $casts = [
        'shipping_address' => 'array',
        'subtotal' => 'double',
        'total_amount' => 'double',
        'discount_amount' => 'double',
        'shipping_cost' => 'double',
    ];

    /**
     * Get the user who placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items (products) for this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tracking(): HasOne
    {
        return $this->hasOne(ShippingTracking::class);
    }

    // ADDED: Relationship for the delivery_addresses table
    public function deliveryAddress(): HasOne
    {
        return $this->hasOne(DeliveryAddress::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
