<?php

namespace Tests\Feature\Progression;

use App\Models\Progression;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/** Spec 019 Q4: every public route that serves a progression hides a private one, as if it did not exist. */
class ProgressionPrivacyTest extends ProgressionTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->milestone(['name' => 'Secret objective']);
        $this->publish()->assertCreated();
        $this->owner = $this->player(['name' => 'Hidden']);
        Progression::whereKey($this->owner->active_progression_id)->update(['is_public' => false, 'current_milestone_id' => \App\Models\Milestone::first()->id]);
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
    }

    /** @return array<string, string> */
    private function jsonRoutes(): array
    {
        $id = $this->owner->active_progression_id;
        $uuid = $this->owner->uuid;

        return [
            'legacy progression' => "/api/progression/{$id}",
            'legacy player graph' => "/api/stages/player/get/{$uuid}",
            'state' => "/api/progressions/{$id}/state",
            'player state' => "/api/players/{$uuid}/progression/state",
        ];
    }

    public function test_json_routes_answer_a_private_progression_like_a_missing_one(): void
    {
        foreach ($this->jsonRoutes() as $name => $url) {
            $this->assertSame(404, $this->getJson($url)->status(), $name);
        }
        $this->assertSame(
            $this->getJson('/api/progressions/999999/state')->json(),
            $this->getJson("/api/progressions/{$this->owner->active_progression_id}/state")->json(),
        );
    }

    public function test_web_pages_hide_a_private_progression(): void
    {
        $this->get("/stages/{$this->owner->uuid}")->assertNotFound();
        $this->get('/profile/Hidden')->assertOk()->assertDontSee('Secret objective');
    }

    public function test_the_game_server_and_the_member_still_see_it(): void
    {
        foreach ($this->jsonRoutes() as $name => $url) {
            $this->asService('progression_editor')->getJson($url)->assertOk();
        }
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($this->owner);
        foreach ($this->jsonRoutes() as $name => $url) {
            $this->getJson($url)->assertOk();
        }
    }
}
