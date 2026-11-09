<?php

namespace Database\Factories;

use App\Models\Milestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MilestoneUnlock>
 */
class MilestoneUnlockFactory extends Factory
{
    public function definition(): array
    {
        $item = 'mekanism:' . $this->faker->unique()->lexify('item_????');

        return [
            'milestone_id' => Milestone::factory(),
            'item_id' => $item,
            'display_name' => 'Test item',
            'recipes_to_ban' => [$item],
        ];
    }
}
