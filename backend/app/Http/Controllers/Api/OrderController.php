<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private const ORDER_QUANTITY_STEP = 10;

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['items.product:id,slug'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $order->load(['items.product:id,slug']);

        return response()->json(['data' => $order]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['required', 'string'],
            'comment' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $totalQuantity = collect($data['items'])->sum('quantity');

        if ($totalQuantity % self::ORDER_QUANTITY_STEP !== 0) {
            return response()->json([
                'message' => 'Заказ оформляется кратно 10 катушкам. Измените количество в корзине.',
            ], 422);
        }

        $order = DB::transaction(function () use ($data, $request) {
            $productIds = collect($data['items'])->pluck('product_id');
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $orderItems = [];

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    abort(422, 'Товар недоступен.');
                }

                if ($product->stock_quantity < $item['quantity']) {
                    abort(422, "Недостаточно товара на складе: {$product->name}");
                }

                $lineTotal = $product->price * $item['quantity'];
                $total += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ];

                $product->decrement('stock_quantity', $item['quantity']);
            }

            $client = Client::updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'email' => $data['customer_email'] ?? $request->user()->email,
                    'address' => $data['delivery_address'],
                ]
            );

            $order = Order::create([
                'number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'user_id' => $request->user()->id,
                'client_id' => $client->id,
                'status' => Order::STATUS_NEW,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? $request->user()->email,
                'delivery_address' => $data['delivery_address'],
                'comment' => $data['comment'] ?? null,
                'total' => $total,
            ]);

            $order->items()->createMany($orderItems);

            return $order->load('items');
        });

        return response()->json(['data' => $order], 201);
    }
}
