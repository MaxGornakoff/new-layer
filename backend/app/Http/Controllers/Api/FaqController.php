<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaqItem;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        $items = FaqItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'question', 'answer', 'sort_order']);

        return response()->json(['data' => $items]);
    }
}
