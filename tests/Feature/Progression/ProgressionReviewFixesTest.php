<?php

namespace Tests\Feature\Progression;

use App\Models\Milestone;
use App\Models\Progression;
use Illuminate\Support\Facades\DB;

class ProgressionReviewFixesTest extends ProgressionTestCase
{
    private function model(array $module = []): void
    {
        $this->stage->update(['module' => $module]);
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $this->milestone(['name' => $name]);
        }
        $this->milestone(['name' => 'f', 'stage_id' => $this->stage(2)->id]);
        $this->stage(3, module: [['item' => 'arffornia:core', 'count' => 1]]);
        $this->publish()->assertCreated();
    }

    public function test_a_batch_reads_the_snapshot_document_at_most_once_and_stays_within_a_query_budget(): void
    {
        $this->model();
        $progressionId = $this->player()->active_progression_id;
        $events = [];
        foreach (range(1, 21) as $i) {
            $events[] = $this->completed(sprintf('evt-%08d', $i), 'arffornia/' . ['a', 'b', 'c', 'd', 'e', 'f'][$i % 6]);
        }
        $request = $this->asService('progression_editor');
        app('cache')->flush();
        DB::enableQueryLog();

        $request->postJson("/api/progressions/{$progressionId}/events", ['events' => $events])->assertOk();

        $queries = array_column(DB::getQueryLog(), 'query');
        $documentReads = array_filter($queries, fn (string $q) => str_contains($q, '"document"'));
        $this->assertLessThanOrEqual(1, count($documentReads));
        $this->assertLessThan(30, count($queries));
    }

    public function test_events_record_the_version_they_were_checked_against(): void
    {
        $this->model();
        $progressionId = $this->player()->active_progression_id;

        $this->sendEvents($progressionId, [$this->completed('evt-00000001', 'arffornia/a')]);

        $this->assertSame(1, (int) DB::table('progression_events')->where('event_id', 'evt-00000001')->value('snapshot_version'));
    }

    public function test_a_new_id_for_a_done_milestone_is_kept_as_a_noop_so_its_replay_is_a_duplicate(): void
    {
        $this->model();
        $progressionId = $this->player()->active_progression_id;
        $this->sendEvents($progressionId, [$this->completed('evt-00000001', 'arffornia/a')]);

        $this->sendEvents($progressionId, [$this->completed('evt-00000002', 'arffornia/a')])->assertJsonPath('results.0.status', 'duplicate');

        $this->assertSame('{"noop":true}', DB::table('progression_events')->where('event_id', 'evt-00000002')->value('payload'));
    }

    public function test_a_module_counts_only_for_the_highest_open_stage(): void
    {
        $this->model();

        $this->sendEvents($this->player()->active_progression_id, [['id' => 'dlv-00000001', 'type' => 'module_delivered', 'stage' => 3, 'item' => 'arffornia:core', 'count' => 1]])
            ->assertJsonPath('results.0.reason', 'stage_not_current');
    }

    public function test_a_stale_progression_catches_up_on_its_next_state_read(): void
    {
        $this->model();
        $progressionId = $this->player()->active_progression_id;
        $this->sendEvents($progressionId, [$this->completed('evt-00000001', 'arffornia/a')])->assertJsonPath('state.open_stage', 1);
        $this->stage->update(['threshold' => 0.2]);
        $this->asService('admin')->postJson('/api/progression/drafts/current/publish');
        Progression::whereKey($progressionId)->update(['evaluated_version' => 1, 'open_stage' => 1]);

        $this->getJson("/api/progressions/{$progressionId}/state")->assertJsonPath('open_stage', 2);
    }

    public function test_dropping_a_milestone_from_the_draft_drops_its_id_from_the_legacy_list(): void
    {
        $this->model();
        $player = $this->player();
        $kept = Milestone::where('key', 'arffornia/a')->first();
        $dropped = Milestone::where('key', 'arffornia/b')->first();
        Progression::whereKey($player->active_progression_id)->update(['completed_milestones' => json_encode([$kept->id, $dropped->id])]);
        $document = $this->asService('admin')->getJson('/api/progression/drafts/current')->json('document');
        $document['milestones'] = array_values(array_filter($document['milestones'], fn ($m) => $m['key'] !== 'arffornia/b'));

        $this->asService('admin')->putJson('/api/progression/drafts/current', ['document' => $document])->assertOk();

        $this->assertSame([$kept->id], Progression::find($player->active_progression_id)->completed_milestones);
    }

    public function test_a_draft_with_broken_references_is_refused_before_any_write(): void
    {
        $this->milestone();
        $document = $this->asService('admin')->getJson('/api/progression/drafts/current')->json('document');
        $document['milestones'][] = [...$document['milestones'][0], 'title' => ['en' => 'Copy']];
        $document['milestones'][] = [...$document['milestones'][0], 'key' => 'arffornia/far', 'stage' => 9];

        $this->asService('admin')->putJson('/api/progression/drafts/current', ['document' => $document])
            ->assertUnprocessable()
            ->assertJsonFragment(['code' => 'duplicate_key'])
            ->assertJsonFragment(['code' => 'unknown_stage']);
        $this->assertSame('Basic machines', Milestone::first()->name);
    }

    public function test_reading_or_validating_the_draft_writes_no_key(): void
    {
        DB::table('milestones')->insert(['name' => 'Old one', 'description' => '', 'stage_id' => $this->stage->id, 'reward_progress_points' => 0, 'icon_type' => 'tech', 'x' => 0, 'y' => 0]);

        $this->asService('admin')->getJson('/api/progression/drafts/current')->assertJsonPath('document.milestones.0.key', 'arffornia/old_one');
        $this->asService('admin')->postJson('/api/progression/drafts/current/validate')->assertOk();

        $this->assertNull(DB::table('milestones')->value('key'));
    }

    public function test_weak_and_star_etags_revalidate_and_numbered_versions_are_immutable(): void
    {
        $this->milestone();
        $this->publish()->assertCreated();
        $etag = trim($this->getJson('/api/progression/model')->headers->get('ETag'), '"');

        $this->withHeader('If-None-Match', 'W/"' . $etag . '"')->get('/api/progression/model')->assertStatus(304);
        $this->withHeader('If-None-Match', '*')->get('/api/progression/model')->assertStatus(304);
        $this->assertStringContainsString('immutable', $this->getJson('/api/progression/model?version=1')->headers->get('Cache-Control'));
    }

    public function test_progressions_older_than_the_first_publish_give_no_stage_time_sample(): void
    {
        $old = $this->player()->active_progression_id;
        Progression::whereKey($old)->update(['created_at' => now()->subYear()]);
        $this->model();

        $this->sendEvents($old, array_map(fn ($k, $i) => $this->completed("evt-0000000{$i}", "arffornia/{$k}"), ['a', 'b', 'c', 'd'], [1, 2, 3, 4]))
            ->assertJsonPath('state.open_stage', 2);

        $this->asService('admin')->getJson('/api/stats/progression')->assertJsonPath('stages.1.samples', 0);
    }
}
