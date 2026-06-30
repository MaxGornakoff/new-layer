<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 64)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('color', 64);
            $table->decimal('diameter', 4, 2)->default(1.75);
            $table->unsignedInteger('weight_grams')->default(1000);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index('color');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
