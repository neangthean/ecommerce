<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingTracking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'carrier',
        'tracking_number',
        'status',
        'estimated_delivery',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'estimated_delivery' => 'datetime',
    ];

    /**
     * Get the order associated with the shipping tracking.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
