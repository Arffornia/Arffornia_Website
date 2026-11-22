<?php

namespace Tests\Feature\Progression;

use App\Models\Milestone;
use App\Models\MilestoneUnlock;
use App\Models\ProgressionDraft;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;

/** Spec 019 item 1: the admin editor's draft API. */
class ProgressionDraftApiTest extends ProgressionTestCase
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

    private function act(array $draft, string $action, array $payload): TestResponse
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/actions", [
            'revision' => $draft['revision'],
            'action' => $action,
            'payload' => $payload,
        ]);
    }

    private function milestoneIn(array $draft, string $key): ?array
    {
        return collect($draft['document']['milestones'])->firstWhere('key', $key);
    }

    public static function routes(): array
    {
        return [
            'open' => ['post', '/api/progression/drafts'],
            'show' => ['get', '/api/progression/drafts/{id}'],
            'act' => ['post', '/api/progression/drafts/{id}/actions'],
            'import' => ['put', '/api/progression/drafts/{id}/document'],
            'export' => ['get', '/api/progression/drafts/{id}/document'],
            'discard' => ['post', '/api/progression/drafts/{id}/discard'],
        ];
    }

    #[DataProvider('routes')]
    public function test_only_admins_reach_the_draft_routes(string $method, string $uri): void
    {
        $id = ProgressionDraft::create(['document' => ['stages' => [], 'graphs' => [], 'milestones' => []], 'base_hash' => str_repeat('0', 64)])->id;
        $uri = str_replace('{id}', (string) $id, $uri);

        $this->json($method, $uri)->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'user']), 'sanctum')->json($method, $uri)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->asService('progression_editor')->json($method, $uri)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $status = $this->actingAs($this->admin, 'sanctum')->json($method, $uri)->status();
        $this->assertNotContains($status, [401, 403], "admin got {$status}");
    }

    public function test_the_editor_page_is_for_admins_only(): void
    {
        $this->get('/admin/progression')->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/admin/progression')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/progression')->assertOk()->assertSee('progression-editor');
    }

    public function test_opening_a_draft_copies_the_tables_and_returns_the_same_draft_until_it_is_closed(): void
    {
        $this->milestone(['name' => 'Basic machines']);

        $first = $this->actingAs($this->admin, 'sanctum')->postJson('/api/progression/drafts')->assertCreated();
        $second = $this->actingAs($this->admin, 'sanctum')->postJson('/api/progression/drafts')->assertOk();

        $this->assertSame($first->json('id'), $second->json('id'));
        $this->assertSame('arffornia/basic_machines', $first->json('document.milestones.0.key'));
        $this->assertSame('draft', $first->json('document.status'));
        $this->assertSame(0, $first->json('revision'));
    }

    public function test_a_draft_can_be_opened_from_a_published_version(): void
    {
        $this->milestone(['name' => 'Old one']);
        $this->publish()->assertCreated();
        $this->milestone(['name' => 'Unpublished']);

        $draft = $this->actingAs($this->admin, 'sanctum')->postJson('/api/progression/drafts', ['from_version' => 1])->assertCreated();

        $this->assertCount(1, $draft->json('document.milestones'));
        $this->assertSame(1, $draft->json('base_version'));
        $this->actingAs($this->admin, 'sanctum')->postJson('/api/progression/drafts/' . $draft->json('id') . '/discard')->assertOk();
        $this->actingAs($this->admin, 'sanctum')->postJson('/api/progression/drafts', ['from_version' => 9])->assertUnprocessable();
    }

    public function test_editor_actions_change_the_draft_but_never_the_live_tables(): void
    {
        $existing = $this->milestone(['name' => 'Basic machines']);
        $draft = $this->openDraft();

        $draft = $this->act($draft, 'milestone.create', ['title' => ['en' => 'Steel'], 'graph' => 'arffornia', 'stage' => 1])->assertOk()->json();
        $draft = $this->act($draft, 'link.create', ['from' => 'arffornia/basic_machines', 'to' => 'arffornia/steel'])->assertOk()->json();
        $draft = $this->act($draft, 'milestone.update', ['key' => 'arffornia/basic_machines', 'title' => ['en' => 'Renamed', 'fr' => 'Renommé']])->assertOk()->json();

        $this->assertSame(['arffornia/basic_machines'], $this->milestoneIn($draft, 'arffornia/steel')['requires']);
        $this->assertSame(['en' => 'Renamed', 'fr' => 'Renommé'], $this->milestoneIn($draft, 'arffornia/basic_machines')['title']);
        $this->assertSame(3, $draft['revision']);
        $this->assertSame(1, Milestone::count());
        $this->assertSame('Basic machines', $existing->fresh()->name);
    }

    public function test_graphs_are_created_updated_and_deleted_only_when_empty(): void
    {
        $this->milestone();
        $draft = $this->openDraft();

        $created = $this->act($draft, 'graph.create', ['name' => ['en' => 'Create Mod'], 'branch' => 'tech', 'mod_id' => 'create'])->assertOk();
        $this->assertSame('create_mod', $created->json('subject'));
        $draft = $this->act($created->json(), 'graph.update', ['key' => 'create_mod', 'name' => ['en' => 'Create'], 'icon_item' => 'create:cogwheel'])->assertOk()->json();
        $graph = collect($draft['document']['graphs'])->firstWhere('key', 'create_mod');
        $this->assertSame(['key' => 'create_mod', 'name' => ['en' => 'Create'], 'branch' => 'tech', 'mod_id' => 'create', 'icon_item' => 'create:cogwheel'], $graph);

        $this->act($draft, 'graph.delete', ['key' => 'arffornia'])->assertUnprocessable()->assertJsonPath('reason', 'graph_not_empty');
        $draft = $this->act($draft, 'graph.delete', ['key' => 'create_mod'])->assertOk()->json();
        $this->assertNull(collect($draft['document']['graphs'])->firstWhere('key', 'create_mod'));
    }

    public function test_stages_are_numbered_in_order_edited_and_deleted_only_when_empty(): void
    {
        $this->milestone();
        $draft = $this->openDraft();

        $draft = $this->act($draft, 'stage.create', ['name' => ['en' => 'Steam'], 'threshold' => 0.75, 'module' => [['item' => 'arffornia:core', 'count' => 2]]])->assertOk()->json();
        $stage = collect($draft['document']['stages'])->firstWhere('number', 2);
        $this->assertSame(['number' => 2, 'name' => ['en' => 'Steam'], 'threshold' => 0.75, 'module' => [['item' => 'arffornia:core', 'count' => 2]]], $stage);

        $draft = $this->act($draft, 'stage.update', ['number' => 2, 'description' => ['en' => 'Boilers'], 'threshold' => 0.5])->assertOk()->json();
        $this->assertSame(['en' => 'Boilers'], collect($draft['document']['stages'])->firstWhere('number', 2)['description']);

        $this->act($draft, 'stage.delete', ['number' => 1])->assertUnprocessable()->assertJsonPath('reason', 'stage_not_empty');
        $draft = $this->act($draft, 'stage.delete', ['number' => 2])->assertOk()->json();
        $this->assertCount(1, $draft['document']['stages']);
    }

    public function test_moving_a_milestone_changes_its_stage_and_graph(): void
    {
        $this->stage(2);
        $this->milestone();
        $draft = $this->openDraft();
        $draft = $this->act($draft, 'graph.create', ['name' => ['en' => 'Magic'], 'branch' => 'magic'])->json();

        $draft = $this->act($draft, 'milestone.move', ['key' => 'arffornia/basic_machines', 'stage' => 2, 'graph' => 'magic'])->assertOk()->json();

        $moved = $this->milestoneIn($draft, 'arffornia/basic_machines');
        $this->assertSame([2, 'magic'], [$moved['stage'], $moved['graph']]);
        $this->act($draft, 'milestone.move', ['key' => 'arffornia/basic_machines', 'stage' => 7])->assertUnprocessable()->assertJsonPath('reason', 'unknown_stage');
    }

    public function test_deleting_a_milestone_removes_the_links_to_it(): void
    {
        $first = $this->milestone(['name' => 'First']);
        $this->link($first, $this->milestone(['name' => 'Second']));
        $draft = $this->openDraft();

        $draft = $this->act($draft, 'milestone.delete', ['key' => 'arffornia/first'])->assertOk()->json();

        $this->assertCount(1, $draft['document']['milestones']);
        $this->assertSame([], $this->milestoneIn($draft, 'arffornia/second')['requires']);
    }

    public function test_links_refuse_self_duplicates_and_missing_ones(): void
    {
        $first = $this->milestone(['name' => 'First']);
        $this->link($first, $this->milestone(['name' => 'Second']));
        $draft = $this->openDraft();

        $this->act($draft, 'link.create', ['from' => 'arffornia/first', 'to' => 'arffornia/first'])->assertUnprocessable()->assertJsonPath('reason', 'link_self');
        $this->act($draft, 'link.create', ['from' => 'arffornia/first', 'to' => 'arffornia/second'])->assertUnprocessable()->assertJsonPath('reason', 'link_exists');
        $draft = $this->act($draft, 'link.delete', ['from' => 'arffornia/first', 'to' => 'arffornia/second'])->assertOk()->json();
        $this->act($draft, 'link.delete', ['from' => 'arffornia/first', 'to' => 'arffornia/second'])->assertUnprocessable()->assertJsonPath('reason', 'link_missing');
    }

    public function test_unlocks_are_added_edited_with_a_recipe_and_deleted_without_any_shop_price(): void
    {
        $this->milestone();
        $draft = $this->openDraft();

        $draft = $this->act($draft, 'unlock.upsert', ['key' => 'arffornia/basic_machines', 'item' => 'mekanism:steel_casing', 'gate_mode' => 'crafting', 'shop_price' => 50])->assertOk()->json();
        $draft = $this->act($draft, 'unlock.upsert', [
            'key' => 'arffornia/basic_machines',
            'item' => 'mekanism:steel_casing',
            'original_item' => 'mekanism:steel_casing',
            'gate_mode' => 'machine',
            'recipes_to_ban' => ['mekanism:steel_casing', ' '],
            'recipe' => ['ingredients' => [['item' => 'minecraft:iron_ingot', 'count' => 4], ['tag' => 'c:glass']], 'result' => [['item' => 'mekanism:steel_casing']], 'energy' => 200, 'time' => 100],
        ])->assertOk()->json();

        $unlocks = $this->milestoneIn($draft, 'arffornia/basic_machines')['unlocks'];
        $this->assertCount(1, $unlocks);
        $this->assertSame([
            'item' => 'mekanism:steel_casing',
            'gate_mode' => 'machine',
            'recipes_to_ban' => ['mekanism:steel_casing'],
            'recipe' => [
                'type' => 'arffornia:mekanism_steel_casing_recipe',
                'ingredients' => [['item' => 'minecraft:iron_ingot', 'count' => 4], ['tag' => 'c:glass']],
                'result' => [['item' => 'mekanism:steel_casing']],
                'energy' => 200,
                'time' => 100,
            ],
        ], $unlocks[0]);

        $this->act($draft, 'unlock.upsert', ['key' => 'arffornia/basic_machines', 'item' => 'not an id', 'gate_mode' => 'crafting'])->assertUnprocessable();
        $draft = $this->act($draft, 'unlock.delete', ['key' => 'arffornia/basic_machines', 'item' => 'mekanism:steel_casing'])->assertOk()->json();
        $this->assertSame([], $this->milestoneIn($draft, 'arffornia/basic_machines')['unlocks']);
        $this->assertSame(0, MilestoneUnlock::count());
    }

    public function test_requirements_make_a_delivery_and_removing_the_last_one_clears_it(): void
    {
        $this->milestone();
        $draft = $this->openDraft();

        $draft = $this->act($draft, 'requirement.upsert', ['key' => 'arffornia/basic_machines', 'item' => 'minecraft:iron_ingot', 'count' => 8])->assertOk()->json();
        $draft = $this->act($draft, 'requirement.upsert', ['key' => 'arffornia/basic_machines', 'item' => 'minecraft:gold_ingot', 'original_item' => 'minecraft:iron_ingot', 'count' => 2])->assertOk()->json();
        $this->assertSame(['type' => 'deliver', 'items' => [['item' => 'minecraft:gold_ingot', 'count' => 2]]], $this->milestoneIn($draft, 'arffornia/basic_machines')['validation']);

        $draft = $this->act($draft, 'requirement.delete', ['key' => 'arffornia/basic_machines', 'item' => 'minecraft:gold_ingot'])->assertOk()->json();
        $this->assertNull($this->milestoneIn($draft, 'arffornia/basic_machines')['validation']);
    }

    public function test_a_stale_revision_is_refused_with_the_current_one(): void
    {
        $this->milestone();
        $draft = $this->openDraft();
        $this->act($draft, 'milestone.update', ['key' => 'arffornia/basic_machines', 'icon_type' => 'magic'])->assertOk();

        $this->act($draft, 'milestone.update', ['key' => 'arffornia/basic_machines', 'icon_type' => 'pipe'])->assertConflict()->assertJsonPath('revision', 1);
    }

    public function test_payloads_are_validated_per_action(): void
    {
        $draft = $this->openDraft();

        $this->act($draft, 'milestone.create', ['title' => ['en' => 'X']])->assertUnprocessable()->assertJsonValidationErrors(['graph', 'stage']);
        $this->act($draft, 'nope', [])->assertUnprocessable()->assertJsonValidationErrors(['action']);
    }

    public function test_import_replaces_the_draft_and_refuses_a_document_outside_the_schema(): void
    {
        $this->milestone();
        $draft = $this->openDraft();
        $document = $draft['document'];
        $document['milestones'][0]['title'] = ['en' => 'Imported'];
        $url = "/api/progression/drafts/{$draft['id']}/document";

        $this->actingAs($this->admin, 'sanctum')->putJson($url, ['revision' => 0, 'document' => ['stages' => []]])->assertUnprocessable()->assertJsonPath('reason', 'import_invalid');
        $imported = $this->actingAs($this->admin, 'sanctum')->putJson($url, ['revision' => 0, 'document' => $document])->assertOk();

        $this->assertSame('Imported', $imported->json('document.milestones.0.title.en'));
        $export = $this->actingAs($this->admin, 'sanctum')->get($url)->assertOk();
        $this->assertStringContainsString('attachment', $export->headers->get('Content-Disposition'));
        $this->assertSame('Imported', $export->json('milestones.0.title.en'));
    }

    public function test_a_discarded_draft_takes_no_more_actions_and_a_new_one_opens(): void
    {
        $this->milestone();
        $draft = $this->openDraft();

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/progression/drafts/{$draft['id']}/discard")->assertOk()->assertJsonPath('status', 'discarded');

        $this->act($draft, 'milestone.update', ['key' => 'arffornia/basic_machines', 'icon_type' => 'pipe'])->assertUnprocessable()->assertJsonPath('reason', 'draft_closed');
        $this->assertNotSame($draft['id'], $this->openDraft()['id']);
    }

    public function test_every_refusal_reason_has_a_message_in_both_locales(): void
    {
        $reasons = [];
        foreach (glob(app_path('Progression/Editor/*.php')) as $file) {
            preg_match_all("/DraftActionRefused\\('([a-z_]+)'/", file_get_contents($file), $m);
            array_push($reasons, ...$m[1]);
        }

        $this->assertNotEmpty($reasons);
        foreach (array_unique($reasons) as $reason) {
            foreach (['en', 'fr'] as $locale) {
                $this->assertTrue(\Illuminate\Support\Facades\Lang::hasForLocale("api.progression_editor.{$reason}", $locale), "{$reason} missing in {$locale}");
            }
        }
    }
}
