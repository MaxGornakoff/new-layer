<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('home_title')->nullable()->after('name');
            $table->string('home_bg_color', 7)->nullable()->after('home_title');
            $table->string('home_accent_color', 7)->nullable()->after('home_bg_color');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['home_title', 'home_bg_color', 'home_accent_color']);
        });
    }
};
