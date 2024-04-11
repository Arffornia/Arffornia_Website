<?php

namespace Database\Factories;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Milestone>
 */
class MilestoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stageId = Stage::inRandomOrder()->pluck('id')->first();

        return [
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->paragraph(),
            'stage_id' => $stageId,
            'reward_progress_points' => random_int(3000, 50000),
        ];
    }
}
