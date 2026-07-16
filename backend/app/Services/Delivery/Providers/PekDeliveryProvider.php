<?php

namespace App\Services\Delivery\Providers;

use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;

class PekDeliveryProvider extends AbstractDeliveryProvider
{
    public function key(): string
    {
        return 'pek';
    }

    public function name(): string
    {
        return config('delivery.providers.pek.name', 'ПЭК');
    }

    public function isConfigured(): bool
    {
        return $this->settings->isProviderConfigured('pek');
    }

    public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $this->isConfigured()) {
            return $this->pendingQuote('Укажите логин и API-ключ ПЭК в настройках доставки.');
        }

        return $this->pendingQuote('Запрос к API ПЭК будет подключён на следующем шаге');
    }
}
