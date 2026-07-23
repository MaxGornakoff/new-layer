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
        public ?string $destinationTerminalProvider = null,
    ) {}

    /**
     * Terminal IDs are provider-specific. Only the owning provider may use them.
     */
    public function terminalIdFor(string $provider): ?string
    {
        if (
            blank($this->destinationTerminalId)
            || blank($this->destinationTerminalProvider)
            || $this->destinationTerminalProvider !== $provider
        ) {
            return null;
        }

        return $this->destinationTerminalId;
    }
}
