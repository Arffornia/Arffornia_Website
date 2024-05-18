<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Stage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
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
            'name' => $this->faker->name(),
            'uuid' => $this->faker->uuid(),
            'money' => random_int(0, 500000),
            'progress_point' => random_int(0, 10000000),
            'stage_id' => $stageId,
            'remember_token' => Str::random(10),
        ];
    }
}
