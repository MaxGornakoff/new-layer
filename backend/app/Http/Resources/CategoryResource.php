<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sort_order' => $this->sort_order,
            'image' => $this->image,
            'image_url' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'advantages' => $this->formatAdvantages(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function formatAdvantages(): array
    {
        return collect($this->advantages ?? [])
            ->map(function (array $advantage) {
                $icon = $advantage['icon'] ?? null;

                return [
                    'text' => $advantage['text'] ?? '',
                    'icon' => $icon,
                    'icon_url' => $icon ? Storage::disk('public')->url($icon) : null,
                ];
            })
            ->values()
            ->all();
    }
}
