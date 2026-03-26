<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAddress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'address_name',
        'formatted_address',
        'latitude',
        'longitude',
        'city',
        'postal_code',
        'google_place_id',
    ];

    /**
     * The attributes that should be cast.
     * * Using 'float' or 'decimal:8' ensures the machine-part 
     * coordinates maintain their precision when accessed.
     */
    protected $casts = [
        'latitude'  => 'double',
        'longitude' => 'double',
    ];

    /**
     * Get the order that owns the delivery address.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}