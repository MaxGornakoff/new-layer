<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('pack_units_count')->default(10);
            $table->decimal('pack_width_cm', 8, 2)->nullable();
            $table->decimal('pack_length_cm', 8, 2)->nullable();
            $table->decimal('pack_height_cm', 8, 2)->nullable();
            $table->decimal('pack_weight_kg', 8, 3)->nullable();
            $table->string('sender_city')->nullable();
            $table->string('sender_postal_code', 16)->nullable();
            $table->text('sender_address')->nullable();
            $table->boolean('cdek_enabled')->default(false);
            $table->boolean('baikal_enabled')->default(false);
            $table->boolean('pek_enabled')->default(false);
            $table->boolean('yandex_delivery_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_settings');
    }
};
