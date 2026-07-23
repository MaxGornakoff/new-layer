<?php

namespace App\Services\Delivery\Providers;

use App\Services\Delivery\Clients\YandexDeliveryApiClient;
use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use Throwable;

class YandexDeliveryProvider extends AbstractDeliveryProvider
{
    public function key(): string
    {
        return 'yandex_delivery';
    }

    public function name(): string
    {
        return config('delivery.providers.yandex_delivery.name', 'Яндекс Доставка');
    }

    public function isConfigured(): bool
    {
        return $this->settings->isProviderConfigured('yandex_delivery');
    }

    public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $this->isConfigured()) {
            return $this->pendingQuote('Укажите OAuth-токен Яндекс Доставки в настройках доставки.');
        }

        $sender = $this->resolveSender();

        if (! $sender) {
            return $this->missingSenderQuote();
        }

        try {
            $client = YandexDeliveryApiClient::fromSettings($this->settings);

            $result = $client->calculate(
                $request,
                $sender,
                $request->terminalIdFor($this->key()),
            );

            $deliveryDays = isset($result['delivery_days']) ? (int) $result['delivery_days'] : null;

            return new DeliveryQuote(
                provider: $this->key(),
                name: $this->name(),
                price: $client->parsePrice((string) $result['pricing_total']),
                deliveryDaysMin: $deliveryDays,
                deliveryDaysMax: $deliveryDays,
            );
        } catch (Throwable $exception) {
            return $this->pendingQuote($exception->getMessage());
        }
    }
}
