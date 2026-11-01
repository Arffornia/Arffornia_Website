<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\Progression;
use App\Models\ShopItem;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Deterministic data for the Playwright end-to-end tests (e2e/).
 * No factories or Faker: values must not change between runs.
 */
class E2ESeeder extends Seeder
{
    public function run(): void
    {
        $stage = Stage::create([
            'number' => 1,
            'name' => 'Stage 1',
            'description' => 'First stage',
            'reward_progress_points' => 1000,
        ]);

        $players = [
            ['name' => 'E2E_Gold', 'uuid' => '00000000000000000000000000000001', 'progress_point' => 3000],
            ['name' => 'E2E_Silver', 'uuid' => '00000000000000000000000000000002', 'progress_point' => 2000],
            ['name' => 'E2E_Bronze', 'uuid' => '00000000000000000000000000000003', 'progress_point' => 1000],
        ];

        foreach ($players as $player) {
            $progression = Progression::create(['max_stage_id' => $stage->id, 'completed_milestones' => []]);

            User::create($player + [
                'money' => 500,
                'stage_id' => $stage->id,
                'day_streak' => 0,
                'grade' => 'citizen',
                'role' => 'user',
                'solo_progression_id' => $progression->id,
                'active_progression_id' => $progression->id,
            ]);
        }

        $news = new News();
        $news->forceFill([
            'title' => 'E2E news title',
            'content' => '<p>E2E news content</p>',
            'imgUrl' => 'images/news/default.png',
        ])->save();

        ShopItem::create([
            'name' => 'E2E Diamond pack',
            'description' => 'Some diamonds',
            'img_url' => 'images/shop/diamond.png',
            'category_id' => 1,
            'real_price' => 100,
            'promo_price' => 0,
            'is_unique' => false,
            'commands' => ['give {player} minecraft:diamond 1'],
        ]);
    }
}
