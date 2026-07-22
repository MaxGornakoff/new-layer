<?php

namespace App\Services\Delivery\Providers;

use App\Services\Delivery\Clients\RussianPostApiClient;
use App\Services\Delivery\DTO\DeliveryQuote;
use App\Services\Delivery\DTO\DeliveryQuoteRequest;
use App\Services\Delivery\RussianPostCityIndexResolver;
use Throwable;

class RussianPostDeliveryProvider extends AbstractDeliveryProvider
{
    public function key(): string
    {
        return 'russian_post';
    }

    public function name(): string
    {
        return config('delivery.providers.russian_post.name', 'Почта России');
    }

    public function isConfigured(): bool
    {
        return $this->settings->isProviderConfigured('russian_post');
    }

    public function calculate(DeliveryQuoteRequest $request): ?DeliveryQuote
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $this->isConfigured()) {
            return $this->pendingQuote(
                'Укажите индекс пункта отправления для Почты России в настройках доставки.'
            );
        }

        $sender = $this->resolveSender();

        if (! $sender) {
            return $this->missingSenderQuote();
        }

        if (blank($sender->postalCode)) {
            return $this->pendingQuote(
                'Для Почты России нужен индекс пункта отправления (6 цифр).'
            );
        }

        $destinationPostal = filled($request->destinationPostalCode)
            ? trim((string) $request->destinationPostalCode)
            : (new RussianPostCityIndexResolver())->resolve($request->destinationCity);

        if (blank($destinationPostal)) {
            return new DeliveryQuote(
                provider: $this->key(),
                name: $this->name(),
                available: false,
                message: 'Укажите почтовый индекс получателя — подставим автоматически, если город знакомый, или введите вручную.',
            );
        }

        try {
            $client = RussianPostApiClient::fromSettings($this->settings);
            $packageWeightGrams = max(1, (int) round($request->package->weightKg * 1000));
            $packagesCount = max(1, $request->packagesCount);

            $result = $client->calculateTariff(
                $sender->postalCode,
                $destinationPostal,
                $packageWeightGrams,
            );

            $price = round($result['price'] * $packagesCount, 2);
            $message = $result['name'] ?? 'Посылка';

            if ($packagesCount > 1) {
                $message .= " × {$packagesCount}";
            }

            $message .= " · индекс {$destinationPostal}";

            return new DeliveryQuote(
                provider: $this->key(),
                name: $this->name(),
                price: $price,
                deliveryDaysMin: $result['delivery_days_min'],
                deliveryDaysMax: $result['delivery_days_max'],
                message: $message,
                postalCode: $destinationPostal,
            );
        } catch (Throwable $exception) {
            return $this->pendingQuote('Почта России: '.$exception->getMessage());
        }
    }
}
