<?php

namespace App\Services\Delivery\DTO;

readonly class DeliveryQuoteRequest
{
    public function __construct(
        public string $destinationCity,
        public ?string $destinationPostalCode,
        public int $totalQuantity,
        public int $packagesCount,
        public CargoPackage $package,
        public string $senderCity,
        public ?string $senderPostalCode = null,
        public ?string $senderAddress = null,
        public ?string $destinationCityGuid = null,
        public ?string $destinationCityId = null,
        public ?string $destinationTerminalId = null,
    ) {}
}
