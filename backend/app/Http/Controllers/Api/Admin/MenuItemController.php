<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MenuItem::query()
            ->orderBy('parent_key')
            ->orderBy('sort_order')
            ->orderBy('title');

        if ($parentKey = $request->string('parent_key')->trim()->toString()) {
            $query->where('parent_key', $parentKey);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $item = MenuItem::create($this->validatedData($request));

        return response()->json(['data' => $item], 201);
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $menuItem->update($this->validatedData($request));

        return response()->json(['data' => $menuItem->fresh()]);
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $menuItem->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'parent_key' => [
                'required',
                'string',
                'max:64',
                Rule::exists('menu_sections', 'key'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'open_in_new_tab' => ['sometimes', 'boolean'],
        ]);
    }
}
