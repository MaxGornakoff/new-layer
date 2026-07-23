<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuSection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@shop.local'],
            [
                'name' => 'Администратор',
                'phone' => '+70000000000',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'client@shop.local'],
            [
                'name' => 'Тестовый клиент',
                'phone' => '+71111111111',
                'password' => 'password',
                'role' => User::ROLE_USER,
            ]
        );

        $categories = [
            ['name' => 'ABS', 'slug' => 'abs', 'sort_order' => 1],
            ['name' => 'PLA', 'slug' => 'pla', 'sort_order' => 2],
            ['name' => 'PETG', 'slug' => 'petg', 'sort_order' => 3],
            ['name' => 'Композитные филаменты', 'slug' => 'composite', 'sort_order' => 4],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $samples = [
            ['category' => 'pla', 'name' => 'PLA Красный', 'color' => '#EF4444', 'price' => 890],
            ['category' => 'pla', 'name' => 'PLA Чёрный', 'color' => '#111827', 'price' => 890],
            ['category' => 'abs', 'name' => 'ABS Белый', 'color' => '#FFFFFF', 'price' => 950],
            ['category' => 'petg', 'name' => 'PETG Синий', 'color' => '#3B82F6', 'price' => 920],
            ['category' => 'composite', 'name' => 'Carbon PLA', 'color' => '#111827', 'price' => 1450],
        ];

        foreach ($samples as $index => $sample) {
            $category = Category::where('slug', $sample['category'])->firstOrFail();
            $slug = Str::slug($sample['name']);
            $sku = 'FIL-'.Str::upper($sample['category']).'-'.($index + 1);

            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $category->id,
                    'name' => $sample['name'],
                    'slug' => $slug,
                    'description' => 'Филамент для 3D-печати, диаметр 1.75 мм, катушка 1 кг.',
                    'price' => $sample['price'],
                    'color' => $sample['color'],
                    'diameter' => 1.75,
                    'weight_grams' => 1000,
                    'stock_quantity' => 25,
                    'is_active' => true,
                ]
            );
        }

        $menuSections = [
            [
                'key' => 'catalog',
                'placement' => MenuSection::PLACEMENT_HEADER,
                'title' => 'Каталог',
                'url' => '/catalog',
                'type' => MenuSection::TYPE_DROPDOWN,
                'include_categories' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'about',
                'placement' => MenuSection::PLACEMENT_HEADER,
                'title' => 'О нас',
                'url' => '/#about-us',
                'type' => MenuSection::TYPE_LINK,
                'include_categories' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'delivery',
                'placement' => MenuSection::PLACEMENT_HEADER,
                'title' => 'Доставка',
                'url' => '/delivery',
                'type' => MenuSection::TYPE_LINK,
                'include_categories' => false,
                'sort_order' => 3,
            ],
            [
                'key' => 'marketplaces',
                'placement' => MenuSection::PLACEMENT_HEADER,
                'title' => 'Маркетплейсы',
                'url' => null,
                'type' => MenuSection::TYPE_DROPDOWN,
                'include_categories' => false,
                'sort_order' => 4,
            ],
            [
                'key' => 'wholesale',
                'placement' => MenuSection::PLACEMENT_HEADER,
                'title' => 'Оптовикам',
                'url' => '/wholesale',
                'type' => MenuSection::TYPE_LINK,
                'include_categories' => false,
                'sort_order' => 5,
            ],
            [
                'key' => 'footer-catalog',
                'placement' => MenuSection::PLACEMENT_FOOTER,
                'title' => 'Каталог',
                'url' => '/catalog',
                'type' => MenuSection::TYPE_DROPDOWN,
                'include_categories' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'footer-buyers',
                'placement' => MenuSection::PLACEMENT_FOOTER,
                'title' => 'Покупателям',
                'url' => null,
                'type' => MenuSection::TYPE_DROPDOWN,
                'include_categories' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'footer-business',
                'placement' => MenuSection::PLACEMENT_FOOTER,
                'title' => 'Для бизнеса',
                'url' => null,
                'type' => MenuSection::TYPE_DROPDOWN,
                'include_categories' => false,
                'sort_order' => 3,
            ],
            [
                'key' => 'footer-legal',
                'placement' => MenuSection::PLACEMENT_FOOTER,
                'title' => 'Документы',
                'url' => null,
                'type' => MenuSection::TYPE_DROPDOWN,
                'include_categories' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($menuSections as $sectionData) {
            MenuSection::updateOrCreate(
                ['key' => $sectionData['key']],
                $sectionData
            );
        }

        MenuItem::updateOrCreate(
            [
                'parent_key' => 'marketplaces',
                'title' => 'Наш магазин на Ozon',
            ],
            [
                'url' => 'https://www.ozon.ru/',
                'sort_order' => 1,
                'open_in_new_tab' => true,
            ]
        );

        $footerItems = [
            ['parent_key' => 'footer-buyers', 'title' => 'Доставка и оплата', 'url' => '/delivery', 'sort_order' => 1],
            ['parent_key' => 'footer-buyers', 'title' => 'Возврат и обмен', 'url' => '/delivery', 'sort_order' => 2],
            ['parent_key' => 'footer-buyers', 'title' => 'Гарантия качества', 'url' => '/#about-us', 'sort_order' => 3],
            ['parent_key' => 'footer-buyers', 'title' => 'Частые вопросы', 'url' => '/faq', 'sort_order' => 4],
            ['parent_key' => 'footer-business', 'title' => 'Оптовый прайс-лист', 'url' => '/wholesale', 'sort_order' => 1],
            ['parent_key' => 'footer-business', 'title' => 'Маркетплейсы', 'url' => '/wholesale', 'sort_order' => 2],
            ['parent_key' => 'footer-business', 'title' => 'Отсрочка платежа', 'url' => '/wholesale', 'sort_order' => 3],
            ['parent_key' => 'footer-business', 'title' => 'Реквизиты компании', 'url' => '/wholesale', 'sort_order' => 4],
            ['parent_key' => 'footer-legal', 'title' => 'Политика конфиденциальности', 'url' => '/legal/privacy-policy', 'sort_order' => 1],
            ['parent_key' => 'footer-legal', 'title' => 'Политика использования cookie', 'url' => '/legal/cookie-policy', 'sort_order' => 2],
            ['parent_key' => 'footer-legal', 'title' => 'Согласие на обработку персональных данных', 'url' => '/legal/personal-data-consent', 'sort_order' => 3],
            ['parent_key' => 'footer-legal', 'title' => 'Публичная оферта', 'url' => '/legal/public-offer', 'sort_order' => 4],
            ['parent_key' => 'footer-legal', 'title' => 'Пользовательское соглашение', 'url' => '/legal/user-agreement', 'sort_order' => 5],
        ];

        foreach ($footerItems as $item) {
            MenuItem::updateOrCreate(
                [
                    'parent_key' => $item['parent_key'],
                    'title' => $item['title'],
                ],
                [
                    'url' => $item['url'],
                    'sort_order' => $item['sort_order'],
                    'open_in_new_tab' => false,
                    'is_active' => true,
                ]
            );
        }
    }
}
