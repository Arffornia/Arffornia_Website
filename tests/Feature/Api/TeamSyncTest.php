<?php

namespace Tests\Feature\Api;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

class TeamSyncTest extends ApiTestCase
{
    private function sync(User $player, ?string $teamUuid, int $observedAtMs, string $teamName = 'Builders'): TestResponse
    {
        return $this->asService('team_editor')->putJson('/api/teams/player/sync', [
            'player_uuid' => $player->uuid,
            'team_uuid' => $teamUuid,
            'team_name' => $teamUuid === null ? null : $teamName,
            'observed_at' => $observedAtMs,
        ]);
    }

    public function test_joining_a_team_switches_to_the_team_progression(): void
    {
        $player = $this->player();
        $teamUuid = (string) Str::uuid();

        $this->sync($player, $teamUuid, 1_000)->assertOk()->assertJsonStructure(['message']);

        $player->refresh();
        $this->assertSame(Team::find($teamUuid)->progression_id, $player->active_progression_id);
    }

    public function test_moving_from_team_a_to_b_soft_deletes_a_when_it_becomes_empty(): void
    {
        $player = $this->player();
        $teamA = (string) Str::uuid();
        $teamB = (string) Str::uuid();
        $this->sync($player, $teamA, 1_000)->assertOk();

        $this->sync($player, $teamB, 2_000)->assertOk();

        $this->assertSoftDeleted('teams', ['id' => $teamA]);
    }

    public function test_moving_from_team_a_to_b_keeps_a_when_members_remain(): void
    {
        $player = $this->player();
        $teammate = $this->player();
        $teamA = (string) Str::uuid();
        $this->sync($player, $teamA, 1_000)->assertOk();
        $this->sync($teammate, $teamA, 1_000)->assertOk();

        $this->sync($player, (string) Str::uuid(), 2_000)->assertOk();

        $this->assertNotSoftDeleted('teams', ['id' => $teamA]);
    }

    public function test_an_older_observation_arriving_late_is_ignored(): void
    {
        $player = $this->player();
        $teamB = (string) Str::uuid();
        $this->sync($player, $teamB, 2_000)->assertOk();

        $this->sync($player, null, 1_000)->assertOk();

        $player->refresh();
        $this->assertSame($teamB, $player->team_id);
    }

    public function test_replaying_the_same_sync_changes_nothing(): void
    {
        $player = $this->player();
        $teamUuid = (string) Str::uuid();
        $this->sync($player, $teamUuid, 1_000)->assertOk();
        $progressionId = $player->refresh()->active_progression_id;

        $this->sync($player, $teamUuid, 1_500)->assertOk();

        $this->assertSame($progressionId, $player->refresh()->active_progression_id);
    }

    public function test_leaving_restores_the_untouched_solo_progression(): void
    {
        $player = $this->player();
        $soloProgressionId = $player->solo_progression_id;
        $this->sync($player, (string) Str::uuid(), 1_000)->assertOk();

        $this->sync($player, null, 2_000)->assertOk();

        $this->assertSame($soloProgressionId, $player->refresh()->active_progression_id);
    }

    public function test_login_sync_repairs_a_stale_state(): void
    {
        $player = $this->player();
        $staleTeam = (string) Str::uuid();
        $this->sync($player, $staleTeam, 1_000)->assertOk();

        $this->sync($player, null, 5_000)->assertOk();

        $player->refresh();
        $this->assertNull($player->team_id);
        $this->assertSoftDeleted('teams', ['id' => $staleTeam]);
    }

    public function test_rejoining_a_soft_deleted_team_restores_its_progression(): void
    {
        $player = $this->player();
        $teamUuid = (string) Str::uuid();
        $this->sync($player, $teamUuid, 1_000)->assertOk();
        $teamProgressionId = Team::find($teamUuid)->progression_id;
        $this->sync($player, null, 2_000)->assertOk();

        $this->sync($player, $teamUuid, 3_000)->assertOk();

        $this->assertSame($teamProgressionId, $player->refresh()->active_progression_id);
    }

    public function test_unknown_player_returns_404(): void
    {
        $this->asService('team_editor')->putJson('/api/teams/player/sync', [
            'player_uuid' => str_repeat('a', 32),
            'team_uuid' => null,
            'observed_at' => 1_000,
        ])->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_team_name_is_required_with_a_team(): void
    {
        $player = $this->player();

        $this->asService('team_editor')->putJson('/api/teams/player/sync', [
            'player_uuid' => $player->uuid,
            'team_uuid' => (string) Str::uuid(),
            'observed_at' => 1_000,
        ])->assertUnprocessable();
    }
}
