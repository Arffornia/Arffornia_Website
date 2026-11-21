<?php

namespace Tests\Feature\Progression;

use App\Models\Milestone;
use App\Models\MilestoneClosure;
use App\Models\Stage;
use App\Models\User;
use Tests\Feature\Api\ApiTestCase;

abstract class ProgressionTestCase extends ApiTestCase
{
    protected function stage(int $number, float $threshold = 0.8, array $module = []): Stage
    {
        return Stage::create([
            'number' => $number,
            'name' => "Stage {$number}",
            'description' => "Stage {$number} description",
            'reward_progress_points' => 0,
            'threshold' => $threshold,
            'module' => $module,
        ]);
    }

    protected function link(Milestone $parent, Milestone $child): void
    {
        MilestoneClosure::create(['milestone_id' => $parent->id, 'descendant_id' => $child->id]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function publish(bool $acceptRuleErrors = false): \Illuminate\Testing\TestResponse
    {
        return $this->asService('admin')->postJson('/api/progression/drafts/current/publish', ['accept_rule_errors' => $acceptRuleErrors]);
    }

    /** @param list<array<string, mixed>> $events */
    protected function sendEvents(int $progressionId, array $events): \Illuminate\Testing\TestResponse
    {
        return $this->asService('progression_editor')->postJson("/api/progressions/{$progressionId}/events", ['events' => $events]);
    }

    protected function completed(string $id, string $key): array
    {
        return ['id' => $id, 'type' => 'milestone_completed', 'milestone' => $key];
    }
}
