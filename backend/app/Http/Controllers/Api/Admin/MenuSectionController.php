<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuSectionController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = MenuSection::query()
            ->orderByRaw("CASE placement WHEN 'header' THEN 0 WHEN 'footer' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return response()->json(['data' => $sections]);
    }

    public function store(Request $request): JsonResponse
    {
        $section = MenuSection::create($this->validatedData($request));

        return response()->json(['data' => $section], 201);
    }

    public function update(Request $request, MenuSection $menuSection): JsonResponse
    {
        $menuSection->update($this->validatedData($request, $menuSection));

        return response()->json(['data' => $menuSection->fresh()]);
    }

    public function destroy(MenuSection $menuSection): JsonResponse
    {
        MenuItem::query()->where('parent_key', $menuSection->key)->delete();
        $menuSection->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validatedData(Request $request, ?MenuSection $menuSection = null): array
    {
        return $request->validate([
            'key' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('menu_sections', 'key')->ignore($menuSection?->id),
            ],
            'placement' => ['required', Rule::in(MenuSection::PLACEMENTS)],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'type' => ['required', Rule::in(MenuSection::TYPES)],
            'include_categories' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'open_in_new_tab' => ['sometimes', 'boolean'],
        ]);
    }
}
