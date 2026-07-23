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
            'contact_phone' => $this->contact_phone,
            'contact_email_business' => $this->contact_email_business,
            'contact_email_support' => $this->contact_email_support,
            'contact_messengers' => collect($this->contact_messengers ?? [])
                ->filter(fn ($item) => is_array($item) && filled($item['url'] ?? null) && filled($item['icon'] ?? null))
                ->map(fn (array $item) => [
                    'label' => (string) ($item['label'] ?? $item['icon']),
                    'icon' => (string) $item['icon'],
                    'url' => (string) $item['url'],
                ])
                ->values()
                ->all(),
        ];
    }
}
