<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $items = [
            [
                'parent_key' => 'footer-legal',
                'title' => 'Политика конфиденциальности',
                'url' => '/legal/privacy-policy',
                'sort_order' => 1,
            ],
            [
                'parent_key' => 'footer-legal',
                'title' => 'Политика использования cookie',
                'url' => '/legal/cookie-policy',
                'sort_order' => 2,
            ],
            [
                'parent_key' => 'footer-legal',
                'title' => 'Согласие на обработку персональных данных',
                'url' => '/legal/personal-data-consent',
                'sort_order' => 3,
            ],
            [
                'parent_key' => 'footer-legal',
                'title' => 'Публичная оферта',
                'url' => '/legal/public-offer',
                'sort_order' => 4,
            ],
            [
                'parent_key' => 'footer-legal',
                'title' => 'Пользовательское соглашение',
                'url' => '/legal/user-agreement',
                'sort_order' => 5,
            ],
        ];

        // Rename old privacy title if present
        DB::table('menu_items')
            ->where('parent_key', 'footer-legal')
            ->where('url', '/legal/privacy-policy')
            ->where('title', 'Политика обработки персональных данных')
            ->update([
                'title' => 'Политика конфиденциальности',
                'sort_order' => 1,
                'updated_at' => $now,
            ]);

        foreach ($items as $item) {
            $existing = DB::table('menu_items')
                ->where('parent_key', $item['parent_key'])
                ->where('url', $item['url'])
                ->first();

            if ($existing) {
                DB::table('menu_items')->where('id', $existing->id)->update([
                    'title' => $item['title'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('menu_items')->insert([
                ...$item,
                'is_active' => true,
                'open_in_new_tab' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menu_items')
            ->where('parent_key', 'footer-legal')
            ->whereIn('url', [
                '/legal/public-offer',
                '/legal/user-agreement',
            ])
            ->delete();
    }
};
