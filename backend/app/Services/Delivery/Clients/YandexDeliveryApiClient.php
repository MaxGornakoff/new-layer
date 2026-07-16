<?php

namespace App\Services\Delivery\Clients;

use App\Models\DeliverySetting;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use App\Services\Delivery\DTO\ProviderSenderPoint;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YandexDeliveryApiClient
{
    public function __construct(
        private readonly string $token,
        private readonly string $baseUrl,
    ) {}

    public static function fromSettings(DeliverySetting $settings): self
    {
        return new self(
            token: (string) $settings->resolveYandexDeliveryToken(),
            baseUrl: rtrim($settings->resolveYandexDeliveryApiBaseUrl(), '/'),
        );
    }

    public function detectLocation(string $query): array
    {
        if (mb_strlen(trim($query)) < 2) {
            return [];
        }

        $response = $this->http()->post("{$this->baseUrl}/api/b2b/platform/location/detect", [
            'location' => trim($query),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractError($response->json(), 'не удалось найти города'));
        }

        return collect($response->json('variants', []))
            ->filter(fn ($variant) => is_array($variant) && isset($variant['geo_id']))
            ->take(12)
            ->map(function (array $variant) {
                $address = (string) ($variant['address'] ?? '');
                $name = $this->extractCityName($address);

                return [
                    'id' => (string) $variant['geo_id'],
                    'name' => $name,
                    'label' => $address !== '' ? $address : $name,
                    'source' => 'yandex_delivery',
                ];
            })
            ->filter(fn (array $city) => $city['name'] !== '')
            ->values()
            ->all();
    }

    public function resolveGeoId(string $cityName): ?string
    {
        $cities = $this->detectLocation($cityName);
        $normalized = mb_strtolower(trim($cityName));

        $match = collect($cities)->first(function (array $city) use ($normalized) {
            return mb_strtolower($city['name']) === $normalized
                || mb_stripos($city['label'], $normalized) !== false;
        }) ?? ($cities[0] ?? null);

        return $match['id'] ?? null;
    }

    public function resolveGeoIdFromIdOrName(?string $cityId, ?string $cityName): ?string
    {
        if (filled($cityName)) {
            $fromName = $this->resolveGeoId($cityName);

            if (filled($fromName)) {
                return $fromName;
            }
        }

        if (filled($cityId) && $this->looksLikeGeoId($cityId)) {
            return $cityId;
        }

        return null;
    }

    public function getPickupPointsForCity(?string $cityId, ?string $cityName, ?string $query = null): array
    {
        $geoId = $this->resolveGeoIdFromIdOrName($cityId, $cityName);

        if (! filled($geoId)) {
            return [];
        }

        return $this->getPickupPoints($geoId, $query);
    }

    public function getPickupPoints(int|string $geoId, ?string $query = null): array
    {
        $response = $this->http()->post("{$this->baseUrl}/api/b2b/platform/pickup-points/list", [
            'geo_id' => (int) $geoId,
            'type' => 'pickup_point',
            'payment_method' => 'already_paid',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractError($response->json(), 'не удалось загрузить пункты выдачи'));
        }

        return collect($response->json('points', []))
            ->filter(fn ($point) => is_array($point))
            ->map(function (array $point) {
                $name = trim((string) ($point['name'] ?? 'Пункт выдачи'));
                $address = trim((string) ($point['address']['full_address'] ?? $point['address']['locality'] ?? ''));

                return [
                    'id' => (string) ($point['id'] ?? ''),
                    'name' => $name,
                    'address' => $address,
                    'label' => $address !== '' ? "{$name} — {$address}" : $name,
                ];
            })
            ->filter(function (array $point) use ($query) {
                if ($point['id'] === '') {
                    return false;
                }

                if ($query === null || trim($query) === '') {
                    return true;
                }

                return mb_stripos($point['label'], trim($query)) !== false;
            })
            ->values()
            ->take(20)
            ->all();
    }

    public function calculate(
        DeliveryQuoteRequest $request,
        ProviderSenderPoint $sender,
        ?string $destinationStationId = null,
    ): array {
        if (! filled($sender->terminalId)) {
            throw new RuntimeException('Укажите ID склада Яндекс Доставки (platform_station_id) в пункте отправления.');
        }

        $places = [];
        for ($i = 0; $i < $request->packagesCount; $i++) {
            $places[] = [
                'physical_dims' => [
                    'dx' => max(1, (int) round($request->package->lengthCm)),
                    'dy' => max(1, (int) round($request->package->widthCm)),
                    'dz' => max(1, (int) round($request->package->heightCm)),
                    'weight_gross' => max(1, (int) round($request->package->weightKg * 1000)),
                ],
            ];
        }

        $destination = filled($destinationStationId)
            ? ['platform_station_id' => $destinationStationId]
            : ['address' => $this->formatDestinationAddress($request)];

        $response = $this->http()->post("{$this->baseUrl}/api/b2b/platform/pricing-calculator", [
            'source' => [
                'platform_station_id' => $sender->terminalId,
            ],
            'destination' => $destination,
            'tariff' => filled($destinationStationId) ? 'self_pickup' : 'time_interval',
            'total_weight' => max(1, (int) round($request->package->weightKg * $request->packagesCount * 1000)),
            'payment_method' => 'already_paid',
            'places' => $places,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractError($response->json(), 'не удалось рассчитать доставку'));
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['pricing_total'])) {
            throw new RuntimeException($this->extractError($data, 'Яндекс Доставка не вернула стоимость.'));
        }

        return $data;
    }

    public function parsePrice(string $pricingTotal): float
    {
        if (preg_match('/([\d.,]+)/', $pricingTotal, $matches) !== 1) {
            throw new RuntimeException('Яндекс Доставка вернула нераспознаваемую стоимость.');
        }

        return (float) str_replace(',', '.', $matches[1]);
    }

    private function formatDestinationAddress(DeliveryQuoteRequest $request): string
    {
        $city = trim($request->destinationCity);

        if (filled($request->destinationPostalCode)) {
            return trim($request->destinationPostalCode).', '.$city;
        }

        return $city;
    }

    private function looksLikeGeoId(string $value): bool
    {
        return ctype_digit($value) && strlen($value) >= 5;
    }

    private function extractCityName(string $address): string
    {
        $address = trim($address);

        if ($address === '') {
            return '';
        }

        $parts = array_map('trim', explode(',', $address));

        return end($parts) ?: $address;
    }

    private function http()
    {
        return Http::acceptJson()
            ->withToken($this->token)
            ->timeout(25);
    }

    private function extractError(mixed $payload, string $fallback): string
    {
        if (is_array($payload)) {
            $message = $payload['message']
                ?? $payload['error']
                ?? $payload['errors'][0]['message']
                ?? $payload['errors'][0]['detail']
                ?? null;

            if (is_string($message) && $message !== '') {
                return 'Яндекс Доставка: '.$message;
            }
        }

        $fallback = trim($fallback);

        return $fallback !== '' ? 'Яндекс Доставка: '.$fallback : 'Яндекс Доставка вернула ошибку.';
    }
}
