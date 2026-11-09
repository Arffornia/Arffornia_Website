<?php

namespace Database\Factories;

use App\Models\MilestoneUnlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'arffornia:' . $this->faker->unique()->lexify('recipe_????'),
            'ingredients' => [['item' => 'minecraft:iron_ingot', 'count' => 1]],
            'result' => [['item' => 'minecraft:iron_block', 'count' => 1]],
            'energy' => 0,
            'time' => 0,
            'milestone_unlock_id' => MilestoneUnlock::factory(),
        ];
    }
}
