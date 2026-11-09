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

    private function addToProgression(int $progressionId, int $milestoneId): TestResponse
    {
        return $this->asService('progression_editor')->postJson('/api/progression/add', [
            'progression_id' => $progressionId,
            'milestone_id' => $milestoneId,
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

    public function test_adding_by_progression_id_completes_that_progression_not_the_players_active_one(): void
    {
        $player = $this->player();
        $milestone = $this->milestone();
        $soloProgressionId = $player->solo_progression_id;
        $teamUuid = (string) Str::uuid();
        $this->sync($player, $teamUuid, 1_000)->assertOk();

        $this->addToProgression($soloProgressionId, $milestone->id)->assertOk()->assertJsonStructure(['message']);

        $this->assertSame([$milestone->id], array_values(Progression::find($soloProgressionId)->completed_milestones));
        $this->assertSame([], Team::find($teamUuid)->progression->completed_milestones);
    }

    public function test_adding_to_a_retired_progression_is_409(): void
    {
        $player = $this->player();
        $milestone = $this->milestone();
        $teamUuid = (string) Str::uuid();
        $this->sync($player, $teamUuid, 1_000)->assertOk();
        $teamProgressionId = Team::find($teamUuid)->progression_id;
        $this->sync($player, null, 2_000)->assertOk();

        $this->addToProgression($teamProgressionId, $milestone->id)->assertConflict()->assertJsonStructure(['message']);

        $this->assertSame([], Progression::find($teamProgressionId)->completed_milestones);
    }

    public function test_adding_to_an_unknown_progression_is_404(): void
    {
        $milestone = $this->milestone();

        $this->addToProgression(999_999, $milestone->id)->assertNotFound();
    }

    public function test_adding_an_unknown_milestone_by_progression_id_is_422(): void
    {
        $player = $this->player();

        $this->addToProgression($player->active_progression_id, 999_999)->assertUnprocessable();
    }
}
