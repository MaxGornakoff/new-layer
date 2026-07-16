<?php

namespace App\Services\Delivery\DTO;

readonly class DeliveryQuote
{
    public function __construct(
        public string $provider,
        public string $name,
        public ?float $price = null,
        public string $currency = 'RUB',
        public ?int $deliveryDaysMin = null,
        public ?int $deliveryDaysMax = null,
        public bool $available = true,
        public ?string $message = null,
    ) {}

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $this->currency,
            'delivery_days_min' => $this->deliveryDaysMin,
            'delivery_days_max' => $this->deliveryDaysMax,
            'available' => $this->available,
            'message' => $this->message,
        ];
    }
}
