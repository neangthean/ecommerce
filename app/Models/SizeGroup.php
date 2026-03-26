<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SizeGroup extends Model
{
    protected $fillable = [
        'name'
    ];

    public function sizes(): HasMany
    {
        return $this->hasMany(Size::class)->orderBy('sort_order');
    }
}
