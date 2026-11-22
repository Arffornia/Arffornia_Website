<?php

namespace Tests\Feature\Progression;

use App\Models\ProgressionDraft;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;

/** Spec 019 item 2: undo, redo and the audit log of an editor draft. */
class ProgressionDraftHistoryTest extends ProgressionTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Brian']);
    }

    private function openDraft(): array
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson('/api/progression/drafts')->assertSuccessful()->json();
    }

    private function act(array $draft, string $action, array $payload): array
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/actions", [
            'revision' => $draft['revision'], 'action' => $action, 'payload' => $payload,
        ])->assertOk()->json();
    }

    private function undo(array $draft): TestResponse
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/undo", ['revision' => $draft['revision']]);
    }

    private function redo(array $draft): TestResponse
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/redo", ['revision' => $draft['revision']]);
    }

    private static function keys(array $draft): array
    {
        return array_column($draft['document']['milestones'], 'key');
    }

    public static function routes(): array
    {
        return [
            'undo' => ['post', '/api/progression/drafts/{id}/undo'],
            'redo' => ['post', '/api/progression/drafts/{id}/redo'],
            'history' => ['get', '/api/progression/drafts/{id}/history'],
        ];
    }

    #[DataProvider('routes')]
    public function test_only_admins_reach_the_history_routes(string $method, string $uri): void
    {
        $id = ProgressionDraft::create(['document' => ['stages' => [], 'graphs' => [], 'milestones' => []], 'base_hash' => str_repeat('0', 64)])->id;
        $uri = str_replace('{id}', (string) $id, $uri);

        $this->json($method, $uri)->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'user']), 'sanctum')->json($method, $uri)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->asService('progression_editor')->json($method, $uri)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->assertNotContains($this->actingAs($this->admin, 'sanctum')->json($method, $uri)->status(), [401, 403]);
    }

    public function test_undo_reverts_the_last_action_and_redo_replays_it(): void
    {
        $this->milestone();
        $opened = $this->openDraft();
        $this->assertFalse($opened['can_undo']);

        $edited = $this->act($opened, 'milestone.create', ['title' => ['en' => 'Steel'], 'graph' => 'arffornia', 'stage' => 1]);
        $this->assertTrue($edited['can_undo']);

        $undone = $this->undo($edited)->assertOk()->json();
        $this->assertSame($opened['document'], $undone['document']);
        $this->assertSame(2, $undone['revision']);
        $this->assertTrue($undone['can_redo']);
        $this->assertFalse($undone['can_undo']);

        $redone = $this->redo($undone)->assertOk()->json();
        $this->assertSame($edited['document'], $redone['document']);
        $this->assertFalse($redone['can_redo']);
    }

    public function test_undo_walks_back_several_actions_and_restores_a_deleted_milestone_with_its_links(): void
    {
        $first = $this->milestone(['name' => 'First']);
        $this->link($first, $this->milestone(['name' => 'Second']));
        $opened = $this->openDraft();

        $draft = $this->act($opened, 'unlock.upsert', ['key' => 'arffornia/first', 'item' => 'minecraft:furnace', 'gate_mode' => 'crafting']);
        $draft = $this->act($draft, 'milestone.delete', ['key' => 'arffornia/first']);
        $this->assertSame(['arffornia/second'], self::keys($draft));

        $draft = $this->undo($draft)->assertOk()->json();
        $this->assertSame(['arffornia/first', 'arffornia/second'], self::keys($draft));
        $this->assertSame(['arffornia/first'], $draft['document']['milestones'][1]['requires']);
        $draft = $this->undo($draft)->assertOk()->json();

        $this->assertSame($opened['document'], $draft['document']);
        $this->undo($draft)->assertUnprocessable()->assertJsonPath('reason', 'nothing_to_undo');
    }

    public function test_a_new_action_after_an_undo_drops_the_redo(): void
    {
        $this->milestone();
        $draft = $this->act($this->openDraft(), 'milestone.update', ['key' => 'arffornia/basic_machines', 'icon_type' => 'magic']);
        $draft = $this->undo($draft)->json();

        $draft = $this->act($draft, 'milestone.update', ['key' => 'arffornia/basic_machines', 'icon_type' => 'pipe']);

        $this->assertFalse($draft['can_redo']);
        $this->redo($draft)->assertUnprocessable()->assertJsonPath('reason', 'nothing_to_redo');
        $this->assertSame('pipe', $draft['document']['milestones'][0]['icon_type']);
    }

    public function test_an_import_is_undone_like_any_action(): void
    {
        $this->milestone();
        $opened = $this->openDraft();
        $document = $opened['document'];
        $document['milestones'] = [];

        $imported = $this->actingAs($this->admin, 'sanctum')->putJson("/api/progression/drafts/{$opened['id']}/document", ['revision' => 0, 'document' => $document])->assertOk()->json();
        $this->assertSame([], $imported['document']['milestones']);

        $this->assertSame($opened['document'], $this->undo($imported)->assertOk()->json('document'));
    }

    public function test_undo_and_redo_refuse_a_stale_revision(): void
    {
        $this->milestone();
        $draft = $this->act($this->openDraft(), 'milestone.update', ['key' => 'arffornia/basic_machines', 'icon_type' => 'magic']);

        $this->undo([...$draft, 'revision' => 0])->assertConflict()->assertJsonPath('revision', 1);
    }

    public function test_the_audit_log_says_who_did_what_and_when_newest_first(): void
    {
        $this->milestone();
        $draft = $this->act($this->openDraft(), 'milestone.update', ['key' => 'arffornia/basic_machines', 'icon_type' => 'magic']);
        $draft = $this->undo($draft)->json();
        $this->redo($draft)->assertOk();
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/discard")->assertOk();

        $entries = $this->actingAs($this->admin, 'sanctum')->getJson("/api/progression/drafts/{$draft['id']}/history")->assertOk()->json('entries');

        $this->assertSame(['discard', 'redo', 'undo', 'edit', 'open'], array_column($entries, 'type'));
        $edit = $entries[3];
        $this->assertSame(['milestone.update', 'arffornia/basic_machines', 'applied', 'Brian', 1], [$edit['action'], $edit['subject'], $edit['state'], $edit['user'], $edit['revision']]);
        $this->assertSame($edit['id'], $entries[2]['target_id']);
        $this->assertNotNull($edit['at']);
        $this->assertSame('tables', $entries[4]['subject']);
    }

    public function test_an_action_that_changes_nothing_is_not_logged(): void
    {
        $this->milestone();
        $draft = $this->openDraft();

        $same = $this->act($draft, 'milestone.update', ['key' => 'arffornia/basic_machines', 'icon_type' => 'tech']);

        $this->assertSame(0, $same['revision']);
        $this->assertFalse($same['can_undo']);
    }
}
