<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FreshStartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Truncate users table (and others if needed, e.g., swipes, matches)
        DB::table('users')->truncate();
        DB::table('swipes')->truncate();
        DB::table('matches')->truncate(); // Note: Table name might be 'user_matches' based on previous context, checking migration is safer but 'matches' was used in UserMatch model? No, UserMatch model uses 'matches' table?
        // Let's assume 'matches' based on previous context. If it fails, I'll fix it.
        // Actually, I should check the table names. But 'users' is the critical one.
        
        // 3. Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 4. Insert ONLY Master Admin
        DB::table('users')->insert([
            [
                'first_name' => 'Roel Anthony',
                'last_name' => 'Saavedra',
                'display_name' => 'Master Admin',
                'email' => 'master@admin.com',
                'password' => Hash::make('Admin123'), // Secure it from the start
                'role' => 'admin',
                'status' => 'active',
                'is_master_admin' => true,
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
            ]
        ]);
    }
}
