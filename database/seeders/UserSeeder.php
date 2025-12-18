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
        DB::table('users')->delete();

        $users = [
            // --- Master Admin ---
            [
                'first_name' => 'Roel Anthony', 'last_name' => 'Saavedra', 'display_name' => 'Master Admin',
                'email' => 'master@admin.com', 'password' => 'Admin123', 'role' => 'admin',
                'status' => 'active', 'is_master_admin' => true,
                'plan' => null, 'location' => null, 'latitude' => null, 'longitude' => null,
                'owner_bio' => null, 'signup_date' => null, 'last_seen' => null,
                'dog_name' => null, 'dog_breed' => null, 'dog_age' => null, 'dog_sex' => null,
                'dog_size' => null, 'dog_bio' => null, 'dog_avatar' => null, 'dog_cover_photo' => null,
                'owner_avatar' => null, 'dog_photos' => null, 'dog_personalities' => null,
                'created_at' => now(), 'updated_at' => now()
            ],
            // --- Standard User (Roel) ---
            [
                'first_name' => 'Roel Anthony', 'last_name' => 'Saavedra', 'display_name' => 'Roel Anthony Saavedra',
                'email' => 'roel@test.com', 'password' => 'Roel1234', 'role' => 'user',
                'status' => 'active', 'is_master_admin' => false,
                'plan' => 'labrador',
                'location' => 'Consolacion, Cebu', 'latitude' => 10.3802, 'longitude' => 123.9555,
                'owner_bio' => 'Guides our technical direction', 'signup_date' => 'Jul 01, 2024', 'last_seen' => '5 minutes ago',
                'dog_name' => 'Jorjee', 'dog_breed' => 'Shih Tzu', 'dog_age' => 3, 'dog_sex' => 'female', 'dog_size' => 'small',
                'dog_bio' => 'Energetic and playful', 'dog_avatar' => '../../assets/images/jorjee-one.jpg',
                'dog_cover_photo' => null, 'owner_avatar' => null, 'dog_photos' => json_encode([]),
                'dog_personalities' => 'Playful,Energetic,Friendly,Curious',
                'created_at' => now(), 'updated_at' => now()
            ]
        ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }
    }
}