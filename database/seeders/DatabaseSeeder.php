<?php

namespace Database\Seeders;

use App\Models\LauncherImage;
use App\Models\LauncherVersioning;
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
            'imgUrl' => "images/screenshots1920x1080/caveBg1920x1080.jpg",
        ]);
        News::create([
            'title' => "Un autre magnifique article 2 !",
            'content' => "<h1>Salut je suis un titre de l'article 2</h1><p>Et moi je suis le paragraphe 1</p><p>Et moi je suis le paragraphe 2</p>",
            'imgUrl' => "images/screenshots1920x1080/Cherry-blossomBg1920x1080.png",
        ]);
        News::create([
            'title' => "Un tres long titre pour voir ce que ca fait 3",
            'content' => "<h1>Salut je suis un titre de l'article 3</h1><p>Et moi je suis le paragraphe 1</p><p>Et moi je suis le paragraphe 2</p>",
            'imgUrl' => "images/screenshots1920x1080/caveBg1920x1080.jpg",
        ]);
        News::create([
            'title' => "je suis short 4",
            'content' => "<h1>Salut je suis un titre de l'article 4</h1><p>Et moi je suis le paragraphe 1</p><p>Et moi je suis le paragraphe 2</p>",
            'imgUrl' => "images/screenshots1920x1080/Cherry-blossomBg1920x1080.png",
        ]);

        LauncherImage::create([
            'url' => 'images/screenshots1920x1080/caveBg1920x1080.jpg',
            'in_prod' => true,
        ]);

        LauncherImage::create([
            'url' => 'images/screenshots1920x1080/Cherry-blossomBg1920x1080.png',
            'in_prod' => false,
        ]);

        LauncherVersioning::create([
            'version' => '1.2',
            'hash' => 'FAFrc!@fy5151vgbhnafFAjmlk4@3514e^rfytg!hiuhyfu!hYRTDFYV',
            'in_prod' => true,
        ]);

        LauncherVersioning::create([
            'version' => '1.3',
            'hash' => 'tF46AFA64!ffg^fgja6mlk4@3514e^rfy44!ffg^fgja6mlk4@&d@$hf',
            'in_prod' => false,
        ]);
    }
}
