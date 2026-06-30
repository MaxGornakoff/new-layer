<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuSection;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = MenuSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $itemsByParent = MenuItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->groupBy('parent_key');

        $data = $sections->map(function (MenuSection $section) use ($itemsByParent) {
            $children = ($itemsByParent[$section->key] ?? collect())->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'url' => $item->url,
                'open_in_new_tab' => $item->open_in_new_tab,
            ])->values();

            return [
                'id' => $section->id,
                'key' => $section->key,
                'title' => $section->title,
                'url' => $section->url,
                'type' => $section->type,
                'include_categories' => $section->include_categories,
                'open_in_new_tab' => $section->open_in_new_tab,
                'children' => $children,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
