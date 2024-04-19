<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\User;
use App\Models\Vote;
use App\Models\Stage;
use App\Models\Milestone;
use App\Models\MilestoneUser;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MilestoneClosure;

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

        News::create([
            'title' => "Un magnifique article 1 !",
            'content' => "<h1>Salut je suis un titre de l'article 1</h1><p>Et moi je suis le paragraphe 1</p><p>Et moi je suis le paragraphe 2</p>",
            'imgUrl' => "https://cdn.discordapp.com/attachments/704424365856391168/1230794306781053000/406df7cc0a0eeaf367b53705a70f2e90.jpg?ex=66349d85&is=66222885&hm=106a3e568b25aae97c49bc2999ddb4b83ac6209fdfd89f75ffba3b3c22b73525&",
        ]);
        News::create([
            'title' => "Un autre magnifique article 2 !",
            'content' => "<h1>Salut je suis un titre de l'article 2</h1><p>Et moi je suis le paragraphe 1</p><p>Et moi je suis le paragraphe 2</p>",
            'imgUrl' => "https://cdn.discordapp.com/attachments/704424365856391168/1230795311082111026/Minecraft-cherry-blossom-2248584-wallhere.com.jpg?ex=66349e75&is=66222975&hm=4fe6dd56d9131775daf4bf9071dc841ece2df932de2457bbda21b462ffedd976&",
        ]);
        News::create([
            'title' => "Un tres long titre pour voir ce que ca fait 3",
            'content' => "<h1>Salut je suis un titre de l'article 3</h1><p>Et moi je suis le paragraphe 1</p><p>Et moi je suis le paragraphe 2</p>",
            'imgUrl' => "https://cdn.discordapp.com/attachments/704424365856391168/1230795311082111026/Minecraft-cherry-blossom-2248584-wallhere.com.jpg?ex=66349e75&is=66222975&hm=4fe6dd56d9131775daf4bf9071dc841ece2df932de2457bbda21b462ffedd976&",
        ]);
        News::create([
            'title' => "je suis short 4",
            'content' => "<h1>Salut je suis un titre de l'article 4</h1><p>Et moi je suis le paragraphe 1</p><p>Et moi je suis le paragraphe 2</p>",
            'imgUrl' => "https://cdn.discordapp.com/attachments/704424365856391168/1230794306781053000/406df7cc0a0eeaf367b53705a70f2e90.jpg?ex=66349d85&is=66222885&hm=106a3e568b25aae97c49bc2999ddb4b83ac6209fdfd89f75ffba3b3c22b73525&",
        ]);
    }
}
