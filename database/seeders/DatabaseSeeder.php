<?php

namespace Database\Seeders;

use App\Models\Milestone;
use App\Models\MilestoneUser;
use App\Models\Stage;
use App\Models\User;
use App\Models\Vote;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {   
        Stage::factory(25)->create();

        for($i = 0; $i < 300; $i++) {
            Milestone::factory()->create();
        }
        
        User::factory(10)->create();
        Vote::factory(25)->create();

        for($i = 0; $i < 1000; $i++) {
            MilestoneUser::factory()->create();
        }
    }
}
