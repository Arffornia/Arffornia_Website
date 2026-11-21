<?php

namespace Tests\Feature\Progression;

use App\Models\MilestoneUnlock;

/** The viewer pages and the icon map once PROGRESSION_VIEWER=api (spec 018 item 3 on the spec 017 API). */
class ProgressionViewerApiModeTest extends ProgressionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['progression.viewer' => 'api', 'progression.icon_base' => 'https://arffornia.com/images/item_textures/']);
    }

    public function test_player_page_reads_the_published_model_and_the_player_state_by_uuid(): void
    {
        $player = $this->player(['name' => 'Alex']);

        $response = $this->get('/progression/Alex');

        $response->assertOk();
        $response->assertSee('api\\/progression\\/model', false);
        $response->assertSee("api\\/players\\/{$player->uuid}\\/progression\\/state", false);
        $response->assertDontSee(__('progression.preview'));
    }

    public function test_icon_map_is_not_found_before_a_model_is_published(): void
    {
        $this->get('/progression/icons.json')->assertNotFound();
    }

    public function test_icon_map_names_renders_after_the_items_display_name(): void
    {
        $milestone = $this->milestone();
        MilestoneUnlock::create(['milestone_id' => $milestone->id, 'item_id' => 'mekanism:basic_fluid_tank', 'display_name' => 'Basic Fluid Tank', 'recipes_to_ban' => [], 'gate_mode' => 'machine']);
        $this->publish(acceptRuleErrors: true)->assertCreated();
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/progression/icons.json');

        $response->assertOk();
        $response->assertJsonPath('mekanism:basic_fluid_tank', 'mekanism/Basic Fluid Tank_128x128.png');
    }

    public function test_icon_map_is_not_served_in_fixture_mode(): void
    {
        config(['progression.viewer' => 'fixture']);

        $this->get('/progression/icons.json')->assertNotFound();
    }
}
