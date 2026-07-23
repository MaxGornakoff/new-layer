<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $placement = $request->validate([
            'placement' => ['sometimes', 'string', Rule::in(MenuSection::PLACEMENTS)],
        ])['placement'] ?? MenuSection::PLACEMENT_HEADER;

        $sections = MenuSection::query()
            ->where('is_active', true)
            ->where('placement', $placement)
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
                'placement' => $section->placement,
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
