<?php

namespace Database\Factories;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stage>
 */
class StageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 0,
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->paragraph(),
            'reward_progress_points' => random_int(3000, 50000),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Stage $model) {
            $previousModel = Stage::where('id', '<', $model->id)->orderBy('id', 'desc')->first();
            $number = $previousModel ? $previousModel->number + 1 : 1;
            $model->number = $number;
            $model->save();
        });
    }
}
