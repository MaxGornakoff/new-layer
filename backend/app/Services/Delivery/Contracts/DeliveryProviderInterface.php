<?php

namespace App\Services\Delivery\Contracts;

use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;

interface DeliveryProviderInterface
{
    public function key(): string;

    public function name(): string;

    public function isEnabled(): bool;

    public function isConfigured(): bool;

    public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote;
}
