<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have users to report
        $users = \App\Models\User::where('role', 'user')->get();

        if ($users->count() < 2) {
            $this->command->info('Not enough users to generate reports. Please seed users first.');
            return;
        }

        // Create 10 dummy reports
        for ($i = 0; $i < 10; $i++) {
            $reported = $users->random();
            $reporter = $users->where('id', '!=', $reported->id)->random();

            \App\Models\Report::create([
                'reported_user_id' => $reported->id,
                'reported_by_user_id' => $reporter->id,
                'reason' => fake()->sentence(),
                'status' => fake()->randomElement(['open', 'resolved', 'dismissed']),
                'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            ]);
        }
    }
}
