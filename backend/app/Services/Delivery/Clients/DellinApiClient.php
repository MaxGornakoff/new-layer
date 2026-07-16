<?php

namespace App\Services\Delivery\Clients;

use App\Models\DeliverySetting;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use App\Services\Delivery\DTO\ProviderSenderPoint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DellinApiClient
{
    private const API_ROOT = 'https://api.dellin.ru';

    public function __construct(
        private readonly string $appKey,
        private readonly string $baseUrl,
    ) {}

    public static function fromSettings(DeliverySetting $settings): self
    {
        return new self(
            appKey: (string) $settings->resolveDellinAppKey(),
            baseUrl: $settings->resolveDellinApiBaseUrl(),
        );
    }

    public function calculate(
        DeliveryQuoteRequest $request,
        ProviderSenderPoint $sender,
        ?string $destinationTerminalId = null,
    ): array {
        $lengthM = $request->package->lengthCm / 100;
        $widthM = $request->package->widthCm / 100;
        $heightM = $request->package->heightCm / 100;
        $packageVolume = $lengthM * $widthM * $heightM;
        $totalWeight = $request->package->weightKg * $request->packagesCount;
        $totalVolume = $packageVolume * $request->packagesCount;

        $derival = filled($sender->terminalId)
            ? ['variant' => 'terminal', 'terminalID' => $sender->terminalId]
            : [
                'variant' => 'address',
                'address' => [
                    'search' => $this->formatSearch($sender->city, $sender->postalCode),
                ],
            ];

        $arrival = filled($destinationTerminalId)
            ? ['variant' => 'terminal', 'terminalID' => $destinationTerminalId]
            : [
                'variant' => 'address',
                'address' => [
                    'search' => $this->formatSearch($request->destinationCity, $request->destinationPostalCode),
                ],
            ];

        $response = $this->http()
            ->post(rtrim($this->baseUrl, '/').'/calculator.json', [
                'appkey' => $this->appKey,
                'delivery' => [
                    'deliveryType' => ['type' => 'auto'],
                    'derival' => $derival,
                    'arrival' => $arrival,
                ],
                'cargo' => [
                    'quantity' => $request->packagesCount,
                    'length' => round($lengthM, 3),
                    'width' => round($widthM, 3),
                    'height' => round($heightM, 3),
                    'weight' => round($request->package->weightKg, 3),
                    'totalWeight' => round($totalWeight, 3),
                    'totalVolume' => round($totalVolume, 4),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractError($response->json(), $response->body()));
        }

        $data = $response->json('data');

        if (! is_array($data) || ! isset($data['price'])) {
            throw new RuntimeException($this->extractError($response->json(), 'Деловые линии не вернули стоимость.'));
        }

        return $data;
    }

    public function searchCities(string $query): array
    {
        if (mb_strlen(trim($query)) < 2) {
            return [];
        }

        $response = $this->http()
            ->post(self::API_ROOT.'/v2/public/kladr.json', [
                'appkey' => $this->appKey,
                'q' => trim($query),
                'limit' => 12,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractError($response->json(), 'не удалось найти города'));
        }

        return $this->normalizeCities($response->json())
            ->take(12)
            ->map(function (array $city) {
                $name = (string) ($city['searchString'] ?? $city['name'] ?? $city['cityName'] ?? '');
                $label = (string) ($city['aString'] ?? $city['searchString'] ?? $name);

                return [
                    'id' => (string) ($city['cityID'] ?? $city['cityId'] ?? $city['code'] ?? ''),
                    'name' => $name,
                    'label' => $label,
                    'source' => 'dellin',
                ];
            })
            ->filter(fn (array $city) => $city['id'] !== '' && $city['name'] !== '')
            ->values()
            ->all();
    }

    public function resolveCityId(string $cityName): ?string
    {
        $cities = $this->searchCities($cityName);
        $normalized = mb_strtolower(trim($cityName));

        $match = collect($cities)->first(
            fn (array $city) => mb_strtolower($city['name']) === $normalized
        ) ?? ($cities[0] ?? null);

        return $match['id'] ?? null;
    }

    public function getPickupPoints(int|string $cityId, ?string $query = null): array
    {
        $response = $this->http()
            ->post(self::API_ROOT.'/v1/public/request_terminals.json', [
                'appkey' => $this->appKey,
                'cityID' => (int) $cityId,
                'direction' => 'arrival',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractError($response->json(), 'не удалось загрузить пункты выдачи'));
        }

        $terminals = collect($response->json('terminals', $response->json('data.terminals', [])))
            ->filter(fn ($terminal) => is_array($terminal));

        return $terminals
            ->map(function (array $terminal) {
                $name = trim((string) ($terminal['name'] ?? $terminal['title'] ?? 'Терминал'));
                $address = trim((string) ($terminal['address'] ?? $terminal['fullAddress'] ?? ''));

                return [
                    'id' => (string) ($terminal['id'] ?? $terminal['terminalID'] ?? $terminal['terminalId'] ?? ''),
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

    private function normalizeCities(mixed $payload): Collection
    {
        if (! is_array($payload)) {
            return collect();
        }

        $cities = $payload['cities'] ?? $payload['city'] ?? $payload;

        if (! is_array($cities)) {
            return collect();
        }

        if (isset($cities['cityID']) || isset($cities['cityId'])) {
            return collect([$cities]);
        }

        return collect($cities)->filter(fn ($city) => is_array($city))->values();
    }

    private function formatSearch(string $city, ?string $postalCode): string
    {
        return trim($postalCode ? "{$postalCode}, {$city}" : $city);
    }

    private function http()
    {
        return Http::acceptJson()
            ->timeout(20);
    }

    private function extractError(mixed $payload, string $fallback): string
    {
        if (is_array($payload)) {
            $message = $payload['errors'][0]['detail']
                ?? $payload['errors'][0]['message']
                ?? $payload['error']
                ?? $payload['message']
                ?? null;

            if (is_string($message) && $message !== '') {
                return 'Деловые линии: '.$message;
            }
        }

        $fallback = trim($fallback);

        return $fallback !== '' ? 'Деловые линии: '.$fallback : 'Деловые линии вернули ошибку расчёта.';
    }
}
