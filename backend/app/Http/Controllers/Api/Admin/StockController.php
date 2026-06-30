<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string'],
        ]);

        $product = DB::transaction(function () use ($data, $product, $request) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $newQuantity = $locked->stock_quantity + $data['quantity'];

            if ($newQuantity < 0) {
                abort(422, 'Остаток не может быть отрицательным.');
            }

            $type = match (true) {
                $data['quantity'] > 0 => StockMovement::TYPE_IN,
                default => StockMovement::TYPE_OUT,
            };

            StockMovement::create([
                'product_id' => $locked->id,
                'quantity_change' => $data['quantity'],
                'type' => $type,
                'note' => $data['note'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            $locked->update(['stock_quantity' => $newQuantity]);

            return $locked->fresh();
        });

        return response()->json(['data' => $product]);
    }
}
