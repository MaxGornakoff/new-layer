<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->after('hero_slider_autoplay_interval');
            $table->string('contact_email_business')->nullable()->after('contact_phone');
            $table->string('contact_email_support')->nullable()->after('contact_email_business');
            $table->json('contact_messengers')->nullable()->after('contact_email_support');
        });

        $defaults = [
            'contact_phone' => '+7 (999) 123-45-67',
            'contact_email_business' => 'opt@site.ru',
            'contact_email_support' => 'help@site.ru',
            'contact_messengers' => json_encode([
                [
                    'label' => 'Telegram',
                    'icon' => 'telegram',
                    'url' => 'https://t.me/',
                ],
                [
                    'label' => 'MAX',
                    'icon' => 'max',
                    'url' => 'https://max.com/',
                ],
            ], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ];

        if (DB::table('site_settings')->exists()) {
            DB::table('site_settings')->update($defaults);
        } else {
            DB::table('site_settings')->insert([
                ...$defaults,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_phone',
                'contact_email_business',
                'contact_email_support',
                'contact_messengers',
            ]);
        });
    }
};
