<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'compare_at_price',
        'compare_at_markup_percent',
        'color',
        'diameter',
        'weight_grams',
        'stock_quantity',
        'is_active',
        'image',
        'images',
        'video',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'compare_at_markup_percent' => 'decimal:2',
            'diameter' => 'decimal:2',
            'images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function compareAtPriceDisplay(): ?string
    {
        $price = (float) $this->price;

        if ($this->compare_at_price !== null) {
            $compare = (float) $this->compare_at_price;

            return $compare > $price ? number_format($compare, 2, '.', '') : null;
        }

        if ($this->compare_at_markup_percent !== null && (float) $this->compare_at_markup_percent > 0) {
            $compare = round($price * (1 + ((float) $this->compare_at_markup_percent / 100)), 2);

            return $compare > $price ? number_format($compare, 2, '.', '') : null;
        }

        return null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
