<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Delivery\DeliveryCalculator;
use App\Services\Delivery\DeliveryLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class DeliveryController extends Controller
{
    public function providers(): JsonResponse
    {
        return response()->json([
            'data' => DeliveryLocationService::make()->listCheckoutProviders(),
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['nullable', 'string', 'max:64'],
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        try {
            $cities = filled($data['provider'] ?? null)
                ? DeliveryLocationService::make()->searchCities($data['provider'], $data['query'])
                : DeliveryLocationService::make()->searchCitiesUnified($data['query']);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $cities]);
    }

    public function pickupPoints(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:64'],
            'city_guid' => ['nullable', 'string', 'max:64'],
            'city_id' => ['nullable', 'string', 'max:64'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $points = DeliveryLocationService::make()->getPickupPoints(
                $data['provider'],
                $data['city_guid'] ?? null,
                $data['query'] ?? null,
                $data['city_name'] ?? null,
                $data['city_id'] ?? null,
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $points]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'destination_city' => ['required', 'string', 'max:255'],
            'destination_city_guid' => ['nullable', 'string', 'max:64'],
            'destination_city_id' => ['nullable', 'string', 'max:64'],
            'destination_terminal_id' => ['nullable', 'string', 'max:64'],
            'destination_postal_code' => ['nullable', 'string', 'max:16'],
            'total_quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $result = DeliveryCalculator::make()->calculate(
                $data['destination_city'],
                $data['destination_postal_code'] ?? null,
                (int) $data['total_quantity'],
                $data['destination_city_guid'] ?? null,
                $data['destination_city_id'] ?? null,
                $data['destination_terminal_id'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json(['data' => $result]);
    }
}
