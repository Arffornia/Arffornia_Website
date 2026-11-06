<?php

namespace Tests\Feature\Api;

use App\Models\MilestoneUnlock;
use App\Models\PendingReward;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Role-protected endpoints called by the mod (progression_editor, user_editor, team_editor)
 * and the proxy (nuvotifier_service).
 */
class ProtectedEndpointsContractTest extends ApiTestCase
{
    /**
     * Each protected route used by a client, with the roles that may call it.
     *
     * @return array<string, array{string, string, list<string>}>
     */
    public static function protectedRoutes(): array
    {
        $progression = ['admin', 'progression_editor'];

        return [
            'progression/add' => ['POST', '/api/progression/add', $progression],
            'progression/remove' => ['POST', '/api/progression/remove', $progression],
            'progression/list' => ['POST', '/api/progression/list', $progression],
            'progression/set-target' => ['POST', '/api/progression/set-target', $progression],
            'migration/items-to-migrate' => ['GET', '/api/migration/items-to-migrate', $progression],
            'migration/submit-batch-recipes' => ['POST', '/api/migration/submit-batch-recipes', $progression],
            'milestone-items/move' => ['POST', '/api/milestone-items/move', $progression],
            'player/ensure-exists' => ['POST', '/api/player/ensure-exists', ['admin', 'user_editor']],
            'teams/player/join' => ['POST', '/api/teams/player/join', ['admin', 'team_editor']],
            'teams/player/leave' => ['POST', '/api/teams/player/leave', ['admin', 'team_editor']],
            'teams/disband' => ['POST', '/api/teams/disband', ['admin', 'team_editor']],
            'vote/nuvotifier' => ['POST', '/api/vote/nuvotifier', ['nuvotifier_service']],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_route_requires_a_token(string $method, string $uri, array $roles): void
    {
        $this->json($method, $uri)->assertUnauthorized();
    }

    #[DataProvider('protectedRoutes')]
    public function test_route_rejects_tokens_without_an_allowed_role(string $method, string $uri, array $roles): void
    {
        $allRoles = ['admin', 'progression_editor', 'user_editor', 'team_editor', 'nuvotifier_service', 'user'];
        $denied = array_values(array_diff($allRoles, $roles));

        foreach ($denied as $role) {
            $this->asService($role)->json($method, $uri)
                ->assertForbidden()
                ->assertExactJson(['message' => 'Forbidden']);
        }
    }

    #[DataProvider('protectedRoutes')]
    public function test_route_accepts_each_allowed_role(string $method, string $uri, array $roles): void
    {
        foreach ($roles as $role) {
            // No body: allowed callers get past auth and reach validation or a 200, never 401/403
            $status = $this->asService($role)->json($method, $uri)->status();
            $this->assertNotContains($status, [401, 403], "$role should be allowed on $method $uri (got $status)");
        }
    }

    public function test_ensure_exists_creates_then_reports_exists_then_updated(): void
    {
        $uuid = str_replace('-', '', (string) Str::uuid());

        $this->asService('user_editor')->postJson('/api/player/ensure-exists', ['uuid' => $uuid, 'username' => 'Steve'])
            ->assertCreated()->assertExactJson(['status' => 'created']);
        $this->asService('user_editor')->postJson('/api/player/ensure-exists', ['uuid' => $uuid, 'username' => 'Steve'])
            ->assertOk()->assertExactJson(['status' => 'exists']);
        $this->asService('user_editor')->postJson('/api/player/ensure-exists', ['uuid' => $uuid, 'username' => 'Steve2'])
            ->assertOk()->assertExactJson(['status' => 'updated']);

        $this->assertSame('Steve2', User::where('uuid', $uuid)->sole()->name);
    }

    public function test_ensure_exists_validates_input(): void
    {
        $this->asService('user_editor')->postJson('/api/player/ensure-exists', ['uuid' => 'short', 'username' => 'Steve'])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['uuid']]);
    }

    public function test_progression_add_list_remove_round_trip(): void
    {
        $player = $this->player();
        $milestone = $this->milestone();
        $body = ['player_uuid' => $player->uuid, 'milestone_id' => $milestone->id];

        $this->asService('progression_editor')->postJson('/api/progression/add', $body)
            ->assertOk()->assertJsonStructure(['message']);

        $this->asService('progression_editor')->postJson('/api/progression/list', ['player_uuid' => $player->uuid])
            ->assertOk()
            ->assertExactJson(['completed_milestones' => [$milestone->id]]);

        $this->asService('progression_editor')->postJson('/api/progression/remove', $body)->assertOk();

        $this->asService('progression_editor')->postJson('/api/progression/list', ['player_uuid' => $player->uuid])
            ->assertOk()
            ->assertExactJson(['completed_milestones' => []]);
    }

    public function test_progression_add_for_unknown_player_is_404(): void
    {
        $milestone = $this->milestone();

        $this->asService('progression_editor')
            ->postJson('/api/progression/add', ['player_uuid' => str_repeat('0', 32), 'milestone_id' => $milestone->id])
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_progression_add_with_unknown_milestone_is_422(): void
    {
        $player = $this->player();

        $this->asService('progression_editor')
            ->postJson('/api/progression/add', ['player_uuid' => $player->uuid, 'milestone_id' => 999999])
            ->assertUnprocessable();
    }

    public function test_set_target_updates_the_active_progression(): void
    {
        $player = $this->player();
        $milestone = $this->milestone();

        $this->asService('progression_editor')
            ->postJson('/api/progression/set-target', ['player_uuid' => $player->uuid, 'milestone_id' => $milestone->id])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertSame($milestone->id, $player->activeProgression()->first()->current_milestone_id);
    }

    public function test_items_to_migrate_lists_item_ids_without_recipe(): void
    {
        $milestone = $this->milestone();
        MilestoneUnlock::create(['milestone_id' => $milestone->id, 'item_id' => 'minecraft:furnace', 'recipes_to_ban' => ['minecraft:furnace']]);
        MilestoneUnlock::create(['milestone_id' => $milestone->id, 'item_id' => 'minecraft:stick', 'recipes_to_ban' => []]);

        $this->asService('progression_editor')->getJson('/api/migration/items-to-migrate')
            ->assertOk()
            ->assertExactJson(['minecraft:furnace']);
    }

    public function test_set_requirements_from_game_replaces_requirements(): void
    {
        $milestone = $this->milestone();
        $milestone->requirements()->create(['item_id' => 'minecraft:dirt', 'display_name' => 'Dirt', 'amount' => 1]);

        $this->asService('progression_editor')
            ->postJson("/api/milestones/{$milestone->id}/set-requirements", ['requirements' => [
                ['item_id' => 'minecraft:iron_ingot', 'display_name' => 'Iron Ingot', 'amount' => 4, 'image_path' => 'x.png'],
            ]])
            ->assertOk()
            ->assertJsonStructure(['message', 'total_items'])
            ->assertJsonPath('total_items', 1);

        $this->assertSame(['minecraft:iron_ingot'], $milestone->requirements()->pluck('item_id')->all());
    }

    public function test_team_join_and_leave(): void
    {
        $player = $this->player();
        $teamUuid = (string) Str::uuid();

        $this->asService('team_editor')
            ->postJson('/api/teams/player/join', ['player_uuid' => $player->uuid, 'team_uuid' => $teamUuid, 'team_name' => 'Builders'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $player->refresh();
        $this->assertSame($teamUuid, $player->team_id);
        $this->assertNotSame($player->solo_progression_id, $player->active_progression_id);

        $this->asService('team_editor')
            ->postJson('/api/teams/player/leave', ['player_uuid' => $player->uuid])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $player->refresh();
        $this->assertNull($player->team_id);
        $this->assertSame($player->solo_progression_id, $player->active_progression_id);
    }

    public function test_team_join_validates_input(): void
    {
        $this->asService('team_editor')->postJson('/api/teams/player/join', ['player_uuid' => 'x'])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors']);
    }

    public function test_nuvotifier_vote_records_the_vote_and_a_pending_reward(): void
    {
        $player = $this->player(['name' => 'Voter_01']);

        $this->asService('nuvotifier_service')
            ->postJson('/api/vote/nuvotifier', ['username' => $player->name, 'service_name' => 'serveurs-minecraft.org', 'ip_address' => '203.0.113.7'])
            ->assertOk()
            ->assertExactJson(['success' => true, 'message' => 'Vote recorded successfully.']);

        $vote = Vote::where('user_id', $player->id)->sole();
        $this->assertSame('serveurs-minecraft.org', $vote->site);
        $this->assertSame('203.0.113.7', $vote->ip_address);
        $this->assertSame(1, PendingReward::where('user_id', $player->id)->where('status', 'pending')->count());
    }

    public function test_nuvotifier_vote_for_unknown_player_is_ignored_with_200(): void
    {
        $this->asService('nuvotifier_service')
            ->postJson('/api/vote/nuvotifier', ['username' => 'Ghost', 'service_name' => 'x'])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertSame(0, Vote::count());
    }

    public function test_nuvotifier_vote_validates_input(): void
    {
        $this->asService('nuvotifier_service')->postJson('/api/vote/nuvotifier', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['username', 'service_name']]);
    }
}
