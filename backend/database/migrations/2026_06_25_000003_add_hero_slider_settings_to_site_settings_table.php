<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('hero_slider_autoplay')->default(true)->after('catalog_auto_apply_filters');
            $table->unsignedSmallInteger('hero_slider_autoplay_interval')->default(6)->after('hero_slider_autoplay');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['hero_slider_autoplay', 'hero_slider_autoplay_interval']);
        });
    }
};
