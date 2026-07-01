<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'home_title',
        'home_bg_color',
        'home_bg_color_end',
        'slug',
        'sort_order',
        'image',
        'advantages',
    ];

    protected function casts(): array
    {
        return [
            'advantages' => 'array',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
