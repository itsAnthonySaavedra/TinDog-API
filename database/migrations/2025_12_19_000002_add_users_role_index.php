<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add index on users.role to speed up role-based filtering.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // This is THE critical missing index - every query filters on role='user'
            $table->index('role');
            
            // Also add index on plan since it's used in analytics
            $table->index('plan');
            
            // Index on created_at for date-range queries
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['plan']);
            $table->dropIndex(['created_at']);
        });
    }
};
