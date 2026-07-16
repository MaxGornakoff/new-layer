<?php

namespace App\Services\Delivery;

use App\Models\DeliverySetting;
use App\Services\Delivery\Clients\BaikalApiClient;
use App\Services\Delivery\Clients\DellinApiClient;
use App\Services\Delivery\Clients\YandexDeliveryApiClient;
use App\Services\Delivery\Clients\ZheldorApiClient;
use InvalidArgumentException;
use RuntimeException;

class DeliveryLocationService
{
    public function __construct(
        private readonly DeliverySetting $settings,
    ) {}

    public static function make(): self
    {
        return new self(DeliverySetting::current());
    }

    public function listCheckoutProviders(): array
    {
        $pickupProviders = ['baikal', 'dellin', 'yandex_delivery', 'zheldor'];

        return collect(config('delivery.providers', []))
            ->map(function (array $config, string $key) use ($pickupProviders) {
                if (! $this->settings->isProviderEnabled($key)) {
                    return null;
                }

                return [
                    'key' => $key,
                    'name' => $config['name'] ?? $key,
                    'configured' => $this->settings->isProviderConfigured($key),
                    'has_pickup_points' => in_array($key, $pickupProviders, true),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function searchCitiesUnified(string $query): array
    {
        if ($this->settings->isProviderEnabled('dellin') && $this->settings->isProviderConfigured('dellin')) {
            return DellinApiClient::fromSettings($this->settings)->searchCities($query);
        }

        if ($this->settings->isProviderEnabled('zheldor') && $this->settings->isProviderConfigured('zheldor')) {
            return ZheldorApiClient::fromSettings($this->settings)->searchCities($query);
        }

        if ($this->settings->isProviderEnabled('yandex_delivery') && $this->settings->isProviderConfigured('yandex_delivery')) {
            return YandexDeliveryApiClient::fromSettings($this->settings)->detectLocation($query);
        }

        if ($this->settings->isProviderEnabled('baikal') && $this->settings->isProviderConfigured('baikal')) {
            return collect(BaikalApiClient::fromSettings($this->settings)->searchCities($query))
                ->map(fn (array $city) => array_merge($city, ['source' => 'baikal']))
                ->all();
        }

        throw new RuntimeException('Нет настроенных служб доставки для поиска городов.');
    }

    public function searchCities(string $provider, string $query): array
    {
        $this->assertProviderEnabled($provider);

        return match ($provider) {
            'baikal' => BaikalApiClient::fromSettings($this->settings)->searchCities($query),
            'dellin' => DellinApiClient::fromSettings($this->settings)->searchCities($query),
            'zheldor' => ZheldorApiClient::fromSettings($this->settings)->searchCities($query),
            'yandex_delivery' => YandexDeliveryApiClient::fromSettings($this->settings)->detectLocation($query),
            default => throw new InvalidArgumentException('Поиск городов для этой службы пока не подключён.'),
        };
    }

    public function getPickupPoints(
        string $provider,
        ?string $cityGuid = null,
        ?string $query = null,
        ?string $cityName = null,
        ?string $cityId = null,
    ): array {
        $this->assertProviderEnabled($provider);

        return match ($provider) {
            'baikal' => $this->getBaikalPickupPoints($cityGuid, $cityName, $query),
            'dellin' => $this->getDellinPickupPoints($cityId, $cityName, $query),
            'zheldor' => $this->getZheldorPickupPoints($cityId, $cityName, $query),
            'yandex_delivery' => $this->getYandexPickupPoints($cityId, $cityName, $query),
            default => throw new InvalidArgumentException('Пункты выдачи для этой службы пока не подключены.'),
        };
    }

    private function getBaikalPickupPoints(?string $cityGuid, ?string $cityName, ?string $query): array
    {
        $client = BaikalApiClient::fromSettings($this->settings);

        if (! filled($cityGuid) && filled($cityName)) {
            $cityGuid = $client->resolveCityGuidPublic($cityName);
        }

        if (! filled($cityGuid)) {
            throw new InvalidArgumentException('Сначала выберите город.');
        }

        return $client->getPickupPoints($cityGuid, $query);
    }

    private function getDellinPickupPoints(?string $cityId, ?string $cityName, ?string $query): array
    {
        $client = DellinApiClient::fromSettings($this->settings);

        if (! filled($cityId) && filled($cityName)) {
            $cityId = $client->resolveCityId($cityName);
        }

        if (! filled($cityId)) {
            throw new InvalidArgumentException('Сначала выберите город.');
        }

        return $client->getPickupPoints($cityId, $query);
    }

    private function getZheldorPickupPoints(?string $cityId, ?string $cityName, ?string $query): array
    {
        $client = ZheldorApiClient::fromSettings($this->settings);

        if (! filled($cityId) && ! filled($cityName)) {
            throw new InvalidArgumentException('Сначала выберите город.');
        }

        $points = $client->getPickupPointsForCity($cityId, $cityName, $query);

        if ($points === [] && filled($cityName)) {
            throw new InvalidArgumentException('Для выбранного города нет пунктов выдачи Желдорэкспедиции.');
        }

        return $points;
    }

    private function getYandexPickupPoints(?string $cityId, ?string $cityName, ?string $query): array
    {
        $client = YandexDeliveryApiClient::fromSettings($this->settings);

        if (! filled($cityId) && ! filled($cityName)) {
            throw new InvalidArgumentException('Сначала выберите город.');
        }

        $points = $client->getPickupPointsForCity($cityId, $cityName, $query);

        if ($points === [] && filled($cityName)) {
            throw new InvalidArgumentException('Для выбранного города нет пунктов выдачи Яндекс Доставки.');
        }

        return $points;
    }

    private function assertProviderEnabled(string $provider): void
    {
        if (! $this->settings->isProviderEnabled($provider)) {
            throw new InvalidArgumentException('Выбранная служба доставки недоступна.');
        }

        if (! $this->settings->isProviderConfigured($provider)) {
            throw new RuntimeException('Служба доставки ещё не настроена администратором.');
        }
    }
}
