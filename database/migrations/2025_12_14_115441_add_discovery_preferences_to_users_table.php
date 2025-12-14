<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'discovery_distance')) {
                $table->integer('discovery_distance')->nullable()->default(50);
                $table->integer('discovery_age_max')->nullable()->default(10);
                $table->string('discovery_dog_sex')->nullable()->default('any');
                $table->string('discovery_dog_size')->nullable()->default('any');
                $table->boolean('is_visible')->default(true);
                $table->json('preferences')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'discovery_distance',
                'discovery_age_max',
                'discovery_dog_sex',
                'discovery_dog_size',
                'is_visible',
                'preferences'
            ]);
        });
    }
};
