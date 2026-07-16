<?php

namespace App\Services\Delivery\Providers;

use App\Services\Delivery\Clients\BaikalApiClient;
use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use Throwable;

class BaikalDeliveryProvider extends AbstractDeliveryProvider
{
    public function key(): string
    {
        return 'baikal';
    }

    public function name(): string
    {
        return config('delivery.providers.baikal.name', 'Байкал Сервис');
    }

    public function isConfigured(): bool
    {
        return $this->settings->isProviderConfigured('baikal');
    }

    public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $this->isConfigured()) {
            return $this->pendingQuote('Укажите API-ключ Байкал Сервис в настройках доставки.');
        }

        $sender = $this->resolveSender();

        if (! $sender) {
            return $this->missingSenderQuote();
        }

        try {
            $client = BaikalApiClient::fromSettings($this->settings);
            $result = $client->calculate($request, $sender);
            $transitDays = $result['transit_days'] ?? null;

            return new DeliveryQuote(
                provider: $this->key(),
                name: $this->name(),
                price: (float) $result['total'],
                deliveryDaysMin: $transitDays,
                deliveryDaysMax: $transitDays,
                message: trim(($result['departure'] ?? '').' → '.($result['destination'] ?? '')),
            );
        } catch (Throwable $exception) {
            return $this->pendingQuote($exception->getMessage());
        }
    }
}
