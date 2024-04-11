<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Milestone;
use App\Models\MilestoneUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MilestoneUser>
 */
class MilestoneUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $milestoneId = Milestone::inRandomOrder()->pluck('id')->first();
        $userId = User::inRandomOrder()->pluck('id')->first();

        return [
            'milestone_id' => $milestoneId,
            'user_id' => $userId,
        ];
    }
}
