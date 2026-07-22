<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'Красный' => '#EF4444',
            'красный' => '#EF4444',
            'Чёрный' => '#111827',
            'Черный' => '#111827',
            'чёрный' => '#111827',
            'черный' => '#111827',
            'Белый' => '#FFFFFF',
            'белый' => '#FFFFFF',
            'Синий' => '#3B82F6',
            'синий' => '#3B82F6',
            'Зелёный' => '#22C55E',
            'Зеленый' => '#22C55E',
            'Жёлтый' => '#EAB308',
            'Желтый' => '#EAB308',
            'Оранжевый' => '#F97316',
            'Серый' => '#64748B',
            'Фиолетовый' => '#A855F7',
            'Розовый' => '#EC4899',
        ];

        foreach ($map as $from => $to) {
            DB::table('products')
                ->where('color', $from)
                ->update(['color' => $to]);
        }

        $products = DB::table('products')
            ->whereNotNull('color')
            ->where('color', '!=', '')
            ->get(['id', 'color']);

        foreach ($products as $product) {
            $color = (string) $product->color;
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) {
                continue;
            }

            $normalized = '#'.strtoupper(substr($color, 1));
            if ($normalized !== $color) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['color' => $normalized]);
            }
        }
    }

    public function down(): void
    {
        // Необратимая нормализация hex; откат не восстанавливает текстовые названия.
    }
};
