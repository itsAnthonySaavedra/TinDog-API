<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * These indexes speed up common queries in the TinDog app.
     */
    public function up(): void
    {
        // Index for reverse-swipe lookups (checking if someone swiped on you)
        Schema::table('swipes', function (Blueprint $table) {
            $table->index('target_user_id');
        });

        // Index for match queries (finding matches for a user)
        Schema::table('matches', function (Blueprint $table) {
            $table->index('user_id_1');
            $table->index('user_id_2');
        });

        // Composite index for conversation participant lookups
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->index(['conversation_id', 'user_id']);
        });

        // Index for messages by conversation (faster message loading)
        Schema::table('messages', function (Blueprint $table) {
            $table->index('conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('swipes', function (Blueprint $table) {
            $table->dropIndex(['target_user_id']);
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex(['user_id_1']);
            $table->dropIndex(['user_id_2']);
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'user_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
        });
    }
};
