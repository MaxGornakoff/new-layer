<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\SiteSetting */
class SiteSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'logo' => $this->logo,
            'logo_url' => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'favicon' => $this->favicon,
            'favicon_url' => $this->favicon ? Storage::disk('public')->url($this->favicon) : null,
            'catalog_auto_apply_filters' => (bool) $this->catalog_auto_apply_filters,
            'hero_slider_autoplay' => (bool) $this->hero_slider_autoplay,
            'hero_slider_autoplay_interval' => (int) ($this->hero_slider_autoplay_interval ?: 6),
        ];
    }
}
