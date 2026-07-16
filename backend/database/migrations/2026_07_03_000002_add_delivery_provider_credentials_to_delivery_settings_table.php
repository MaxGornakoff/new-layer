<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->string('cdek_client_id')->nullable()->after('yandex_delivery_enabled');
            $table->text('cdek_client_secret')->nullable()->after('cdek_client_id');
            $table->boolean('cdek_use_test_api')->default(true)->after('cdek_client_secret');
            $table->text('baikal_api_key')->nullable()->after('cdek_use_test_api');
            $table->string('pek_login')->nullable()->after('baikal_api_key');
            $table->text('pek_api_key')->nullable()->after('pek_login');
            $table->text('yandex_delivery_oauth_token')->nullable()->after('pek_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->dropColumn([
                'cdek_client_id',
                'cdek_client_secret',
                'cdek_use_test_api',
                'baikal_api_key',
                'pek_login',
                'pek_api_key',
                'yandex_delivery_oauth_token',
            ]);
        });
    }
};
