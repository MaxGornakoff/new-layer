<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliverySettingResource;
use App\Models\DeliverySetting;
use App\Services\Delivery\Clients\BaikalApiClient;
use App\Services\Delivery\Clients\DellinApiClient;
use App\Services\Delivery\Clients\YandexDeliveryApiClient;
use App\Services\Delivery\Clients\ZheldorApiClient;
use App\Services\Delivery\DTO\CargoPackage;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliverySettingController extends Controller
{
    public function show(): DeliverySettingResource
    {
        return new DeliverySettingResource(DeliverySetting::current());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pack_units_count' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'pack_width_cm' => ['nullable', 'numeric', 'min:0.1', 'max:1000'],
            'pack_length_cm' => ['nullable', 'numeric', 'min:0.1', 'max:1000'],
            'pack_height_cm' => ['nullable', 'numeric', 'min:0.1', 'max:1000'],
            'pack_weight_kg' => ['nullable', 'numeric', 'min:0.001', 'max:1000'],
            'sender_city' => ['nullable', 'string', 'max:255'],
            'sender_postal_code' => ['nullable', 'string', 'max:16'],
            'sender_address' => ['nullable', 'string'],
            'provider_senders' => ['nullable', 'array'],
            'provider_senders.*.city' => ['nullable', 'string', 'max:255'],
            'provider_senders.*.postal_code' => ['nullable', 'string', 'max:16'],
            'provider_senders.*.address' => ['nullable', 'string'],
            'provider_senders.*.terminal_id' => ['nullable', 'string', 'max:64'],
            'baikal_enabled' => ['sometimes', 'boolean'],
            'dellin_enabled' => ['sometimes', 'boolean'],
            'yandex_delivery_enabled' => ['sometimes', 'boolean'],
            'zheldor_enabled' => ['sometimes', 'boolean'],
            'cdek_enabled' => ['sometimes', 'boolean'],
            'baikal_api_key' => ['nullable', 'string', 'max:255'],
            'dellin_app_key' => ['nullable', 'string', 'max:255'],
            'yandex_delivery_oauth_token' => ['nullable', 'string', 'max:255'],
            'zheldor_login' => ['nullable', 'string', 'max:255'],
            'zheldor_password' => ['nullable', 'string', 'max:255'],
            'cdek_client_id' => ['nullable', 'string', 'max:255'],
            'cdek_client_secret' => ['nullable', 'string', 'max:255'],
            'cdek_use_test_api' => ['sometimes', 'boolean'],
        ]);

        $settings = DeliverySetting::current();
        $settings->fill(collect($data)->except([
            'provider_senders',
            'baikal_api_key',
            'dellin_app_key',
            'yandex_delivery_oauth_token',
            'zheldor_password',
            'cdek_client_secret',
        ])->all());

        if ($request->has('provider_senders')) {
            $settings->provider_senders = $this->normalizeProviderSenders(
                $request->input('provider_senders', [])
            );
        }

        foreach ([
            'baikal_api_key',
            'dellin_app_key',
            'yandex_delivery_oauth_token',
            'zheldor_password',
            'cdek_client_secret',
        ] as $secretField) {
            if ($request->filled($secretField)) {
                $settings->{$secretField} = $request->string($secretField)->toString();
            }
        }

        $settings->save();

        return (new DeliverySettingResource($settings))->response();
    }

    public function testBaikal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'departure_city' => ['nullable', 'string', 'max:255'],
            'destination_city' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = DeliverySetting::current();

        if (! $settings->isProviderConfigured('baikal')) {
            return response()->json([
                'message' => 'Сначала сохраните API-ключ Байкал Сервис.',
            ], 422);
        }

        $sender = $settings->resolveProviderSender('baikal');

        if (! $sender) {
            return response()->json([
                'message' => 'Укажите пункт отправления для Байкал Сервис.',
            ], 422);
        }

        $departureCity = $data['departure_city'] ?? $sender->city;
        $destinationCity = $data['destination_city'] ?? 'Санкт-Петербург';

        try {
            $client = BaikalApiClient::fromSettings($settings);
            $connection = $client->testConnection($departureCity, $destinationCity);

            return response()->json([
                'message' => 'Подключение к API Байкал Сервис успешно. Города найдены в справочнике.',
                'data' => $connection,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function testDellin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'departure_city' => ['nullable', 'string', 'max:255'],
            'destination_city' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = DeliverySetting::current();

        if (! $settings->isProviderConfigured('dellin')) {
            return response()->json([
                'message' => 'Сначала сохраните App Key Деловых линий.',
            ], 422);
        }

        $sender = $settings->resolveProviderSender('dellin');
        $departureCity = $data['departure_city'] ?? $sender?->city ?? $settings->sender_city ?? 'Москва';
        $destinationCity = $data['destination_city'] ?? 'Санкт-Петербург';

        try {
            $client = DellinApiClient::fromSettings($settings);
            $departure = collect($client->searchCities($departureCity))->first();
            $destination = collect($client->searchCities($destinationCity))->first();

            if (! $departure) {
                throw new \RuntimeException("Город отправления «{$departureCity}» не найден в справочнике КЛАДР.");
            }

            if (! $destination) {
                throw new \RuntimeException("Город назначения «{$destinationCity}» не найден в справочнике КЛАДР.");
            }

            return response()->json([
                'message' => 'Подключение к API Деловых линий успешно. Города найдены в справочнике.',
                'data' => [
                    'departure_city' => $departure,
                    'destination_city' => $destination,
                ],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function testYandex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'destination_city' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = DeliverySetting::current();

        if (! $settings->isProviderConfigured('yandex_delivery')) {
            return response()->json([
                'message' => 'Сначала сохраните OAuth-токен Яндекс Доставки.',
            ], 422);
        }

        $destinationCity = $data['destination_city'] ?? 'Санкт-Петербург';

        try {
            $client = YandexDeliveryApiClient::fromSettings($settings);
            $destination = collect($client->detectLocation($destinationCity))->first();

            if (! $destination) {
                throw new \RuntimeException("Город «{$destinationCity}» не найден в справочнике Яндекс Доставки.");
            }

            $sender = $settings->resolveProviderSender('yandex_delivery');

            if (! filled($sender?->terminalId)) {
                throw new \RuntimeException(
                    'Укажите ID склада (platform_station_id) в пункте отправления. Без него тарифы Яндекс Доставки не появятся на checkout.'
                );
            }

            if (! $settings->hasCargoDimensions()) {
                throw new \RuntimeException('Сначала настройте параметры грузоместа в блоке «Упаковка».');
            }

            $request = new DeliveryQuoteRequest(
                destinationCity: $destinationCity,
                destinationPostalCode: null,
                totalQuantity: (int) $settings->pack_units_count,
                packagesCount: 1,
                package: new CargoPackage(
                    widthCm: (float) $settings->pack_width_cm,
                    lengthCm: (float) $settings->pack_length_cm,
                    heightCm: (float) $settings->pack_height_cm,
                    weightKg: (float) $settings->pack_weight_kg,
                    count: 1,
                ),
                senderCity: (string) ($sender->city ?? $settings->sender_city ?? ''),
            );

            $calculation = $client->calculate($request, $sender);

            return response()->json([
                'message' => 'Подключение к API Яндекс Доставки успешно. Калькулятор вернул тестовый тариф.',
                'data' => [
                    'destination_city' => $destination,
                    'sample_price' => $client->parsePrice((string) $calculation['pricing_total']),
                    'delivery_days' => $calculation['delivery_days'] ?? null,
                ],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function testZheldor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'departure_city' => ['nullable', 'string', 'max:255'],
            'destination_city' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = DeliverySetting::current();

        if (! $settings->isProviderConfigured('zheldor')) {
            return response()->json([
                'message' => 'Сначала сохраните логин и пароль Желдорэкспедиции.',
            ], 422);
        }

        $sender = $settings->resolveProviderSender('zheldor');
        $departureCity = $data['departure_city'] ?? $sender?->city ?? $settings->sender_city ?? 'Москва';
        $destinationCity = $data['destination_city'] ?? 'Санкт-Петербург';

        try {
            $client = ZheldorApiClient::fromSettings($settings);
            $connection = $client->testConnection($departureCity, $destinationCity);

            return response()->json([
                'message' => 'Подключение к API Желдорэкспедиции успешно. Справочник и калькулятор доступны.',
                'data' => $connection,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function normalizeProviderSenders(array $input): array
    {
        $normalized = DeliverySetting::defaultProviderSenders();

        foreach ($normalized as $provider => $defaults) {
            $providerInput = $input[$provider] ?? [];

            if (! is_array($providerInput)) {
                continue;
            }

            $normalized[$provider] = [
                'city' => trim((string) ($providerInput['city'] ?? '')),
                'postal_code' => trim((string) ($providerInput['postal_code'] ?? '')),
                'address' => trim((string) ($providerInput['address'] ?? '')),
                'terminal_id' => trim((string) ($providerInput['terminal_id'] ?? '')),
            ];
        }

        return $normalized;
    }
}
