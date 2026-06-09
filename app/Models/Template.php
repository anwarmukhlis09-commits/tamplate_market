<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'short_desc',
        'long_desc',
        'price',
        'discount_price',
        'badge',
        'features',
        'status',
        'allow_edit_before_checkout',
        'preview_image',
        'preview_gradients',
        'zip_file',
        'sold_count',
        'rating',
    ];

    protected $casts = [
        'price' => 'integer',
        'discount_price' => 'integer',
        'features' => 'array',
        'preview_gradients' => 'array',
        'allow_edit_before_checkout' => 'boolean',
        'sold_count' => 'integer',
        'rating' => 'decimal:1',
    ];
}
