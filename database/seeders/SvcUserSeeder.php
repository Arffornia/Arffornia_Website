<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SvcUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Generate a secure secret. DISPLAY ONLY DURING SEEDING.
        $secret = "minecraft-server-svc"; // TODO Remove this (For testing purpose only)
        $svc_id = 'minecraft-server-svc';

        User::updateOrCreate(
            ['name' => $svc_id],
            [
                'uuid' => Hash::make($secret),
                'role' => 'svc,team_editor,progression_editor',
                'grade' => 'system',
                'money' => 0,
                'progress_point' => 0,
                'stage_id' => 1, // Make sure stage 1 exists
                'day_streak' => 0
            ]
        );

        // Display credentials in cleartext TO THE CONSOLE ONLY.
        // Never log or display them elsewhere.
        $this->command->info('Service Account Created/Updated:');
        $this->command->info('  Service ID: ' . $svc_id);
        $this->command->warn('  SECRET (Copy this now, it will not be shown again): ' . $secret);
    }
}
