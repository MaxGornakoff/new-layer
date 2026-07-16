<?php

namespace App\Services\Delivery\Clients;

use App\Models\DeliverySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CdekApiClient
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $baseUrl,
    ) {}

    public static function fromSettings(DeliverySetting $settings): self
    {
        return new self(
            clientId: (string) $settings->resolveCdekClientId(),
            clientSecret: (string) $settings->resolveCdekClientSecret(),
            baseUrl: rtrim($settings->resolveCdekApiBaseUrl(), '/'),
        );
    }

    public function calculateCheapestTariff(array $fromLocation, array $toLocation, array $packages): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->post("{$this->baseUrl}/calculator/tarifflist", [
                'type' => 1,
                'currency' => 1,
                'lang' => 'rus',
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'packages' => $packages,
            ]);

        if (! $response->successful()) {
            $message = $response->json('errors.0.message')
                ?? $response->json('message')
                ?? $response->body();

            throw new RuntimeException(trim((string) $message) ?: 'СДЭК вернул ошибку расчёта.');
        }

        $tariffs = collect($response->json('tariff_codes', []))
            ->filter(fn (array $tariff) => isset($tariff['delivery_sum']) && is_numeric($tariff['delivery_sum']))
            ->sortBy('delivery_sum')
            ->values();

        if ($tariffs->isEmpty()) {
            throw new RuntimeException('СДЭК не вернул доступные тарифы для указанного маршрута.');
        }

        return $tariffs->first();
    }

    private function getAccessToken(): string
    {
        $cacheKey = 'cdek_token_'.sha1($this->clientId.$this->baseUrl);

        return Cache::remember($cacheKey, 3500, function () {
            $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (! $response->successful()) {
                $message = $response->json('error_description')
                    ?? $response->json('message')
                    ?? $response->body();

                throw new RuntimeException(trim((string) $message) ?: 'Не удалось авторизоваться в API СДЭК.');
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('СДЭК не вернул access token.');
            }

            return $token;
        });
    }
}
