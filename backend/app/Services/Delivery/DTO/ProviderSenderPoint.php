<?php

namespace App\Services\Delivery\DTO;

readonly class ProviderSenderPoint
{
    public function __construct(
        public string $city,
        public ?string $postalCode = null,
        public ?string $address = null,
        public ?string $terminalId = null,
    ) {}
}
