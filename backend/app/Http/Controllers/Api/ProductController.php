<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function filters(): JsonResponse
    {
        $base = Product::query()->where('is_active', true);

        $colors = (clone $base)
            ->whereNotNull('color')
            ->where('color', '!=', '')
            ->distinct()
            ->orderBy('color')
            ->pluck('color')
            ->values()
            ->all();

        $diameters = (clone $base)
            ->whereNotNull('diameter')
            ->distinct()
            ->orderBy('diameter')
            ->pluck('diameter')
            ->map(fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') ?: '0')
            ->values()
            ->all();

        $weights = (clone $base)
            ->whereNotNull('weight_grams')
            ->distinct()
            ->orderBy('weight_grams')
            ->pluck('weight_grams')
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'groups' => array_values(array_filter([
                    [
                        'key' => 'color',
                        'title' => 'Цвет',
                        'options' => array_map(
                            static fn (string $color) => ['value' => $color, 'label' => $color],
                            $colors,
                        ),
                    ],
                    [
                        'key' => 'diameter',
                        'title' => 'Диаметр',
                        'options' => array_map(
                            static fn (string $diameter) => [
                                'value' => $diameter,
                                'label' => "{$diameter} мм",
                            ],
                            $diameters,
                        ),
                    ],
                    [
                        'key' => 'weight',
                        'title' => 'Вес катушки',
                        'options' => array_map(
                            static fn (int $weight) => [
                                'value' => (string) $weight,
                                'label' => "{$weight} г",
                            ],
                            $weights,
                        ),
                    ],
                ], static fn (array $group) => $group['options'] !== [])),
                // backward-compatible flat lists
                'colors' => $colors,
                'diameters' => $diameters,
                'weights' => $weights,
            ],
        ]);
    }

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

        $categories = $this->requestList($request, 'category');
        if ($categories !== []) {
            $query->whereHas('category', function ($builder) use ($categories) {
                $builder->whereIn('slug', $categories);
            });
        }

        $colors = $this->requestList($request, 'color');
        if ($colors !== []) {
            $query->whereIn('color', $colors);
        }

        $diameters = $this->requestList($request, 'diameter');
        if ($diameters !== []) {
            $query->whereIn('diameter', $diameters);
        }

        $weights = $this->requestList($request, 'weight');
        if ($weights !== []) {
            $query->whereIn('weight_grams', array_map('intval', $weights));
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

        return ProductResource::collection($products)->response();
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->is_active) {
            abort(404);
        }

        $product->load('category:id,name,slug');

        return (new ProductResource($product))->response();
    }

    /**
     * @return list<string>
     */
    private function requestList(Request $request, string $key): array
    {
        $value = $request->input($key);

        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            $value = explode(',', (string) $value);
        }

        return array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            $value,
        ), static fn ($item) => $item !== ''));
    }
}
