<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paths = is_array($this->images ?? null) ? $this->images : [];
        $paths = array_values(array_filter($paths, fn ($p) => is_string($p) && trim($p) !== ''));

        if (count($paths) === 0 && filled($this->image)) {
            $paths = [$this->image];
        }

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'compare_at_markup_percent' => $this->compare_at_markup_percent,
            'compare_at_price_display' => $this->compareAtPriceDisplay(),
            'color' => $this->color,
            'diameter' => $this->diameter,
            'weight_grams' => $this->weight_grams,
            'stock_quantity' => $this->stock_quantity,
            'is_active' => $this->is_active,
            'image' => $this->image,
            'image_url' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'images' => array_map(
                fn (string $path) => [
                    'path' => $path,
                    'url' => Storage::disk('public')->url($path),
                ],
                $paths,
            ),
            'video' => $this->video,
            'video_url' => $this->video ? Storage::disk('public')->url($this->video) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
