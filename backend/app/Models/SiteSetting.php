<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'logo',
        'favicon',
        'catalog_auto_apply_filters',
        'hero_slider_autoplay',
        'hero_slider_autoplay_interval',
    ];

    protected function casts(): array
    {
        return [
            'catalog_auto_apply_filters' => 'boolean',
            'hero_slider_autoplay' => 'boolean',
            'hero_slider_autoplay_interval' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
