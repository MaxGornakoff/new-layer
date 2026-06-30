<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'product_id',
        'quantity_change',
        'type',
        'note',
        'user_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
