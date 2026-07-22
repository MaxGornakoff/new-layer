<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->boolean('russian_post_enabled')->default(false)->after('cdek_use_test_api');
            $table->unsignedInteger('russian_post_object_type')->nullable()->after('russian_post_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_settings', function (Blueprint $table) {
            $table->dropColumn(['russian_post_enabled', 'russian_post_object_type']);
        });
    }
};
