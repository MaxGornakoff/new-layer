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
            ['category' => 'pla', 'name' => 'PLA Красный', 'color' => 'Красный', 'price' => 890],
            ['category' => 'pla', 'name' => 'PLA Чёрный', 'color' => 'Чёрный', 'price' => 890],
            ['category' => 'abs', 'name' => 'ABS Белый', 'color' => 'Белый', 'price' => 950],
            ['category' => 'petg', 'name' => 'PETG Синий', 'color' => 'Синий', 'price' => 920],
            ['category' => 'composite', 'name' => 'Carbon PLA', 'color' => 'Чёрный', 'price' => 1450],
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
                'title' => 'Каталог',
                'url' => '/catalog',
                'type' => MenuSection::TYPE_DROPDOWN,
                'include_categories' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'about',
                'title' => 'О нас',
                'url' => '/#about-us',
                'type' => MenuSection::TYPE_LINK,
                'include_categories' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'delivery',
                'title' => 'Доставка',
                'url' => '/delivery',
                'type' => MenuSection::TYPE_LINK,
                'include_categories' => false,
                'sort_order' => 3,
            ],
            [
                'key' => 'marketplaces',
                'title' => 'Маркетплейсы',
                'url' => null,
                'type' => MenuSection::TYPE_DROPDOWN,
                'include_categories' => false,
                'sort_order' => 4,
            ],
            [
                'key' => 'wholesale',
                'title' => 'Оптовикам',
                'url' => '/wholesale',
                'type' => MenuSection::TYPE_LINK,
                'include_categories' => false,
                'sort_order' => 5,
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
    }
}
