<?php

namespace Tests\Feature\Progression;

/** The viewer pages once PROGRESSION_VIEWER=api (spec 018 item 3 on the spec 017 API). */
class ProgressionViewerApiModeTest extends ProgressionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['progression.viewer' => 'api']);
    }

    public function test_player_page_reads_the_published_model_and_the_player_state_by_uuid(): void
    {
        $player = $this->player(['name' => 'Alex']);

        $response = $this->get('/progression/Alex');

        $response->assertOk();
        $response->assertSee('api\\/progression\\/model', false);
        $response->assertSee("api\\/players\\/{$player->uuid}\\/progression\\/state", false);
        $response->assertDontSee(__('progression.preview'));
        $response->assertSee('icons\\/manifest.json', false);
    }
}
