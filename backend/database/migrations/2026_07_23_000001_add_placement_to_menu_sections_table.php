<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_sections', function (Blueprint $table) {
            $table->string('placement', 16)->default('header')->after('key');
            $table->index(['placement', 'is_active', 'sort_order']);
        });

        $now = now();

        $footerSections = [
            [
                'key' => 'footer-catalog',
                'placement' => 'footer',
                'title' => 'Каталог',
                'url' => '/catalog',
                'type' => 'dropdown',
                'include_categories' => true,
                'sort_order' => 1,
                'is_active' => true,
                'open_in_new_tab' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'footer-buyers',
                'placement' => 'footer',
                'title' => 'Покупателям',
                'url' => null,
                'type' => 'dropdown',
                'include_categories' => false,
                'sort_order' => 2,
                'is_active' => true,
                'open_in_new_tab' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'footer-business',
                'placement' => 'footer',
                'title' => 'Для бизнеса',
                'url' => null,
                'type' => 'dropdown',
                'include_categories' => false,
                'sort_order' => 3,
                'is_active' => true,
                'open_in_new_tab' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'footer-legal',
                'placement' => 'footer',
                'title' => 'Документы',
                'url' => null,
                'type' => 'dropdown',
                'include_categories' => false,
                'sort_order' => 4,
                'is_active' => true,
                'open_in_new_tab' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($footerSections as $section) {
            $exists = DB::table('menu_sections')->where('key', $section['key'])->exists();
            if (! $exists) {
                DB::table('menu_sections')->insert($section);
            }
        }

        $footerItems = [
            ['parent_key' => 'footer-buyers', 'title' => 'Доставка и оплата', 'url' => '/delivery', 'sort_order' => 1],
            ['parent_key' => 'footer-buyers', 'title' => 'Возврат и обмен', 'url' => '/delivery', 'sort_order' => 2],
            ['parent_key' => 'footer-buyers', 'title' => 'Гарантия качества', 'url' => '/#about-us', 'sort_order' => 3],
            ['parent_key' => 'footer-buyers', 'title' => 'Частые вопросы', 'url' => '/delivery', 'sort_order' => 4],
            ['parent_key' => 'footer-business', 'title' => 'Оптовый прайс-лист', 'url' => '/wholesale', 'sort_order' => 1],
            ['parent_key' => 'footer-business', 'title' => 'Маркетплейсы', 'url' => '/wholesale', 'sort_order' => 2],
            ['parent_key' => 'footer-business', 'title' => 'Отсрочка платежа', 'url' => '/wholesale', 'sort_order' => 3],
            ['parent_key' => 'footer-business', 'title' => 'Реквизиты компании', 'url' => '/wholesale', 'sort_order' => 4],
            ['parent_key' => 'footer-legal', 'title' => 'Политика обработки персональных данных', 'url' => '/legal/privacy-policy', 'sort_order' => 1],
            ['parent_key' => 'footer-legal', 'title' => 'Политика использования cookie', 'url' => '/legal/cookie-policy', 'sort_order' => 2],
            ['parent_key' => 'footer-legal', 'title' => 'Согласие на обработку персональных данных', 'url' => '/legal/personal-data-consent', 'sort_order' => 3],
        ];

        foreach ($footerItems as $item) {
            $exists = DB::table('menu_items')
                ->where('parent_key', $item['parent_key'])
                ->where('title', $item['title'])
                ->exists();

            if (! $exists) {
                DB::table('menu_items')->insert([
                    ...$item,
                    'is_active' => true,
                    'open_in_new_tab' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->whereIn('parent_key', [
            'footer-catalog',
            'footer-buyers',
            'footer-business',
            'footer-legal',
        ])->delete();

        DB::table('menu_sections')->whereIn('key', [
            'footer-catalog',
            'footer-buyers',
            'footer-business',
            'footer-legal',
        ])->delete();

        Schema::table('menu_sections', function (Blueprint $table) {
            $table->dropIndex(['placement', 'is_active', 'sort_order']);
            $table->dropColumn('placement');
        });
    }
};
