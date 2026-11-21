<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressionViewerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_is_not_found_while_it_is_switched_off(): void
    {
        config(['progression.viewer' => 'off']);

        $this->get('/progression')->assertNotFound();
    }

    public function test_catalog_view_loads_the_fixture_without_a_player_state(): void
    {
        config(['progression.viewer' => 'fixture']);

        $response = $this->get('/progression');

        $response->assertOk();
        $response->assertSee('progression-fixture\\/model.json', false);
        $response->assertSee('"stateUrl":null', false);
    }

    public function test_player_view_loads_the_demo_state_for_a_known_player(): void
    {
        config(['progression.viewer' => 'fixture']);
        Stage::factory()->create(['number' => 1]);
        User::factory()->create(['name' => 'Steve']);

        $response = $this->get('/progression/Steve');

        $response->assertOk();
        $response->assertSee('progression-fixture\\/state-demo.json', false);
    }

    public function test_player_view_is_not_found_for_an_unknown_player(): void
    {
        config(['progression.viewer' => 'fixture']);

        $this->get('/progression/NobodyHere')->assertNotFound();
    }

    public function test_player_name_is_escaped_in_the_page(): void
    {
        config(['progression.viewer' => 'fixture']);
        Stage::factory()->create(['number' => 1]);
        User::factory()->create(['name' => '<b>"x']);

        $response = $this->get('/progression/' . rawurlencode('<b>"x'));

        $response->assertOk();
        $response->assertDontSee('<b>"x', false);
    }

    public function test_committed_fixture_files_exist(): void
    {
        foreach (['model.json', 'icons.json', 'state-demo.json'] as $file) {
            $this->assertFileExists(public_path("progression-fixture/{$file}"));
        }
    }
}
