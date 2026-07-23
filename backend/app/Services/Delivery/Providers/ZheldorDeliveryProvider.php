<?php

namespace App\Services\Delivery\Providers;

use App\Services\Delivery\Clients\ZheldorApiClient;
use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use Throwable;

class ZheldorDeliveryProvider extends AbstractDeliveryProvider
{
    public function key(): string
    {
        return 'zheldor';
    }

    public function name(): string
    {
        return config('delivery.providers.zheldor.name', 'Желдорэкспедиция');
    }

    public function isConfigured(): bool
    {
        return $this->settings->isProviderConfigured('zheldor');
    }

    public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $this->isConfigured()) {
            return $this->pendingQuote('Укажите логин и пароль Желдорэкспедиции в настройках доставки.');
        }

        $sender = $this->resolveSender();

        if (! $sender) {
            return $this->missingSenderQuote();
        }

        try {
            $client = ZheldorApiClient::fromSettings($this->settings);
            $result = $client->calculate(
                $request,
                $sender,
                $request->terminalIdFor($this->key()),
            );

            return new DeliveryQuote(
                provider: $this->key(),
                name: $this->name(),
                price: (float) $result['price'],
                deliveryDaysMin: isset($result['mindays']) ? (int) $result['mindays'] : null,
                deliveryDaysMax: isset($result['maxdays']) ? (int) $result['maxdays'] : null,
            );
        } catch (Throwable $exception) {
            return $this->pendingQuote($exception->getMessage());
        }
    }
}
