<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HeroSlideResource;
use App\Models\HeroSlide;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class HeroSlideController extends Controller
{
    public function index(): JsonResponse
    {
        $slides = HeroSlide::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $settings = SiteSetting::current();

        return HeroSlideResource::collection($slides)->additional([
            'meta' => [
                'autoplay' => (bool) $settings->hero_slider_autoplay,
                'autoplay_interval_seconds' => (int) ($settings->hero_slider_autoplay_interval ?: 6),
            ],
        ])->response();
    }
}
