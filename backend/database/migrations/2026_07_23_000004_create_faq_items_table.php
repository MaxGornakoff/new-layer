<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_items', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();
        $demos = [
            [
                'question' => 'Какой диаметр филамента вы производите?',
                'answer' => 'Основной ассортимент — филамент диаметром 1.75 мм. Точный диаметр и допуски указаны в карточке каждого товара.',
                'sort_order' => 1,
            ],
            [
                'question' => 'Как оформить заказ?',
                'answer' => 'Выберите товары в каталоге, добавьте их в корзину и перейдите к оформлению. Укажите контакты и способ доставки — мы свяжемся с вами для подтверждения.',
                'sort_order' => 2,
            ],
            [
                'question' => 'Какие способы оплаты доступны?',
                'answer' => 'Сейчас оплата производится наличными или переводом при получении. Онлайн-оплата появится позже — информация будет на сайте.',
                'sort_order' => 3,
            ],
            [
                'question' => 'Сколько занимает доставка?',
                'answer' => 'Срок зависит от города и выбранной службы доставки. Точные сроки и стоимость рассчитываются при оформлении заказа.',
                'sort_order' => 4,
            ],
            [
                'question' => 'Можно ли вернуть или обменять товар?',
                'answer' => 'Товар надлежащего качества можно вернуть или обменять в течение 14 дней с момента получения, если сохранены товарный вид и потребительские свойства.',
                'sort_order' => 5,
            ],
        ];

        foreach ($demos as $item) {
            DB::table('faq_items')->insert([
                ...$item,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Point footer “Частые вопросы” to /faq if present
        DB::table('menu_items')
            ->where('parent_key', 'footer-buyers')
            ->where('title', 'Частые вопросы')
            ->update([
                'url' => '/faq',
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_items');
    }
};
