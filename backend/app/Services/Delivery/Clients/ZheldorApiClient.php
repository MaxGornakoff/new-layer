<?php

namespace App\Services\Delivery\Clients;

use App\Models\DeliverySetting;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use App\Services\Delivery\DTO\ProviderSenderPoint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZheldorApiClient
{
    private const CACHE_TTL_SECONDS = 86_400;

    public function __construct(
        private readonly string $login,
        private readonly string $password,
        private readonly string $apiRoot,
    ) {}

    public static function fromSettings(DeliverySetting $settings): self
    {
        return new self(
            login: (string) $settings->resolveZheldorLogin(),
            password: (string) $settings->resolveZheldorPassword(),
            apiRoot: rtrim($settings->resolveZheldorApiBaseUrl(), '/').'/'.config('delivery.providers.zheldor.api_version', 'vD'),
        );
    }

    public function testConnection(string $departureCity, string $destinationCity): array
    {
        $departure = collect($this->searchCities($departureCity))->first();
        $destination = collect($this->searchCities($destinationCity))->first();

        if (! $departure) {
            throw new RuntimeException("Город отправления «{$departureCity}» не найден в справочнике Желдорэкспедиции.");
        }

        if (! $destination) {
            throw new RuntimeException("Город назначения «{$destinationCity}» не найден в справочнике Желдорэкспедиции.");
        }

        $fromTerminal = $this->resolveSenderTerminalId($departure['name']);
        $toTerminal = $this->resolveDestinationTerminalId($destination['kladr_code']);

        $calculation = $this->calculateByTerminals(
            fromTerminalId: $fromTerminal,
            toTerminalId: $toTerminal,
            weightKg: 1.0,
            volumeM3: 0.001,
            packagesCount: 1,
        );

        return [
            'departure_city' => $departure,
            'destination_city' => $destination,
            'from_terminal_id' => $fromTerminal,
            'to_terminal_id' => $toTerminal,
            'sample_price' => $calculation['price'] ?? null,
            'sample_days' => [
                'min' => $calculation['mindays'] ?? null,
                'max' => $calculation['maxdays'] ?? null,
            ],
        ];
    }

    public function searchCities(string $query): array
    {
        if (mb_strlen(trim($query)) < 2) {
            return [];
        }

        $needle = mb_strtolower(trim($query));

        return collect($this->getCities())
            ->filter(function (array $city) use ($needle) {
                return mb_stripos($city['title'], $needle) !== false;
            })
            ->take(12)
            ->map(fn (array $city) => [
                'id' => (string) $city['kladr_code'],
                'name' => (string) $city['title'],
                'label' => (string) $city['title'],
                'source' => 'zheldor',
                'kladr_code' => (string) $city['kladr_code'],
            ])
            ->values()
            ->all();
    }

    public function resolveCityKladr(string $cityName): ?string
    {
        $cities = $this->searchCities($cityName);
        $normalized = mb_strtolower(trim($cityName));

        $match = collect($cities)->first(
            fn (array $city) => mb_strtolower($city['name']) === $normalized
        ) ?? ($cities[0] ?? null);

        return $match['kladr_code'] ?? null;
    }

    public function resolveCityKladrFromIdOrName(?string $cityId, ?string $cityName): ?string
    {
        if (filled($cityId) && $this->isKladrCode($cityId)) {
            return $cityId;
        }

        if (filled($cityName)) {
            return $this->resolveCityKladr($cityName);
        }

        return null;
    }

    public function getPickupPointsForCity(?string $cityId, ?string $cityName, ?string $query = null): array
    {
        $cityKladr = $this->resolveCityKladrFromIdOrName($cityId, $cityName);

        if (! filled($cityKladr)) {
            return [];
        }

        return $this->getPickupPoints($cityKladr, $query);
    }

    public function getPickupPoints(string $cityKladr, ?string $query = null): array
    {
        $terminals = collect($this->getDeliveryTerminals())
            ->filter(fn (array $terminal) => ($terminal['kladr_code_city'] ?? '') === $cityKladr);

        return $terminals
            ->map(function (array $terminal) {
                $name = trim((string) ($terminal['title'] ?? 'Терминал'));
                $address = trim((string) ($terminal['addr'] ?? ''));

                return [
                    'id' => (string) ($terminal['code'] ?? ''),
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
        ?string $destinationTerminalId = null,
    ): array {
        $fromTerminalId = filled($sender->terminalId)
            ? $sender->terminalId
            : $this->resolveSenderTerminalId($sender->city);

        $toTerminalId = filled($destinationTerminalId)
            ? $destinationTerminalId
            : $this->resolveDestinationTerminalId(
                $this->resolveCityKladrFromIdOrName(
                    $request->destinationCityId,
                    $request->destinationCity,
                )
            );

        if (! filled($fromTerminalId)) {
            throw new RuntimeException('Не удалось определить терминал отправления Желдорэкспедиции.');
        }

        if (! filled($toTerminalId)) {
            throw new RuntimeException('Не удалось определить терминал назначения Желдорэкспедиции.');
        }

        $lengthM = $request->package->lengthCm / 100;
        $widthM = $request->package->widthCm / 100;
        $heightM = $request->package->heightCm / 100;
        $packageVolume = $lengthM * $widthM * $heightM;
        $totalWeight = $request->package->weightKg * $request->packagesCount;
        $totalVolume = $packageVolume * $request->packagesCount;

        return $this->calculateByTerminals(
            fromTerminalId: $fromTerminalId,
            toTerminalId: $toTerminalId,
            weightKg: $totalWeight,
            volumeM3: $totalVolume,
            packagesCount: $request->packagesCount,
        );
    }

    private function calculateByTerminals(
        string $fromTerminalId,
        string $toTerminalId,
        float $weightKg,
        float $volumeM3,
        int $packagesCount,
    ): array {
        $params = [
            'from' => $fromTerminalId,
            'to' => $toTerminalId,
            'weight' => round(max($weightKg, 0.001), 3),
            'volume' => round(max($volumeM3, 0.0001), 4),
            'type' => 1,
            'quantity' => max($packagesCount, 1),
        ];

        $response = $this->http(private: true)->get("{$this->apiRoot}/calculator/price", [
            ...$params,
            'user' => $this->login,
            'token' => $this->password,
        ]);

        if (! $response->successful()) {
            $response = $this->http(private: true)->get("{$this->apiRoot}/calculator/price", $params);
        }

        if (! $response->successful()) {
            throw new RuntimeException($this->extractError($response->json(), $response->body()));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Желдорэкспедиция вернула некорректный ответ калькулятора.');
        }

        if (isset($data['error']) && is_string($data['error']) && str_contains($data['error'], 'Access violation')) {
            $response = $this->http(private: true)->get("{$this->apiRoot}/calculator/price", $params);
            $data = $response->json();
        }

        if (($data['result'] ?? '0') !== '1' && ($data['result'] ?? false) !== true) {
            throw new RuntimeException($this->extractError($data, 'Желдорэкспедиция не рассчитала стоимость.'));
        }

        if (! isset($data['price'])) {
            throw new RuntimeException($this->extractError($data, 'Желдорэкспедиция не вернула стоимость.'));
        }

        return $data;
    }

    private function resolveSenderTerminalId(string $cityName): ?string
    {
        $normalizedCity = mb_strtolower(trim($cityName));

        $terminal = collect($this->getPickupTerminals())
            ->first(fn (array $terminal) => mb_strtolower((string) ($terminal['city'] ?? '')) === $normalizedCity);

        return isset($terminal['code']) ? (string) $terminal['code'] : null;
    }

    private function resolveDestinationTerminalId(?string $cityKladr): ?string
    {
        if (! filled($cityKladr)) {
            return null;
        }

        $terminal = collect($this->getDeliveryTerminals())
            ->first(fn (array $terminal) => ($terminal['kladr_code_city'] ?? '') === $cityKladr);

        return isset($terminal['code']) ? (string) $terminal['code'] : null;
    }

    private function isKladrCode(string $value): bool
    {
        return (bool) preg_match('/^\d{11,13}$/', $value);
    }

    private function getCities(): array
    {
        return Cache::remember('zheldor.cities', self::CACHE_TTL_SECONDS, function () {
            $response = $this->http()->get("{$this->apiRoot}/geo/SearchCity", ['mode' => 2]);

            if (! $response->successful()) {
                throw new RuntimeException($this->extractError($response->json(), 'не удалось загрузить справочник городов'));
            }

            $cities = $response->json();

            return is_array($cities) ? $cities : [];
        });
    }

    private function getPickupTerminals(): array
    {
        return Cache::remember('zheldor.terminals.pickup', self::CACHE_TTL_SECONDS, function () {
            return $this->fetchTerminals(1);
        });
    }

    private function getDeliveryTerminals(): array
    {
        return Cache::remember('zheldor.terminals.delivery', self::CACHE_TTL_SECONDS, function () {
            return $this->fetchTerminals(2);
        });
    }

    private function fetchTerminals(int $mode): array
    {
        $response = $this->http()->get("{$this->apiRoot}/geo/search", ['mode' => $mode]);

        if (! $response->successful()) {
            throw new RuntimeException($this->extractError($response->json(), 'не удалось загрузить терминалы'));
        }

        $terminals = $response->json();

        return is_array($terminals) ? $terminals : [];
    }

    private function http(bool $private = false)
    {
        return Http::acceptJson()->timeout($private ? 30 : 60);
    }

    private function extractError(mixed $payload, string $fallback): string
    {
        if (is_array($payload)) {
            $message = $payload['error']
                ?? $payload['message']
                ?? null;

            if (is_string($message) && $message !== '') {
                return 'Желдорэкспедиция: '.$message;
            }
        }

        $fallback = trim($fallback);

        if ($fallback !== '' && ! str_starts_with(mb_strtolower($fallback), 'желдор')) {
            return 'Желдорэкспедиция: '.$fallback;
        }

        return $fallback !== '' ? $fallback : 'Желдорэкспедиция вернула ошибку.';
    }
}
