<?php

namespace Tests\Feature\Api;

use App\Models\Progression;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Machines in the mod belong to the progression they were placed for (Arffornia_Mods#18): they read
 * `retired` and complete milestones by progression id, not through the placer's active progression.
 */
class MachineProgressionTest extends ApiTestCase
{
    private function sync(User $player, ?string $teamUuid, int $observedAtMs): TestResponse
    {
        return $this->asService('team_editor')->putJson('/api/teams/player/sync', [
            'player_uuid' => $player->uuid,
            'team_uuid' => $teamUuid,
            'team_name' => $teamUuid === null ? null : 'Builders',
            'observed_at' => $observedAtMs,
        ]);
    }

    public function test_a_solo_progression_is_not_retired(): void
    {
        $player = $this->player();

        $this->getJson("/api/progression/{$player->active_progression_id}")
            ->assertOk()
            ->assertJsonPath('retired', false);
    }

    public function test_a_live_team_progression_is_not_retired(): void
    {
        $player = $this->player();
        $teamUuid = (string) Str::uuid();
        $this->sync($player, $teamUuid, 1_000)->assertOk();

        $this->getJson('/api/progression/' . Team::find($teamUuid)->progression_id)
            ->assertOk()
            ->assertJsonPath('retired', false);
    }

    public function test_the_progression_of_a_soft_deleted_team_is_retired(): void
    {
        $player = $this->player();
        $teamUuid = (string) Str::uuid();
        $this->sync($player, $teamUuid, 1_000)->assertOk();
        $teamProgressionId = Team::find($teamUuid)->progression_id;

        $this->sync($player, null, 2_000)->assertOk();

        $this->getJson("/api/progression/{$teamProgressionId}")
            ->assertOk()
            ->assertJsonPath('retired', true);
    }
}
