<?php

namespace App\Http\Resources;

use App\Models\DeliverySetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DeliverySetting */
class DeliverySettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pack_units_count' => (int) ($this->pack_units_count ?: config('delivery.pack_size', 10)),
            'pack_width_cm' => $this->pack_width_cm !== null ? (float) $this->pack_width_cm : null,
            'pack_length_cm' => $this->pack_length_cm !== null ? (float) $this->pack_length_cm : null,
            'pack_height_cm' => $this->pack_height_cm !== null ? (float) $this->pack_height_cm : null,
            'pack_weight_kg' => $this->pack_weight_kg !== null ? (float) $this->pack_weight_kg : null,
            'sender_city' => $this->sender_city,
            'sender_postal_code' => $this->sender_postal_code,
            'sender_address' => $this->sender_address,
            'baikal_enabled' => (bool) $this->baikal_enabled,
            'dellin_enabled' => (bool) $this->dellin_enabled,
            'yandex_delivery_enabled' => (bool) $this->yandex_delivery_enabled,
            'zheldor_enabled' => (bool) $this->zheldor_enabled,
            'cdek_enabled' => (bool) $this->cdek_enabled,
            'cargo_configured' => $this->hasCargoDimensions(),
            'provider_senders' => $this->normalizedProviderSenders(),
            'credentials' => $this->credentialsMeta(),
            'providers' => $this->providerStatuses(),
        ];
    }

    private function normalizedProviderSenders(): array
    {
        $defaults = DeliverySetting::defaultProviderSenders();
        $saved = is_array($this->provider_senders) ? $this->provider_senders : [];

        return collect($defaults)->map(function (array $default, string $key) use ($saved) {
            $provider = $saved[$key] ?? [];

            return [
                'city' => $provider['city'] ?? '',
                'postal_code' => $provider['postal_code'] ?? '',
                'address' => $provider['address'] ?? '',
                'terminal_id' => $provider['terminal_id'] ?? '',
                'configured' => filled($provider['city'] ?? null),
            ];
        })->all();
    }

    private function credentialsMeta(): array
    {
        return [
            'baikal' => [
                'api_key_set' => filled($this->baikal_api_key),
                'api_key_hint' => DeliverySetting::maskSecret($this->baikal_api_key),
            ],
            'dellin' => [
                'app_key_set' => filled($this->dellin_app_key),
                'app_key_hint' => DeliverySetting::maskSecret($this->dellin_app_key),
            ],
            'yandex_delivery' => [
                'oauth_token_set' => filled($this->yandex_delivery_oauth_token),
                'oauth_token_hint' => DeliverySetting::maskSecret($this->yandex_delivery_oauth_token),
            ],
            'zheldor' => [
                'login' => $this->zheldor_login,
                'password_set' => filled($this->zheldor_password),
                'password_hint' => DeliverySetting::maskSecret($this->zheldor_password),
            ],
            'cdek' => [
                'client_id' => $this->cdek_client_id,
                'client_secret_set' => filled($this->cdek_client_secret),
                'client_secret_hint' => DeliverySetting::maskSecret($this->cdek_client_secret),
                'use_test_api' => (bool) $this->cdek_use_test_api,
            ],
        ];
    }

    private function providerStatuses(): array
    {
        $providers = config('delivery.providers', []);

        return collect($providers)->map(function (array $config, string $key) {
            return [
                'key' => $key,
                'name' => $config['name'] ?? $key,
                'enabled' => $this->isProviderEnabled($key),
                'configured' => $this->isProviderConfigured($key),
            ];
        })->values()->all();
    }
}
