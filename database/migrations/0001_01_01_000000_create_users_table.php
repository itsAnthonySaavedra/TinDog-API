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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('display_name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->boolean('is_master_admin')->default(false);
            $table->string('plan')->nullable()->default('chihuahua');
            $table->string('location')->nullable();
            $table->text('owner_bio')->nullable();
            $table->string('signup_date')->nullable();
            $table->string('last_seen')->nullable();
            $table->string('dog_name')->nullable();
            $table->string('dog_breed')->nullable();
            $table->integer('dog_age')->nullable();
            $table->string('dog_sex')->nullable();
            $table->string('dog_size')->nullable();
            $table->text('dog_bio')->nullable();
            $table->string('dog_avatar')->nullable();
            $table->string('dog_cover_photo')->nullable();
            $table->timestamps(); // This adds created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};