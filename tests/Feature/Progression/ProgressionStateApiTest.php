<?php

namespace Tests\Feature\Progression;

use App\Models\Progression;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

class ProgressionStateApiTest extends ProgressionTestCase
{
    private const STEEL = 'mekanism:steel_casing';

    /** Stage 1 has five milestones and a module of 4 steel casings; stage 2 has one. */
    private function fiveMilestoneModel(array $module = []): void
    {
        $this->stage->update(['module' => $module]);
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $this->milestone(['name' => $name]);
        }
        $this->milestone(['name' => 'f', 'stage_id' => $this->stage(2)->id]);
        $this->publish()->assertCreated();
    }

    public function test_events_complete_a_milestone_once_whatever_the_replays(): void
    {
        $this->fiveMilestoneModel();
        $progressionId = $this->player()->active_progression_id;

        $this->sendEvents($progressionId, [$this->completed('evt-00000001', 'arffornia/a')])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'applied')
            ->assertJsonPath('state.completed.0.key', 'arffornia/a');
        $this->sendEvents($progressionId, [$this->completed('evt-00000001', 'arffornia/a'), $this->completed('evt-00000002', 'arffornia/a')])
            ->assertJsonPath('results.*.status', ['duplicate', 'duplicate']);

        $this->assertSame(1, DB::table('progression_milestones')->where('progression_id', $progressionId)->count());
    }

    public function test_an_unknown_milestone_is_rejected_without_failing_the_batch(): void
    {
        $this->fiveMilestoneModel();
        $progressionId = $this->player()->active_progression_id;

        $this->sendEvents($progressionId, [$this->completed('evt-00000001', 'arffornia/nope'), $this->completed('evt-00000002', 'arffornia/b')])
            ->assertOk()
            ->assertJsonPath('results.0.reason', 'unknown_milestone')
            ->assertJsonPath('results.1.status', 'applied');
    }

    public function test_a_stage_stays_closed_below_its_threshold(): void
    {
        $this->fiveMilestoneModel();
        $progressionId = $this->player()->active_progression_id;

        $this->sendEvents($progressionId, [
            $this->completed('evt-00000001', 'arffornia/a'),
            $this->completed('evt-00000002', 'arffornia/b'),
            $this->completed('evt-00000003', 'arffornia/c'),
        ])->assertJsonPath('state.open_stage', 1);
    }

    public function test_threshold_plus_module_opens_the_next_stage_for_the_whole_team(): void
    {
        $this->fiveMilestoneModel([['item' => self::STEEL, 'count' => 4]]);
        $progressionId = $this->player()->active_progression_id;
        $this->sendEvents($progressionId, array_map(fn ($k, $i) => $this->completed("evt-0000000{$i}", "arffornia/{$k}"), ['a', 'b', 'c', 'd'], [1, 2, 3, 4]))
            ->assertJsonPath('state.open_stage', 1);

        $delivery = ['id' => 'dlv-00000001', 'type' => 'module_delivered', 'stage' => 1, 'item' => self::STEEL, 'count' => 4];
        $this->sendEvents($progressionId, [$delivery])
            ->assertJsonPath('state.open_stage', 2)
            ->assertJsonPath('state.stages.0.module_done', true)
            ->assertJsonPath('state.next', ['arffornia/e', 'arffornia/f']);
        $this->sendEvents($progressionId, [$delivery])->assertJsonPath('results.0.status', 'duplicate');

        $this->assertSame(4, (int) DB::table('progression_deliveries')->value('count'));
        $this->assertSame(1, DB::table('progression_events')->where('type', 'stage_opened')->count());
        $this->assertSame(2, Progression::find($progressionId)->maxStage->number);
    }

    public function test_an_item_outside_the_module_is_rejected(): void
    {
        $this->fiveMilestoneModel([['item' => self::STEEL, 'count' => 4]]);

        $this->sendEvents($this->player()->active_progression_id, [['id' => 'dlv-00000001', 'type' => 'module_delivered', 'stage' => 1, 'item' => 'minecraft:dirt', 'count' => 4]])
            ->assertJsonPath('results.0.reason', 'not_in_module');
    }

    public function test_events_are_refused_before_any_publish_and_for_a_retired_progression(): void
    {
        $player = $this->player();
        $this->milestone(['name' => 'a']);
        $this->sendEvents($player->active_progression_id, [$this->completed('evt-00000001', 'arffornia/a')])->assertStatus(409);

        $this->publish()->assertCreated();
        $team = Team::create(['id' => (string) Str::uuid(), 'name' => 'Gone', 'progression_id' => $player->active_progression_id]);
        $team->delete();

        $this->sendEvents($player->active_progression_id, [$this->completed('evt-00000001', 'arffornia/a')])->assertStatus(409);
    }

    public function test_events_need_the_game_server_role(): void
    {
        $this->fiveMilestoneModel();
        $progressionId = $this->player()->active_progression_id;

        $this->asService('team_editor')->postJson("/api/progressions/{$progressionId}/events", ['events' => [$this->completed('evt-00000001', 'arffornia/a')]])
            ->assertForbidden();
    }

    public function test_the_old_add_route_writes_the_same_state(): void
    {
        $this->fiveMilestoneModel();
        $player = $this->player();
        $milestone = \App\Models\Milestone::where('key', 'arffornia/c')->first();

        $this->asService('progression_editor')->postJson('/api/progression/add', ['player_uuid' => $player->uuid, 'milestone_id' => $milestone->id])->assertOk();

        $this->getJson("/api/progressions/{$player->active_progression_id}/state")
            ->assertOk()
            ->assertJsonPath('completed.0.key', 'arffornia/c');
    }

    public function test_the_player_route_returns_the_active_progression_state(): void
    {
        $this->fiveMilestoneModel();
        $player = $this->player();

        $this->getJson("/api/players/{$player->uuid}/progression/state")
            ->assertOk()
            ->assertJsonPath('progression_id', $player->active_progression_id)
            ->assertJsonPath('stages.0.total', 5);
    }

    public function test_a_private_progression_is_hidden_from_others_but_not_from_its_member(): void
    {
        $this->fiveMilestoneModel();
        $player = $this->player();
        Sanctum::actingAs($player);
        $this->putJson("/api/progressions/{$player->active_progression_id}/visibility", ['public' => false])->assertOk()->assertJsonPath('public', false);

        $this->getJson("/api/progressions/{$player->active_progression_id}/state")->assertOk();

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->player());
        $this->getJson("/api/progressions/{$player->active_progression_id}/state")->assertNotFound();
    }

    public function test_only_a_member_or_an_admin_can_switch_visibility(): void
    {
        $owner = $this->player();
        Sanctum::actingAs($this->player());

        $this->putJson("/api/progressions/{$owner->active_progression_id}/visibility", ['public' => false])->assertForbidden();
        $this->assertTrue(Progression::find($owner->active_progression_id)->is_public);
    }

    public function test_stats_count_progressions_per_stage_and_milestone_completion(): void
    {
        $this->fiveMilestoneModel();
        $fast = $this->player()->active_progression_id;
        $this->player();
        $this->sendEvents($fast, array_map(fn ($k, $i) => $this->completed("evt-0000000{$i}", "arffornia/{$k}"), ['a', 'b', 'c', 'd'], [1, 2, 3, 4]));

        $stats = $this->asService('admin')->getJson('/api/stats/progression')->assertOk();

        $stats->assertJsonPath('progressions', 2)
            ->assertJsonPath('active_progressions', 1)
            ->assertJsonPath('stages.0.progressions', 1)
            ->assertJsonPath('stages.1.progressions', 1)
            ->assertJsonPath('stages.1.samples', 1)
            ->assertJsonPath('milestones.0.rate', 1);
    }

    public function test_playtime_keeps_the_highest_total_of_the_day(): void
    {
        $player = $this->player();
        $report = fn (int $seconds) => $this->asService('progression_editor')->postJson('/api/players/playtime', ['player_uuid' => $player->uuid, 'day' => '2026-11-20', 'seconds' => $seconds]);

        $report(3600)->assertOk()->assertJsonPath('seconds', 3600);
        $report(1200)->assertOk()->assertJsonPath('seconds', 3600);
        $report(5400)->assertOk()->assertJsonPath('seconds', 5400);
    }
}
