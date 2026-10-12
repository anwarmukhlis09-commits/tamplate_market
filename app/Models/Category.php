<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
    ];

    /**
     * Get templates belonging to this category.
     * Note: templates table stores the category slug in the 'category' column.
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, 'category', 'slug');
    }
}
