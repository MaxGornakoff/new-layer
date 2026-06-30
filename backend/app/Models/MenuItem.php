<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = [
        'parent_key',
        'title',
        'url',
        'sort_order',
        'is_active',
        'open_in_new_tab',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'open_in_new_tab' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(MenuSection::class, 'parent_key', 'key');
    }
}
