<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Using raw SQL to avoid doctrine/dbal dependency issues
        // Postgres syntax
        DB::statement('ALTER TABLE users ALTER COLUMN dog_avatar TYPE TEXT');
        DB::statement('ALTER TABLE users ALTER COLUMN dog_cover_photo TYPE TEXT');
        DB::statement('ALTER TABLE users ALTER COLUMN owner_avatar TYPE TEXT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to string (varchar 255) - Warning: Data truncation possible if used
        DB::statement('ALTER TABLE users ALTER COLUMN dog_avatar TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE users ALTER COLUMN dog_cover_photo TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE users ALTER COLUMN owner_avatar TYPE VARCHAR(255)');
    }
};
