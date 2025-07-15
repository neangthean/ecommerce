<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutoSlider extends Model
{
    //
    use HasFactory;

    protected $table = 'auto_sliders';

    protected $fillable = [
        'image_url',
        'title',
        'sub_title',
    ];
}
