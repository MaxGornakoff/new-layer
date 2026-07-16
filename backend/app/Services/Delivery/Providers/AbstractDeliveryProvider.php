<?php

namespace App\Services\Delivery\Providers;

use App\Models\DeliverySetting;
use App\Services\Delivery\Contracts\DeliveryProviderInterface;
use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use App\Services\Delivery\DTO\ProviderSenderPoint;

abstract class AbstractDeliveryProvider implements DeliveryProviderInterface
{
    public function __construct(
        protected DeliverySetting $settings,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->isProviderEnabled($this->key());
    }

    protected function pendingQuote(string $message): DeliveryQuote
    {
        return new DeliveryQuote(
            provider: $this->key(),
            name: $this->name(),
            available: false,
            message: $message,
        );
    }

    protected function resolveSender(): ?ProviderSenderPoint
    {
        return $this->settings->resolveProviderSender($this->key());
    }

    protected function missingSenderQuote(): DeliveryQuote
    {
        return $this->pendingQuote(
            'Укажите пункт отправления для '.$this->name().' в настройках доставки.'
        );
    }

    abstract public function key(): string;

    abstract public function name(): string;

    abstract public function isConfigured(): bool;

    abstract public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote;
}
