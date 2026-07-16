<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->boolean('dellin_enabled')->default(false)->after('baikal_enabled');
            $table->text('dellin_app_key')->nullable()->after('baikal_api_key');
            $table->boolean('zheldor_enabled')->default(false)->after('yandex_delivery_enabled');
            $table->string('zheldor_login')->nullable()->after('yandex_delivery_oauth_token');
            $table->text('zheldor_password')->nullable()->after('zheldor_login');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->dropColumn([
                'dellin_enabled',
                'dellin_app_key',
                'zheldor_enabled',
                'zheldor_login',
                'zheldor_password',
            ]);
        });
    }
};
