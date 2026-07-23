<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteSettingResource;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function show(): SiteSettingResource
    {
        return new SiteSettingResource(SiteSetting::current());
    }

    public function update(Request $request): JsonResponse
    {
        $messengers = $this->parseMessengers($request);

        if ($messengers !== null) {
            $request->merge([
                'contact_messengers' => $messengers,
            ]);
        }

        $validated = $request->validate([
            'logo' => ['nullable', 'file', 'mimes:svg,png', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:svg,png', 'max:1024'],
            'catalog_auto_apply_filters' => ['sometimes', 'boolean'],
            'hero_slider_autoplay' => ['sometimes', 'boolean'],
            'hero_slider_autoplay_interval' => ['sometimes', 'integer', 'min:2', 'max:120'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'contact_email_business' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_email_support' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_messengers' => ['sometimes', 'array', 'max:20'],
            'contact_messengers.*.label' => ['required_with:contact_messengers', 'string', 'max:64'],
            'contact_messengers.*.icon' => [
                'required_with:contact_messengers',
                'string',
                'max:64',
                'regex:/^[a-z0-9_-]+$/',
            ],
            'contact_messengers.*.url' => ['required_with:contact_messengers', 'string', 'max:2048'],
        ]);

        $settings = SiteSetting::current();

        if ($request->has('catalog_auto_apply_filters')) {
            $settings->catalog_auto_apply_filters = $request->boolean('catalog_auto_apply_filters');
        }

        if ($request->has('hero_slider_autoplay')) {
            $settings->hero_slider_autoplay = $request->boolean('hero_slider_autoplay');
        }

        if ($request->has('hero_slider_autoplay_interval')) {
            $settings->hero_slider_autoplay_interval = $request->integer('hero_slider_autoplay_interval');
        }

        if ($request->exists('contact_phone')) {
            $settings->contact_phone = $validated['contact_phone'] ?: null;
        }

        if ($request->exists('contact_email_business')) {
            $settings->contact_email_business = $validated['contact_email_business'] ?: null;
        }

        if ($request->exists('contact_email_support')) {
            $settings->contact_email_support = $validated['contact_email_support'] ?: null;
        }

        if ($messengers !== null) {
            $settings->contact_messengers = $validated['contact_messengers'] ?? [];
        }

        if ($request->hasFile('logo')) {
            $this->deleteFile($settings->logo);
            $settings->logo = $request->file('logo')->store('site/logo', 'public');
        }

        if ($request->hasFile('favicon')) {
            $this->deleteFile($settings->favicon);
            $settings->favicon = $request->file('favicon')->store('site/favicon', 'public');
        }

        $settings->save();

        return (new SiteSettingResource($settings))->response();
    }

    /**
     * @return list<array{label: string, icon: string, url: string}>|null
     */
    private function parseMessengers(Request $request): ?array
    {
        if (! $request->exists('contact_messengers') && ! $request->has('contact_messengers')) {
            return null;
        }

        $raw = $request->input('contact_messengers');

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => [
                'label' => trim((string) ($item['label'] ?? '')),
                'icon' => trim((string) ($item['icon'] ?? '')),
                'url' => trim((string) ($item['url'] ?? '')),
            ])
            ->values()
            ->all();
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
