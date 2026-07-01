<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('home_bg_color_end', 7)->nullable()->after('home_bg_color');
        });

        DB::table('categories')
            ->whereNotNull('home_accent_color')
            ->update(['home_bg_color_end' => DB::raw('home_accent_color')]);

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('home_accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('home_accent_color', 7)->nullable()->after('home_bg_color');
        });

        DB::table('categories')
            ->whereNotNull('home_bg_color_end')
            ->update(['home_accent_color' => DB::raw('home_bg_color_end')]);

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('home_bg_color_end');
        });
    }
};
