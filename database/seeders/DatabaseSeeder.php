<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\User;
use App\Models\Vote;
use App\Models\Stage;
use App\Models\ShopItem;
use App\Models\Milestone;
use App\Models\LauncherImage;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\MilestoneUser;
use App\Models\MilestoneUnlock;
use Illuminate\Database\Seeder;
use App\Models\MilestoneClosure;
use App\Models\MilestoneRequirement;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(SvcUserSeeder::class);

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

        MilestoneClosure::create([
            'milestone_id' => 7,
            'descendant_id' => 6,
        ]);

        MilestoneClosure::create([
            'milestone_id' => 7,
            'descendant_id' => 3,
        ]);

        User::factory(10)->create();
        Vote::factory(25)->create();

        for ($i = 0; $i < 10; $i++) {
            MilestoneUser::factory()->create();
        }

        MilestoneUnlock::create([
            'milestone_id' => 1,
            'item_id' => 'minecraft:oak_planks',
            'display_name' => 'Oak Planks',
            'recipe_id_to_ban' => 'minecraft:oak_planks',
            'shop_price' => 10,
            'image_path' => 'minecraft_oak_planks.png'
        ]);

        MilestoneRequirement::create([
            'milestone_id' => 1,
            'item_id' => 'minecraft:oak_log',
            'display_name' => 'Oak Log',
            'image_path' => 'minecraft_oak_log.png',
            'amount' => 10
        ]);

        MilestoneRequirement::create([
            'milestone_id' => 2,
            'item_id' => 'minecraft:iron_ore',
            'display_name' => 'Iron Ore',
            'image_path' => 'minecraft_iron_ore.png',
            'amount' => 64
        ]);

        MilestoneUnlock::create([
            'milestone_id' => 1,
            'item_id' => 'minecraft:oak_slab',
            'display_name' => 'Oak Slab',
            'recipe_id_to_ban' => 'minecraft:oak_slab',
            'shop_price' => 5,
            'image_path' => 'minecraft_oak_slab.png'
        ]);

        MilestoneUnlock::create([
            'milestone_id' => 2,
            'item_id' => 'minecraft:iron_ingot',
            'display_name' => 'Iron Ingot',
            'recipe_id_to_ban' => 'minecraft:iron_ingot',
            'shop_price' => 42,
            'image_path' => 'minecraft_iron_ingot.png'
        ]);

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
            'commands' => [
                'give {player} minecraft:iron_ingot 64',
                'give {player} minecraft:gold_ingot 32',
                'give {player} minecraft:diamond 8',
                'give {player} minecraft:quartz 64'
            ]
        ]);

        ShopItem::create([
            'name' => "Meka Kit",
            'description' => "Let's unlock the nuclear power !",
            'img_url' => "images/shop_items/meka_icon.png",
            'category_id' => 0,
            'real_price' => 1250,
            'promo_price' => 0,
            'commands' => [
                'give {player} minecraft:iron_block 16',
                'give {player} minecraft:redstone_block 32',
                'give {player} minecraft:netherite_ingot 2',
                'xp add {player} 500 levels'
            ]
        ]);

        ShopItem::create([
            'name' => "Kitten Pet",
            'description' => "Let this kawaii kitten seduce you.",
            'img_url' => "images/shop_items/kitten_pet.png",
            'category_id' => 0,
            'real_price' => 500,
            'promo_price' => 0,
            'commands' => [
                'lp user {player} permission set cosmetic.pet.kitten true',
                'say {player} a adopté un adorable chaton !'
            ]
        ]);

        ShopItem::create([
            'name' => "Dragon Pet",
            'description' => "Who says a pet dragon isn't impressive ?",
            'img_url' => "images/shop_items/dragon_pet.png",
            'category_id' => 0,
            'real_price' => 750,
            'promo_price' => 0,
            'commands' => [
                'lp user {player} permission set cosmetic.pet.dragon true',
                'say {player} est maintenant accompagné d\'un puissant dragon !'
            ]
        ]);

        ShopItem::create([
            'name' => "Dinosaur Pet",
            'description' => "The last of his species !",
            'img_url' => "images/shop_items/dinosaur_pet.png",
            'category_id' => 0,
            'real_price' => 750,
            'promo_price' => 0,
            'commands' => [
                'lp user {player} permission set cosmetic.pet.dinosaur true',
                'say Attention ! {player} a ramené un dinosaure à la vie !'
            ]
        ]);


        //? Note: To generation a hash from plainText:
        //? php artisan tinker
        //? bcrypt('admin');
        User::create([
            'name' => 'svc_ftbu',
            'uuid' => '$2y$12$5LS4/QVEvKVPBZZ09QsSyeJD.DRlxou/F0tt7CdmY8zTZyWJPn9yS',
            'money' => 0,
            'progress_point' => 0,
            'stage_id' => 0,
            'day_streak' => 0,
            'grade' => "",
            'role' => "svc",
        ]);
    }
}
