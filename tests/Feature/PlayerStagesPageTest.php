<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerStagesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_stages_page_renders_for_a_known_player(): void
    {
        Stage::factory()->create(['number' => 1]);
        $player = User::factory()->create();

        $response = $this->get("/stages/{$player->uuid}");

        $response->assertOk();
    }

    public function test_player_stages_page_is_404_for_an_unknown_player(): void
    {
        $response = $this->get('/stages/00000000000000000000000000000000');

        $response->assertNotFound();
    }
}
