<?php

namespace Database\Seeders;

use App\Models\LauncherImage;
use App\Models\News;
use App\Models\User;
use App\Models\Vote;
use App\Models\Stage;
use App\Models\Milestone;
use App\Models\MilestoneUser;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MilestoneClosure;
use App\Models\ShopItem;

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
            'icon_type' => 'tech',
            'x' => 1,
            'y' => 2,
        ]);

        Milestone::create([
            'name' => 'Mekanism Factory Tier 2',
            'description' => 'Basic of meka',
            'stage_id' => 2,
            'reward_progress_points' => 200,
            'icon_type' => 'tech',
            'x' => 3,
            'y' => 1,
        ]);

        Milestone::create([
            'name' => 'Mekanism Factory Tier 3',
            'description' => 'Basic of meka',
            'stage_id' => 3,
            'reward_progress_points' => 2000,
            'icon_type' => 'tech',
            'x' => 3,
            'y' => 3,
        ]);

        Milestone::create([
            'name' => 'Mekanism Pipe Tier 1',
            'description' => 'Basic of meka',
            'stage_id' => 2,
            'reward_progress_points' => 20,
            'icon_type' => 'pipe',
            'x' => 1,
            'y' => 7,
        ]);

        Milestone::create([
            'name' => 'Mekanism Pipe Tier 2',
            'description' => 'Basic of meka',
            'stage_id' => 3,
            'reward_progress_points' => 200,
            'icon_type' => 'pipe',
            'x' => 3,
            'y' => 6,

        ]);

        Milestone::create([
            'name' => 'Botania Tier 1',
            'description' => 'Basic of bota',
            'stage_id' => 1,
            'reward_progress_points' => 20,
            'icon_type' => 'magic',
            'x' => 3,
            'y' => 8,

        ]);

        Milestone::create([
            'name' => 'Botania Tier 2',
            'description' => 'Basic of bota',
            'stage_id' => 2,
            'reward_progress_points' => 200,
            'icon_type' => 'magic',
            'x' => 6,
            'y' => 6,

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
            'path' => 'images/screenshots1920x1080/caveBg1920x1080.jpg',
            'in_prod' => true,
        ]);

        LauncherImage::create([
            'path' => 'images/screenshots1920x1080/Cherry-blossomBg1920x1080.png',
            'in_prod' => false,
        ]);

        ShopItem::create([
            'name' => "AE2 Kit",
            'description' => "Make your brain great again !",
            'img_url' => "images/shop_items/ae2_icon.png",
            'category_id' => 0,
            'real_price' => 1250,
            'promo_price' => 0,
        ]);

        ShopItem::create([
            'name' => "Meka Kit",
            'description' => "Let's unlock the nuclear power !",
            'img_url' => "images/shop_items/meka_icon.png",
            'category_id' => 0,
            'real_price' => 1250,
            'promo_price' => 0,
        ]);

        ShopItem::create([
            'name' => "Kitten Pet",
            'description' => "Let this kawaii kitten seduce you.",
            'img_url' => "images/shop_items/kitten_pet.png",
            'category_id' => 0,
            'real_price' => 500,
            'promo_price' => 0,
        ]);

        ShopItem::create([
            'name' => "Dragon Pet",
            'description' => "Who says a pet dragon isn't impressive ?",
            'img_url' => "images/shop_items/dragon_pet.png",
            'category_id' => 0,
            'real_price' => 750,
            'promo_price' => 0,
        ]);

        ShopItem::create([
            'name' => "Dinosaur Pet",
            'description' => "The last of his species !",
            'img_url' => "images/shop_items/dinosaur_pet.png",
            'category_id' => 0,
            'real_price' => 750,
            'promo_price' => 0,
        ]);
    }
}
