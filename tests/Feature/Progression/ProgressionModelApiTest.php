<?php

namespace Tests\Feature\Progression;

use App\Models\Milestone;
use App\Models\MilestoneClosure;
use App\Models\Progression;
use App\Progression\ModelValidator;

class ProgressionModelApiTest extends ProgressionTestCase
{
    public function test_model_is_404_until_a_snapshot_is_published(): void
    {
        $this->getJson('/api/progression/model')->assertNotFound();
    }

    public function test_publish_freezes_a_schema_valid_snapshot_served_with_an_etag(): void
    {
        $first = $this->milestone(['name' => 'Basic machines']);
        $this->link($first, $this->milestone(['name' => 'Better machines']));

        $this->publish()->assertCreated()->assertJsonPath('version', 1);

        $response = $this->getJson('/api/progression/model')->assertOk()->assertHeader('X-Model-Version', '1');
        $document = json_decode($response->getContent(), true);
        $this->assertSame([], app(ModelValidator::class)->schemaErrors($document));
        $this->assertSame('published', $document['status']);
        $this->assertSame(['arffornia/basic_machines'], $document['milestones'][1]['requires']);

        $this->withHeader('If-None-Match', $response->headers->get('ETag'))->get('/api/progression/model')->assertStatus(304);
    }

    public function test_a_second_publish_archives_the_first_and_old_versions_stay_readable(): void
    {
        $this->milestone();
        $this->publish()->assertCreated();
        $this->milestone(['name' => 'Another']);

        $this->publish()->assertCreated()->assertJsonPath('version', 2);

        $this->getJson('/api/progression/model')->assertHeader('X-Model-Version', '2');
        $this->assertCount(1, $this->getJson('/api/progression/model?version=1')->assertOk()->json('milestones'));
    }

    public function test_publish_is_refused_on_a_backward_link_unless_rule_errors_are_accepted(): void
    {
        $later = $this->milestone(['name' => 'Later', 'stage_id' => $this->stage(2)->id]);
        $this->link($later, $this->milestone(['name' => 'Earlier']));

        $this->publish()->assertUnprocessable()->assertJsonPath('findings.0.code', 'requires_later_stage');
        $this->publish(acceptRuleErrors: true)->assertCreated();
    }

    public function test_replacing_the_draft_keeps_rows_by_key_and_rebuilds_links(): void
    {
        $kept = $this->milestone(['name' => 'Kept']);
        $dropped = $this->milestone(['name' => 'Dropped']);
        $player = $this->player();
        Progression::whereKey($player->active_progression_id)->update(['current_milestone_id' => $dropped->id]);
        $document = $this->asService('admin')->getJson('/api/progression/drafts/current')->assertOk()->json('document');

        $document['milestones'] = [
            $document['milestones'][0],
            [...$document['milestones'][0], 'key' => 'arffornia/new_one', 'title' => ['en' => 'New one'], 'requires' => ['arffornia/kept'], 'unlocks' => []],
        ];
        $this->asService('admin')->putJson('/api/progression/drafts/current', ['document' => $document])->assertOk();

        $this->assertSame($kept->id, Milestone::where('key', 'arffornia/kept')->value('id'));
        $this->assertNull(Milestone::find($dropped->id));
        $this->assertNull(Progression::find($player->active_progression_id)->current_milestone_id);
        $this->assertSame(1, MilestoneClosure::where('milestone_id', $kept->id)->count());
    }

    public function test_a_draft_round_trip_keeps_empty_and_padded_texts_as_sent(): void
    {
        $this->milestone();
        $document = $this->asService('admin')->getJson('/api/progression/drafts/current')->json('document');
        $document['milestones'][0]['title'] = ['en' => ' Padded ', 'fr' => ''];

        $this->asService('admin')->putJson('/api/progression/drafts/current', ['document' => $document])->assertOk();

        $this->assertSame(' Padded ', \App\Models\Milestone::first()->name);
    }

    public function test_a_draft_that_breaks_the_schema_is_rejected_and_changes_nothing(): void
    {
        $this->milestone();
        $document = $this->asService('admin')->getJson('/api/progression/drafts/current')->json('document');
        $document['milestones'][0]['key'] = 'Not A Key';

        $this->asService('admin')->putJson('/api/progression/drafts/current', ['document' => $document])
            ->assertUnprocessable()
            ->assertJsonPath('findings.0.code', 'schema');
        $this->assertSame('arffornia/basic_machines', Milestone::first()->key);
    }

    public function test_snapshots_carry_no_shop_price(): void
    {
        $milestone = $this->milestone();
        $milestone->unlocks()->create(['item_id' => 'mekanism:steel_casing', 'recipes_to_ban' => [], 'shop_price' => 500]);

        $unlock = $this->asService('admin')->getJson('/api/progression/drafts/current')->json('document.milestones.0.unlocks.0');

        $this->assertArrayNotHasKey('shop_price', $unlock);
    }

    public function test_draft_publish_and_stats_are_admin_only(): void
    {
        $this->getJson('/api/progression/drafts/current')->assertUnauthorized();
        $this->asService('progression_editor')->getJson('/api/progression/drafts/current')->assertForbidden();
        $this->asService('progression_editor')->postJson('/api/progression/drafts/current/publish')->assertForbidden();
        $this->asService('progression_editor')->getJson('/api/stats/progression')->assertForbidden();
    }
}
