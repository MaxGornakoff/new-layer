<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MenuItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title');

        if ($parentKey = $request->string('parent_key')->trim()->toString()) {
            $query->where('parent_key', $parentKey);
        }

        return response()->json(['data' => $query->get()]);
    }
}
