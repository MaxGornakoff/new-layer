<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE hero_slides MODIFY title TEXT NOT NULL');
        DB::statement('ALTER TABLE hero_slides MODIFY subtitle TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE hero_slides MODIFY title VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE hero_slides MODIFY subtitle VARCHAR(500) NULL');
    }
};
