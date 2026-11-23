<?php

namespace Tests\Feature\Progression;

use App\Models\Milestone;
use App\Models\ModelSnapshot;
use App\Models\ProgressionDraft;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;

/** Spec 019 item 3: validate, diff and publish an editor draft. */
class ProgressionDraftPublishTest extends ProgressionTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->admin();
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

    private function publishDraft(array $draft, array $body = []): TestResponse
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/publish", $body);
    }

    /** One complete milestone on stage 1, so the draft has neither errors nor warnings. */
    private function cleanTree(): void
    {
        $this->milestone(['name' => 'First'])->requirements()->create(['item_id' => 'minecraft:stone', 'display_name' => 'Stone', 'amount' => 1]);
    }

    public static function routes(): array
    {
        return [
            'validate' => ['post', '/api/progression/drafts/{id}/validate'],
            'diff' => ['get', '/api/progression/drafts/{id}/diff'],
            'publish' => ['post', '/api/progression/drafts/{id}/publish'],
        ];
    }

    #[DataProvider('routes')]
    public function test_only_admins_reach_the_publish_routes(string $method, string $uri): void
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

    public function test_publish_is_refused_while_the_draft_breaks_rule_2_and_nothing_is_written(): void
    {
        $this->stage(2);
        $this->cleanTree();
        $draft = $this->openDraft();
        $draft = $this->act($draft, 'milestone.create', ['title' => ['en' => 'Later'], 'graph' => 'arffornia', 'stage' => 2]);
        $draft = $this->act($draft, 'link.create', ['from' => 'arffornia/later', 'to' => 'arffornia/first']);

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/validate")
            ->assertOk()->assertJsonPath('valid', false)->assertJsonPath('findings.0.code', 'requires_later_stage');
        $this->publishDraft($draft, ['confirm_warnings' => true])->assertUnprocessable()
            ->assertJsonPath('reason', 'publish_errors')->assertJsonPath('findings.0.code', 'requires_later_stage');

        $this->assertSame(0, ModelSnapshot::count());
        $this->assertNull(Milestone::where('key', 'arffornia/later')->first());
        $this->assertSame('open', ProgressionDraft::find($draft['id'])->status);
    }

    public function test_warnings_need_a_confirmation(): void
    {
        $this->milestone();
        $draft = $this->openDraft();

        $this->publishDraft($draft)->assertUnprocessable()
            ->assertJsonPath('reason', 'publish_needs_confirmation')->assertJsonPath('needs_confirmation', true)->assertJsonPath('findings.0.code', 'no_validation');
        $this->assertSame(0, ModelSnapshot::count());

        $this->publishDraft($draft, ['confirm_warnings' => true])->assertCreated()->assertJsonPath('version', 1);
    }

    public function test_publish_writes_the_tables_freezes_a_snapshot_and_closes_the_draft(): void
    {
        $this->cleanTree();
        $draft = $this->openDraft();
        $draft = $this->act($draft, 'milestone.update', ['key' => 'arffornia/first', 'title' => ['en' => 'Renamed']]);

        $published = $this->publishDraft($draft, ['notes' => 'First editor publish'])->assertCreated();

        $this->assertSame(1, $published->json('version'));
        $this->assertSame(['published', 1], [$published->json('draft.status'), $published->json('draft.published_version')]);
        $this->assertSame('Renamed', Milestone::where('key', 'arffornia/first')->value('name'));
        $snapshot = ModelSnapshot::where('version', 1)->first();
        $this->assertSame(['First editor publish', $this->admin->id], [$snapshot->notes, $snapshot->published_by]);
        $this->getJson('/api/progression/model')->assertOk()->assertJsonPath('milestones.0.title.en', 'Renamed');

        $history = $this->actingAs($this->admin, 'sanctum')->getJson("/api/progression/drafts/{$draft['id']}/history")->json('entries.0');
        $this->assertSame(['publish', 'version 1'], [$history['type'], $history['subject']]);
        $this->publishDraft($draft)->assertUnprocessable()->assertJsonPath('reason', 'draft_closed');
        $this->assertNotSame($draft['id'], $this->openDraft()['id']);
    }

    public function test_a_change_to_the_tables_after_opening_the_draft_is_a_warning(): void
    {
        $this->cleanTree();
        $draft = $this->openDraft();
        $this->milestone(['name' => 'Written in game'])->requirements()->create(['item_id' => 'minecraft:dirt', 'display_name' => 'Dirt', 'amount' => 1]);

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/validate")
            ->assertOk()->assertJsonPath('valid', true)->assertJsonPath('findings.0.code', 'tables_changed');
        $this->publishDraft($draft)->assertUnprocessable()->assertJsonPath('needs_confirmation', true);
    }

    public function test_the_diff_lists_changes_against_the_published_version_or_an_older_one(): void
    {
        $this->cleanTree();
        $this->publish()->assertCreated();
        $draft = $this->openDraft();
        $draft = $this->act($draft, 'milestone.create', ['title' => ['en' => 'Second'], 'graph' => 'arffornia', 'stage' => 1]);
        $draft = $this->act($draft, 'link.create', ['from' => 'arffornia/first', 'to' => 'arffornia/second']);

        $diff = $this->actingAs($this->admin, 'sanctum')->getJson("/api/progression/drafts/{$draft['id']}/diff")->assertOk();

        $diff->assertJsonPath('against', 1)
            ->assertJsonPath('milestones.added', ['arffornia/second'])
            ->assertJsonPath('links.added', [['from' => 'arffornia/first', 'to' => 'arffornia/second']])
            ->assertJsonPath('marks', ['arffornia/second' => 'added'])
            ->assertJsonPath('empty', false);
        $this->actingAs($this->admin, 'sanctum')->getJson("/api/progression/drafts/{$draft['id']}/diff?against=1")->assertOk()->assertJsonPath('against', 1);
        $this->actingAs($this->admin, 'sanctum')->getJson("/api/progression/drafts/{$draft['id']}/diff?against=7")->assertUnprocessable()->assertJsonPath('reason', 'unknown_version');
        $this->actingAs($this->admin, 'sanctum')->getJson("/api/progression/drafts/{$draft['id']}/diff?against=x")->assertUnprocessable();
    }

    public function test_before_any_publish_the_diff_shows_everything_as_added(): void
    {
        $this->cleanTree();
        $draft = $this->openDraft();

        $this->actingAs($this->admin, 'sanctum')->getJson("/api/progression/drafts/{$draft['id']}/diff")->assertOk()
            ->assertJsonPath('against', null)->assertJsonPath('milestones.added', ['arffornia/first']);
    }
}
