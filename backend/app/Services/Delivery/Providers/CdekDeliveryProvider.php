<?php

namespace App\Services\Delivery\Providers;

use App\Services\Delivery\Clients\CdekApiClient;
use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use App\Services\Delivery\DTO\ProviderSenderPoint;
use Throwable;

class CdekDeliveryProvider extends AbstractDeliveryProvider
{
    public function key(): string
    {
        return 'cdek';
    }

    public function name(): string
    {
        return config('delivery.providers.cdek.name', 'СДЭК');
    }

    public function isConfigured(): bool
    {
        return $this->settings->isProviderConfigured('cdek');
    }

    public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $this->isConfigured()) {
            return $this->pendingQuote('Укажите Client ID и Client Secret СДЭК в настройках доставки.');
        }

        $sender = $this->resolveSender();

        if (! $sender) {
            return $this->missingSenderQuote();
        }

        try {
            $client = CdekApiClient::fromSettings($this->settings);
            $tariff = $client->calculateCheapestTariff(
                $this->buildLocation($sender),
                $this->buildLocation($request->destinationPostalCode, $request->destinationCity),
                $this->buildPackages($request),
            );

            return new DeliveryQuote(
                provider: $this->key(),
                name: $this->name(),
                price: (float) $tariff['delivery_sum'],
                deliveryDaysMin: isset($tariff['period_min']) ? (int) $tariff['period_min'] : null,
                deliveryDaysMax: isset($tariff['period_max']) ? (int) $tariff['period_max'] : null,
                message: $tariff['tariff_name'] ?? null,
            );
        } catch (Throwable $exception) {
            return $this->pendingQuote('СДЭК: '.$exception->getMessage());
        }
    }

    private function buildLocation(ProviderSenderPoint|string|null $postalCodeOrSender, ?string $city = null): array
    {
        if ($postalCodeOrSender instanceof ProviderSenderPoint) {
            if (filled($postalCodeOrSender->postalCode)) {
                return ['postal_code' => $postalCodeOrSender->postalCode];
            }

            return ['city' => $postalCodeOrSender->city];
        }

        if (filled($postalCodeOrSender)) {
            return ['postal_code' => trim((string) $postalCodeOrSender)];
        }

        return ['city' => trim((string) $city)];
    }

    private function buildPackages(DeliveryQuoteRequest $request): array
    {
        $package = [
            'weight' => max(1, (int) round($request->package->weightKg * 1000)),
            'length' => max(1, (int) ceil($request->package->lengthCm)),
            'width' => max(1, (int) ceil($request->package->widthCm)),
            'height' => max(1, (int) ceil($request->package->heightCm)),
        ];

        return array_fill(0, max(1, $request->packagesCount), $package);
    }
}
