<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with('category:id,name,slug')
            ->where('is_active', true);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->trim()->toString()) {
            $query->join('categories', 'products.category_id', '=', 'categories.id')
                ->where('categories.slug', $category)
                ->select('products.*');
        }

        if ($color = $request->string('color')->trim()->toString()) {
            $query->where('color', $color);
        }

        if ($request->filled('diameter')) {
            $query->where('diameter', $request->input('diameter'));
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock_quantity', '>', 0);
        }

        $sort = $request->string('sort', 'name')->toString();
        $direction = $request->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, ['name', 'price', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name');
        }

        $products = $query->paginate($request->integer('per_page', 12));

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load('category:id,name,slug');

        return response()->json(['data' => $product]);
    }
}
