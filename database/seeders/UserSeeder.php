<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This ensures the table is empty before we add new data.
        // Todo: Passwords must be changed to Hashed values in production.
        DB::table('users')->truncate();

        DB::table('users')->insert([
            // --- Master Admin (Unchanged) ---
            [
                'first_name' => 'Roel Anthony',
                'last_name' => 'Saavedra',
                'display_name' => 'Master Admin',
                'email' => 'master@admin.com',
                'password' => 'Admin123',
                'role' => 'admin',
                'status' => 'active',
                'is_master_admin' => true,
                'plan' => null, 'location' => null,
                'owner_bio' => null,
                'signup_date' => null,
                'last_seen' => null,
                'dog_name' => null,
                'dog_breed' => null,
                'dog_age' => null, 
                'dog_sex' => null,
                'dog_size' => null,
                'dog_bio' => null,
                'dog_avatar' => null,
                'dog_cover_photo' => null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // --- Regular Admin ---
            [
                'first_name' => 'Kirk John',
                'last_name' => 'Samutya',
                'display_name' => 'Kirk John Samutya',
                'email' => 'kirk@admin.com',
                'password' => 'Kirk1234',
                'role' => 'admin',
                'status' => 'active',
                'is_master_admin' => false,
                'plan' => null,
                'location' => null,
                'owner_bio' => null,
                'signup_date' => null,
                'last_seen' => null,
                'dog_name' => null,
                'dog_breed' => null,
                'dog_age' => null,
                'dog_sex' => null,
                'dog_size' => null,
                'dog_bio' => null,
                'dog_avatar' => null,
                'dog_cover_photo' => null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            // --- Standard User ---
            [
                'first_name' => 'Roel Anthony',
                'last_name' => 'Saavedra',
                'display_name' => 'Roel Anthony Saavedra',
                'email' => 'roel@test.com',
                'password' => 'Roel1234',
                'role' => 'user',
                'status' => 'active',
                'is_master_admin' => false,
                'plan' => 'labrador',
                'location' => 'Consolacion, Cebu',
                'owner_bio' => 'Guides our technical direction and translates creative concepts into functional, polished applications.',
                'signup_date' => 'Jul 01, 2024',
                'last_seen' => '5 minutes ago',
                'dog_name' => 'Jorjee',
                'dog_breed' => 'Shih Tzu',
                'dog_age' => 3,
                'dog_sex' => 'female',
                'dog_size' => 'small',
                'dog_bio' => 'Energetic and playful, loves chasing balls and long walks. Looking for a companion to explore with!',
                'dog_avatar' => '../assets/images/jorjee-one.jpg',
                'dog_cover_photo' => '../assets/images/jorjee-three.jpg',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}