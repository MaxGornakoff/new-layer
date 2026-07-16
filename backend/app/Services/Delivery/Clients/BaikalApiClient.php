<?php

namespace App\Services\Delivery\Clients;

use App\Models\DeliverySetting;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use App\Services\Delivery\DTO\ProviderSenderPoint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BaikalApiClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    public static function fromSettings(DeliverySetting $settings): self
    {
        return new self(
            apiKey: (string) $settings->resolveBaikalApiKey(),
            baseUrl: rtrim($settings->resolveBaikalApiBaseUrl(), '/'),
        );
    }

    public function calculate(DeliveryQuoteRequest $request, ProviderSenderPoint $sender): array
    {
        $departureGuid = $this->resolveCityGuid($sender->city);
        $destinationGuid = $request->destinationCityGuid ?: $this->resolveCityGuid($request->destinationCity);

        $lengthM = $request->package->lengthCm / 100;
        $widthM = $request->package->widthCm / 100;
        $heightM = $request->package->heightCm / 100;
        $packageVolume = $lengthM * $widthM * $heightM;
        $totalWeight = $request->package->weightKg * $request->packagesCount;
        $totalVolume = $packageVolume * $request->packagesCount;

        $payload = [
            'Departure' => [
                'CityGuid' => $departureGuid,
            ],
            'Destination' => [
                'CityGuid' => $destinationGuid,
            ],
            'Cargo' => [
                'SummaryCargo' => [
                    'Length' => round($lengthM, 3),
                    'Width' => round($widthM, 3),
                    'Height' => round($heightM, 3),
                    'Volume' => round($totalVolume, 4),
                    'Weight' => round($totalWeight, 3),
                    'Units' => $request->packagesCount,
                    'Oversized' => 0,
                    'EstimatedCost' => max(1000, (int) round($totalWeight * 100)),
                ],
            ],
            'Preference' => [
                'AuthKey' => $this->apiKey,
            ],
        ];

        $response = $this->http()
            ->post("{$this->baseUrl}/v2/calculator", $payload);

        $body = $response->json();

        if (! $response->successful()) {
            throw new RuntimeException($this->extractErrors($body, $response->body()));
        }

        $errors = collect($body['errors'] ?? [])
            ->filter(fn ($error) => is_array($error) && filled($error['description'] ?? null))
            ->pluck('description')
            ->implode(' ');

        if ($errors !== '') {
            throw new RuntimeException('Байкал Сервис: '.$errors);
        }

        if (! isset($body['total']) || ! is_numeric($body['total'])) {
            throw new RuntimeException('Байкал Сервис не вернул стоимость доставки.');
        }

        return [
            'total' => (float) $body['total'],
            'transit_days' => isset($body['transit']['int']) ? (int) $body['transit']['int'] : null,
            'departure' => $body['departure']['title'] ?? $sender->city,
            'destination' => $body['destination']['title'] ?? $request->destinationCity,
        ];
    }

    public function testConnection(string $departureCity, string $destinationCity): array
    {
        $departureGuid = $this->resolveCityGuid($departureCity);
        $destinationGuid = $this->resolveCityGuid($destinationCity);

        return [
            'departure_city' => $departureCity,
            'destination_city' => $destinationCity,
            'departure_guid' => $departureGuid,
            'destination_guid' => $destinationGuid,
            'api_base_url' => $this->baseUrl,
        ];
    }

    public function searchCities(string $query): array
    {
        if (mb_strlen(trim($query)) < 2) {
            return [];
        }

        $response = $this->http()
            ->get("{$this->baseUrl}/v2/fias/cities", [
                'text' => trim($query),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractErrors($response->json(), 'не удалось найти города'));
        }

        return $this->normalizeCities($response->json())
            ->take(12)
            ->map(fn (array $city) => [
                'id' => $city['guid'],
                'name' => $city['name'],
                'label' => $city['name'],
            ])
            ->values()
            ->all();
    }

    public function getPickupPoints(string $cityGuid, ?string $query = null): array
    {
        $response = $this->http()
            ->get("{$this->baseUrl}/v2/affiliate/find", [
                'guid' => $cityGuid,
            ]);

        if (! $response->successful()) {
            $response = $this->http()
                ->get("{$this->baseUrl}/v1/affiliate/find", [
                    'guid' => $cityGuid,
                ]);
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->extractErrors($response->json(), 'не удалось загрузить пункты выдачи'));
        }

        $terminals = collect($response->json('terminals', []))
            ->filter(fn ($terminal) => is_array($terminal));

        return $terminals
            ->map(function (array $terminal) {
                $address = trim(strip_tags((string) ($terminal['address'] ?? '')));
                $name = trim((string) ($terminal['name'] ?? 'Пункт выдачи'));

                return [
                    'id' => (string) ($terminal['id'] ?? $terminal['code'] ?? $terminal['guid_1c'] ?? $name),
                    'name' => $name,
                    'address' => $address,
                    'label' => $address !== '' ? "{$name} — {$address}" : $name,
                ];
            })
            ->filter(function (array $point) use ($query) {
                if ($query === null || trim($query) === '') {
                    return true;
                }

                return mb_stripos($point['label'], trim($query)) !== false;
            })
            ->values()
            ->take(20)
            ->all();
    }

    public function resolveCityGuid(string $city): string
    {
        return $this->resolveCityGuidPublic($city);
    }

    public function resolveCityGuidPublic(string $city): string
    {
        $response = $this->http()
            ->get("{$this->baseUrl}/v2/fias/cities", [
                'text' => trim($city),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractErrors($response->json(), "город «{$city}» не найден"));
        }

        $cities = $this->normalizeCities($response->json());

        if ($cities->isEmpty()) {
            throw new RuntimeException("Байкал Сервис: город «{$city}» не найден в справочнике.");
        }

        $normalizedCity = mb_strtolower(trim($city));

        $match = $cities->first(
            fn (array $item) => mb_strtolower((string) ($item['name'] ?? '')) === $normalizedCity
        ) ?? $cities->first();

        $guid = $match['guid'] ?? null;

        if (! is_string($guid) || $guid === '') {
            throw new RuntimeException("Байкал Сервис: не удалось определить GUID для города «{$city}».");
        }

        return $guid;
    }

    private function normalizeCities(mixed $payload): Collection
    {
        if (! is_array($payload)) {
            return collect();
        }

        if (isset($payload['guid'])) {
            return collect([$payload]);
        }

        return collect($payload)
            ->filter(fn ($item) => is_array($item) && filled($item['guid'] ?? null))
            ->values();
    }

    private function http()
    {
        return Http::withBasicAuth($this->apiKey, '')
            ->acceptJson()
            ->timeout(20);
    }

    private function extractErrors(mixed $payload, string $fallback): string
    {
        if (is_array($payload)) {
            $errors = collect($payload['errors'] ?? [])
                ->filter(fn ($error) => is_array($error))
                ->map(fn ($error) => $error['description'] ?? $error['message'] ?? null)
                ->filter()
                ->implode(' ');

            if ($errors !== '') {
                return 'Байкал Сервис: '.$errors;
            }

            $message = $payload['message'] ?? $payload['error'] ?? null;

            if (is_string($message) && $message !== '') {
                return 'Байкал Сервис: '.$message;
            }
        }

        $fallback = trim($fallback);

        return $fallback !== '' ? 'Байкал Сервис: '.$fallback : 'Байкал Сервис вернул ошибку.';
    }
}
