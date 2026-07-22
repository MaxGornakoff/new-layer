<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'total' => 'total',
            'created_at' => 'created_at',
            'status' => 'status',
        ];

        $sortColumn = $sortable[$sort] ?? 'created_at';

        $orders = Order::query()
            ->with(['items', 'client:id,name,phone,email'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy($sortColumn, $direction)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($orders);
    }

    public function newCount(): JsonResponse
    {
        $count = Order::query()
            ->where('status', Order::STATUS_NEW)
            ->count();

        return response()->json(['data' => ['count' => $count]]);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['items', 'client', 'user:id,name,email']);

        return response()->json(['data' => $order]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                Order::STATUS_NEW,
                Order::STATUS_CONFIRMED,
                Order::STATUS_PROCESSING,
                Order::STATUS_SHIPPED,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ])],
        ]);

        $order->update(['status' => $data['status']]);

        return response()->json(['data' => $order->fresh(['items', 'client'])]);
    }

    public function destroy(Order $order): JsonResponse
    {
        $order->delete();

        return response()->json(['message' => 'Заказ удалён.']);
    }
}
