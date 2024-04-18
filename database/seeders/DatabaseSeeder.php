<?php

namespace Database\Seeders;

use App\Models\Milestone;
use App\Models\MilestoneClosure;
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
        Stage::factory(3)->create();

        Milestone::create([
            'name' => 'Mekanism Factory Tier 1',
            'description' => 'Basic of meka',
            'stage_id' => 1,
            'reward_progress_points' => 20,
            'is_root' => true,
            'icon_type' => 'tech',
        ]);

        Milestone::create([
            'name' => 'Mekanism Factory Tier 2',
            'description' => 'Basic of meka',
            'stage_id' => 2,
            'reward_progress_points' => 200,
            'is_root' => false,
            'icon_type' => 'tech',
        ]);

        Milestone::create([
            'name' => 'Mekanism Factory Tier 3',
            'description' => 'Basic of meka',
            'stage_id' => 3,
            'reward_progress_points' => 2000,
            'is_root' => false,
            'icon_type' => 'tech',
        ]);

        Milestone::create([
            'name' => 'Mekanism Pipe Tier 1',
            'description' => 'Basic of meka',
            'stage_id' => 2,
            'reward_progress_points' => 20,
            'is_root' => false,
            'icon_type' => 'pipe',
        ]);

        Milestone::create([
            'name' => 'Mekanism Pipe Tier 2',
            'description' => 'Basic of meka',
            'stage_id' => 3,
            'reward_progress_points' => 200,
            'is_root' => false,
            'icon_type' => 'pipe',

        ]);

        Milestone::create([
            'name' => 'Botania Tier 1',
            'description' => 'Basic of bota',
            'stage_id' => 1,
            'reward_progress_points' => 20,
            'is_root' => true,
            'icon_type' => 'magic',

        ]);

        Milestone::create([
            'name' => 'Botania Tier 2',
            'description' => 'Basic of bota',
            'stage_id' => 2,
            'reward_progress_points' => 200,
            'is_root' => false,
            'icon_type' => 'magic',

        ]);
        
        MilestoneClosure::create([
            'milestone_id' => 1,
            'descendant_id' => 2,
        ]);

        MilestoneClosure::create([
            'milestone_id' => 2,
            'descendant_id' => 3,
        ]);

        MilestoneClosure::create([
            'milestone_id' => 1,
            'descendant_id' => 4,
        ]);

        MilestoneClosure::create([
            'milestone_id' => 4,
            'descendant_id' => 5,
        ]);

        MilestoneClosure::create([
            'milestone_id' => 6,
            'descendant_id' => 7,
        ]);

        User::factory(10)->create();
        Vote::factory(25)->create();

        for($i = 0; $i < 10; $i++) {
            MilestoneUser::factory()->create();
        }
    }
}
