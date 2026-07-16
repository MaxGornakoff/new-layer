<?php

return [
    'pack_size' => (int) env('DELIVERY_PACK_SIZE', 10),

    'providers' => [
        'baikal' => [
            'name' => 'Байкал Сервис',
            'api_key' => env('BAIKAL_API_KEY'),
            'api_base_url' => env('BAIKAL_API_BASE_URL', 'https://api.baikalsr.ru'),
            'use_test_api' => (bool) env('BAIKAL_USE_TEST_API', false),
        ],
        'dellin' => [
            'name' => 'Деловые линии',
            'app_key' => env('DELLIN_APP_KEY'),
            'api_base_url' => env('DELLIN_API_BASE_URL', 'https://api.dellin.ru/v2'),
        ],
        'yandex_delivery' => [
            'name' => 'Яндекс Доставка',
            'oauth_token' => env('YANDEX_DELIVERY_OAUTH_TOKEN'),
            'api_base_url' => env('YANDEX_DELIVERY_API_BASE_URL', 'https://b2b-authproxy.taxi.yandex.net'),
            'use_test_api' => (bool) env('YANDEX_DELIVERY_USE_TEST_API', false),
        ],
        'zheldor' => [
            'name' => 'Желдорэкспедиция',
            'login' => env('ZHELDOR_LOGIN'),
            'password' => env('ZHELDOR_PASSWORD'),
            'api_base_url' => env('ZHELDOR_API_BASE_URL', 'https://api.jde.ru'),
            'api_version' => env('ZHELDOR_API_VERSION', 'vD'),
        ],
        'cdek' => [
            'name' => 'СДЭК',
            'client_id' => env('CDEK_CLIENT_ID'),
            'client_secret' => env('CDEK_CLIENT_SECRET'),
            'api_base_url' => env('CDEK_API_BASE_URL', 'https://api.cdek.ru/v2'),
        ],
    ],
];
