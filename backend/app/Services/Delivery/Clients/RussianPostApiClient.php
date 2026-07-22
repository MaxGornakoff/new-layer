<?php

namespace App\Services\Delivery\Clients;

use App\Models\DeliverySetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RussianPostApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $objectType,
    ) {}

    public static function fromSettings(DeliverySetting $settings): self
    {
        return new self(
            baseUrl: rtrim($settings->resolveRussianPostApiBaseUrl(), '/'),
            objectType: $settings->resolveRussianPostObjectType(),
        );
    }

    /**
     * @return array{
     *     price: float,
     *     delivery_days_min: int|null,
     *     delivery_days_max: int|null,
     *     name: string|null,
     *     raw: array<string, mixed>
     * }
     */
    public function calculateTariff(
        string $fromPostalCode,
        string $toPostalCode,
        int $weightGrams,
    ): array {
        $from = $this->normalizePostalCode($fromPostalCode);
        $to = $this->normalizePostalCode($toPostalCode);
        $weight = max(1, $weightGrams);

        $response = Http::acceptJson()
            ->timeout(20)
            ->get("{$this->baseUrl}/v2/calculate/tariff/delivery", [
                'json' => 'json',
                'object' => $this->objectType,
                'from' => $from,
                'to' => $to,
                'weight' => $weight,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                trim((string) ($response->json('message') ?? $response->body()))
                    ?: 'Почта России вернула ошибку расчёта.'
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        if (! empty($data['errors']) && is_array($data['errors'])) {
            $messages = collect($data['errors'])
                ->map(fn ($error) => is_array($error) ? ($error['msg'] ?? null) : null)
                ->filter()
                ->values();

            if ($messages->isNotEmpty() && ! isset($data['paynds']) && ! isset($data['pay'])) {
                throw new RuntimeException($messages->first());
            }
        }

        $payNds = $data['paynds'] ?? $data['paymoneynds'] ?? null;
        $pay = $data['pay'] ?? $data['paymoney'] ?? null;

        if (! is_numeric($payNds) && ! is_numeric($pay)) {
            throw new RuntimeException('Почта России не вернула стоимость доставки для указанного маршрута.');
        }

        $priceKopecks = is_numeric($payNds) ? (float) $payNds : (float) $pay;

        $delivery = is_array($data['delivery'] ?? null) ? $data['delivery'] : [];

        return [
            'price' => round($priceKopecks / 100, 2),
            'delivery_days_min' => isset($delivery['min']) ? (int) $delivery['min'] : null,
            'delivery_days_max' => isset($delivery['max']) ? (int) $delivery['max'] : null,
            'name' => isset($data['name']) ? (string) $data['name'] : null,
            'raw' => $data,
        ];
    }

    public function testConnection(string $fromPostalCode, string $toPostalCode = '190000'): array
    {
        $result = $this->calculateTariff($fromPostalCode, $toPostalCode, 1000);

        return [
            'object_type' => $this->objectType,
            'from' => $this->normalizePostalCode($fromPostalCode),
            'to' => $this->normalizePostalCode($toPostalCode),
            'sample_price' => $result['price'],
            'delivery_days_min' => $result['delivery_days_min'],
            'delivery_days_max' => $result['delivery_days_max'],
            'tariff_name' => $result['name'],
        ];
    }

    private function normalizePostalCode(string $postalCode): string
    {
        $digits = preg_replace('/\D+/', '', $postalCode) ?? '';

        if (strlen($digits) !== 6) {
            throw new RuntimeException('Почтовый индекс должен содержать 6 цифр.');
        }

        return $digits;
    }
}
