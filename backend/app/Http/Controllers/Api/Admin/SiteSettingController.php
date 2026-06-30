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
        $request->validate([
            'logo' => ['nullable', 'file', 'mimes:svg,png', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:svg,png', 'max:1024'],
            'catalog_auto_apply_filters' => ['sometimes', 'boolean'],
            'hero_slider_autoplay' => ['sometimes', 'boolean'],
            'hero_slider_autoplay_interval' => ['sometimes', 'integer', 'min:2', 'max:120'],
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

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
