<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuSection extends Model
{
    public const TYPE_LINK = 'link';

    public const TYPE_DROPDOWN = 'dropdown';

    public const TYPES = [
        self::TYPE_LINK,
        self::TYPE_DROPDOWN,
    ];

    protected $fillable = [
        'key',
        'title',
        'url',
        'type',
        'include_categories',
        'sort_order',
        'is_active',
        'open_in_new_tab',
    ];

    protected function casts(): array
    {
        return [
            'include_categories' => 'boolean',
            'is_active' => 'boolean',
            'open_in_new_tab' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_key', 'key');
    }
}
