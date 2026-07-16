<?php

namespace App\Services\Delivery;

use App\Models\DeliverySetting;
use App\Services\Delivery\DTO\CargoPackage;
use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use App\Services\Delivery\Providers\BaikalDeliveryProvider;
use App\Services\Delivery\Providers\CdekDeliveryProvider;
use App\Services\Delivery\Providers\DellinDeliveryProvider;
use App\Services\Delivery\Providers\YandexDeliveryProvider;
use App\Services\Delivery\Providers\ZheldorDeliveryProvider;
use InvalidArgumentException;

class DeliveryCalculator
{
    public function __construct(
        private readonly DeliverySetting $settings,
    ) {}

    public static function make(): self
    {
        return new self(DeliverySetting::current());
    }

    /**
     * @return array{
     *     packages_count: int,
     *     package: array{width_cm: float, length_cm: float, height_cm: float, weight_kg: float},
     *     quotes: array<int, array<string, mixed>>
     * }
     */
    public function calculate(
        string $destinationCity,
        ?string $destinationPostalCode,
        int $totalQuantity,
        ?string $destinationCityGuid = null,
        ?string $destinationCityId = null,
        ?string $destinationTerminalId = null,
    ): array {
        $packSize = (int) ($this->settings->pack_units_count ?: config('delivery.pack_size', 10));

        if ($totalQuantity <= 0) {
            throw new InvalidArgumentException('Укажите количество товаров для расчёта доставки.');
        }

        if ($totalQuantity % $packSize !== 0) {
            throw new InvalidArgumentException("Количество должно быть кратно {$packSize} катушкам.");
        }

        if (! $this->settings->hasCargoDimensions()) {
            throw new InvalidArgumentException('Администратор ещё не настроил параметры грузоместа.');
        }

        if (blank($this->settings->sender_city) && ! $this->hasAnyProviderSender()) {
            throw new InvalidArgumentException('Администратор ещё не указал пункты отправления для служб доставки.');
        }

        $packagesCount = (int) ($totalQuantity / $packSize);
        $package = new CargoPackage(
            widthCm: (float) $this->settings->pack_width_cm,
            lengthCm: (float) $this->settings->pack_length_cm,
            heightCm: (float) $this->settings->pack_height_cm,
            weightKg: (float) $this->settings->pack_weight_kg,
            count: $packagesCount,
        );

        $request = new DeliveryQuoteRequest(
            destinationCity: trim($destinationCity),
            destinationPostalCode: $destinationPostalCode ? trim($destinationPostalCode) : null,
            totalQuantity: $totalQuantity,
            packagesCount: $packagesCount,
            package: $package,
            senderCity: (string) ($this->settings->sender_city ?? ''),
            senderPostalCode: $this->settings->sender_postal_code,
            senderAddress: $this->settings->sender_address,
            destinationCityGuid: $destinationCityGuid,
            destinationCityId: $destinationCityId,
            destinationTerminalId: $destinationTerminalId,
        );

        $quotes = collect($this->providers())
            ->map(fn ($provider) => $provider->calculate($request))
            ->filter()
            ->map(fn (DeliveryQuote $quote) => $quote->toArray())
            ->values()
            ->all();

        return [
            'packages_count' => $packagesCount,
            'pack_units_count' => $packSize,
            'package' => [
                'width_cm' => $package->widthCm,
                'length_cm' => $package->lengthCm,
                'height_cm' => $package->heightCm,
                'weight_kg' => $package->weightKg,
            ],
            'quotes' => $quotes,
        ];
    }

    private function hasAnyProviderSender(): bool
    {
        foreach (array_keys(config('delivery.providers', [])) as $provider) {
            if ($this->settings->isProviderEnabled($provider) && $this->settings->hasProviderSender($provider)) {
                return true;
            }
        }

        return false;
    }

    private function providers(): array
    {
        return [
            new BaikalDeliveryProvider($this->settings),
            new DellinDeliveryProvider($this->settings),
            new YandexDeliveryProvider($this->settings),
            new ZheldorDeliveryProvider($this->settings),
            new CdekDeliveryProvider($this->settings),
        ];
    }
}
