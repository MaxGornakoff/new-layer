<?php

namespace App\Models;

use App\Services\Delivery\DTO\ProviderSenderPoint;
use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    protected $fillable = [
        'pack_units_count',
        'pack_width_cm',
        'pack_length_cm',
        'pack_height_cm',
        'pack_weight_kg',
        'sender_city',
        'sender_postal_code',
        'sender_address',
        'provider_senders',
        'baikal_enabled',
        'dellin_enabled',
        'pek_enabled',
        'yandex_delivery_enabled',
        'zheldor_enabled',
        'cdek_enabled',
        'cdek_client_id',
        'cdek_client_secret',
        'cdek_use_test_api',
        'russian_post_enabled',
        'russian_post_object_type',
        'baikal_api_key',
        'dellin_app_key',
        'pek_login',
        'pek_api_key',
        'yandex_delivery_oauth_token',
        'zheldor_login',
        'zheldor_password',
    ];

    protected $hidden = [
        'cdek_client_secret',
        'baikal_api_key',
        'dellin_app_key',
        'pek_api_key',
        'yandex_delivery_oauth_token',
        'zheldor_password',
    ];

    protected function casts(): array
    {
        return [
            'pack_units_count' => 'integer',
            'pack_width_cm' => 'decimal:2',
            'pack_length_cm' => 'decimal:2',
            'pack_height_cm' => 'decimal:2',
            'pack_weight_kg' => 'decimal:3',
            'baikal_enabled' => 'boolean',
            'dellin_enabled' => 'boolean',
            'pek_enabled' => 'boolean',
            'yandex_delivery_enabled' => 'boolean',
            'zheldor_enabled' => 'boolean',
            'cdek_enabled' => 'boolean',
            'cdek_use_test_api' => 'boolean',
            'russian_post_enabled' => 'boolean',
            'russian_post_object_type' => 'integer',
            'cdek_client_secret' => 'encrypted',
            'baikal_api_key' => 'encrypted',
            'dellin_app_key' => 'encrypted',
            'pek_api_key' => 'encrypted',
            'yandex_delivery_oauth_token' => 'encrypted',
            'zheldor_password' => 'encrypted',
            'provider_senders' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function hasCargoDimensions(): bool
    {
        return $this->pack_width_cm > 0
            && $this->pack_length_cm > 0
            && $this->pack_height_cm > 0
            && $this->pack_weight_kg > 0;
    }

    public function isProviderEnabled(string $provider): bool
    {
        return match ($provider) {
            'baikal' => (bool) $this->baikal_enabled,
            'dellin' => (bool) $this->dellin_enabled,
            'yandex_delivery' => (bool) $this->yandex_delivery_enabled,
            'zheldor' => (bool) $this->zheldor_enabled,
            'cdek' => (bool) $this->cdek_enabled,
            'russian_post' => (bool) $this->russian_post_enabled,
            'pek' => (bool) $this->pek_enabled,
            default => false,
        };
    }

    public function isProviderConfigured(string $provider): bool
    {
        return match ($provider) {
            'baikal' => filled($this->resolveBaikalApiKey()),
            'dellin' => filled($this->resolveDellinAppKey()),
            'yandex_delivery' => filled($this->resolveYandexDeliveryToken()),
            'zheldor' => filled($this->resolveZheldorLogin()) && filled($this->resolveZheldorPassword()),
            'cdek' => filled($this->resolveCdekClientId()) && filled($this->resolveCdekClientSecret()),
            'russian_post' => filled($this->resolveProviderSender('russian_post')?->postalCode),
            'pek' => filled($this->resolvePekLogin()) && filled($this->resolvePekApiKey()),
            default => false,
        };
    }

    public function resolveCdekClientId(): ?string
    {
        return $this->cdek_client_id ?: config('delivery.providers.cdek.client_id');
    }

    public function resolveCdekClientSecret(): ?string
    {
        return $this->cdek_client_secret ?: config('delivery.providers.cdek.client_secret');
    }

    public function resolveCdekApiBaseUrl(): string
    {
        if ($this->cdek_use_test_api) {
            return 'https://api.edu.cdek.ru/v2';
        }

        return config('delivery.providers.cdek.api_base_url', 'https://api.cdek.ru/v2');
    }

    public function resolveRussianPostApiBaseUrl(): string
    {
        return config('delivery.providers.russian_post.api_base_url', 'https://tariff.pochta.ru');
    }

    public function resolveRussianPostObjectType(): int
    {
        $configured = $this->russian_post_object_type;

        if (is_numeric($configured) && (int) $configured > 0) {
            return (int) $configured;
        }

        return (int) config('delivery.providers.russian_post.object_type', 4030);
    }

    public function resolveBaikalApiKey(): ?string
    {
        return $this->baikal_api_key ?: config('delivery.providers.baikal.api_key');
    }

    public function resolveBaikalApiBaseUrl(): string
    {
        if (config('delivery.providers.baikal.use_test_api')) {
            return 'https://test-api.baikalsr.ru';
        }

        return config('delivery.providers.baikal.api_base_url', 'https://api.baikalsr.ru');
    }

    public function resolveDellinAppKey(): ?string
    {
        return $this->dellin_app_key ?: config('delivery.providers.dellin.app_key');
    }

    public function resolveDellinApiBaseUrl(): string
    {
        return config('delivery.providers.dellin.api_base_url', 'https://api.dellin.ru/v2');
    }

    public function resolveZheldorLogin(): ?string
    {
        return $this->zheldor_login ?: config('delivery.providers.zheldor.login');
    }

    public function resolveZheldorPassword(): ?string
    {
        return $this->zheldor_password ?: config('delivery.providers.zheldor.password');
    }

    public function resolveZheldorApiBaseUrl(): string
    {
        return config('delivery.providers.zheldor.api_base_url', 'https://api.jde.ru');
    }

    public function resolveYandexDeliveryApiBaseUrl(): string
    {
        if (config('delivery.providers.yandex_delivery.use_test_api')) {
            return 'https://b2b.taxi.tst.yandex.net';
        }

        return config('delivery.providers.yandex_delivery.api_base_url', 'https://b2b-authproxy.taxi.yandex.net');
    }

    public function resolvePekLogin(): ?string
    {
        return $this->pek_login ?: config('delivery.providers.pek.login');
    }

    public function resolvePekApiKey(): ?string
    {
        return $this->pek_api_key ?: config('delivery.providers.pek.api_key');
    }

    public function resolveYandexDeliveryToken(): ?string
    {
        return $this->yandex_delivery_oauth_token ?: config('delivery.providers.yandex_delivery.oauth_token');
    }

    public function resolveProviderSender(string $provider): ?ProviderSenderPoint
    {
        $providerData = $this->provider_senders[$provider] ?? null;

        if (is_array($providerData) && filled($providerData['city'] ?? null)) {
            return new ProviderSenderPoint(
                city: trim((string) $providerData['city']),
                postalCode: filled($providerData['postal_code'] ?? null) ? trim((string) $providerData['postal_code']) : null,
                address: filled($providerData['address'] ?? null) ? trim((string) $providerData['address']) : null,
                terminalId: filled($providerData['terminal_id'] ?? null) ? trim((string) $providerData['terminal_id']) : null,
            );
        }

        if (! filled($this->sender_city)) {
            return null;
        }

        return new ProviderSenderPoint(
            city: trim((string) $this->sender_city),
            postalCode: filled($this->sender_postal_code) ? trim((string) $this->sender_postal_code) : null,
            address: filled($this->sender_address) ? trim((string) $this->sender_address) : null,
        );
    }

    public function hasProviderSender(string $provider): bool
    {
        return $this->resolveProviderSender($provider) !== null;
    }

    public static function defaultProviderSenders(): array
    {
        return collect(array_keys(config('delivery.providers', [])))
            ->mapWithKeys(fn (string $key) => [
                $key => [
                    'city' => '',
                    'postal_code' => '',
                    'address' => '',
                    'terminal_id' => '',
                ],
            ])
            ->all();
    }

    public static function maskSecret(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $length = mb_strlen($value);

        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        return str_repeat('•', max(8, $length - 4)).mb_substr($value, -4);
    }
}
