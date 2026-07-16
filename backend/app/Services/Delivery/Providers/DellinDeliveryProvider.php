<?php

namespace App\Services\Delivery\Providers;

use App\Models\DeliverySetting;
use App\Services\Delivery\Clients\DellinApiClient;
use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use Throwable;

class DellinDeliveryProvider extends AbstractDeliveryProvider
{
    public function key(): string
    {
        return 'dellin';
    }

    public function name(): string
    {
        return config('delivery.providers.dellin.name', 'Деловые линии');
    }

    public function isConfigured(): bool
    {
        return $this->settings->isProviderConfigured('dellin');
    }

    public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $this->isConfigured()) {
            return $this->pendingQuote('Укажите App Key Деловых линий в настройках доставки.');
        }

        $sender = $this->resolveSender();

        if (! $sender) {
            return $this->missingSenderQuote();
        }

        try {
            $client = DellinApiClient::fromSettings($this->settings);

            $result = $client->calculate(
                $request,
                $sender,
                $request->destinationTerminalId,
            );
            $deliveryDays = $this->extractDeliveryDays($result);

            return new DeliveryQuote(
                provider: $this->key(),
                name: $this->name(),
                price: (float) $result['price'],
                deliveryDaysMin: $deliveryDays,
                deliveryDaysMax: $deliveryDays,
            );
        } catch (Throwable $exception) {
            return $this->pendingQuote($exception->getMessage());
        }
    }

    private function extractDeliveryDays(array $result): ?int
    {
        if (isset($result['deliveryTerm']) && (int) $result['deliveryTerm'] > 0) {
            return (int) $result['deliveryTerm'];
        }

        $orderDates = $result['orderDates'] ?? null;

        if (! is_array($orderDates)) {
            return null;
        }

        $start = $orderDates['pickup']
            ?? $orderDates['derivalFromOspSender']
            ?? null;
        $end = $orderDates['derivalFromOspReceiver']
            ?? $orderDates['arrivalToOspReceiver']
            ?? null;

        if (! is_string($start) || ! is_string($end) || $start === '' || $end === '') {
            return null;
        }

        try {
            $startDate = new \DateTimeImmutable($start);
            $endDate = new \DateTimeImmutable($end);
            $days = (int) $startDate->diff($endDate)->days;

            return $days > 0 ? $days : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
