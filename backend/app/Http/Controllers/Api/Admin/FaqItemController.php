<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = FaqItem::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $item = FaqItem::query()->create($this->validatedData($request));

        return response()->json(['data' => $item], 201);
    }

    public function update(Request $request, FaqItem $faqItem): JsonResponse
    {
        $faqItem->update($this->validatedData($request));

        return response()->json(['data' => $faqItem->fresh()]);
    }

    public function destroy(FaqItem $faqItem): JsonResponse
    {
        $faqItem->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:10000'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
