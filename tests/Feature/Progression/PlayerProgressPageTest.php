<?php

namespace Tests\Feature\Progression;

use App\Models\Progression;
use App\Models\User;

/** Spec 019 item 7: the player's progress page and its public/private switch (Q4). */
class PlayerProgressPageTest extends ProgressionTestCase
{
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        config(['progression.viewer' => 'api']);
        $this->owner = $this->player(['name' => 'Owner']);
    }

    public function test_the_owner_sees_the_switch_on_and_public_by_default(): void
    {
        $response = $this->actingAs($this->owner)->get('/progression/Owner');

        $response->assertOk();
        $this->assertSame(
            ['url' => url("/api/progressions/{$this->owner->active_progression_id}/visibility"), 'public' => true],
            $response->viewData('privacy'),
        );
        $response->assertSee('data-privacy-switch', false);
    }

    public function test_the_switch_shows_a_private_progression_as_off(): void
    {
        Progression::whereKey($this->owner->active_progression_id)->update(['is_public' => false]);

        $response = $this->actingAs($this->owner)->get('/progression/Owner');

        $this->assertFalse($response->viewData('privacy')['public']);
    }

    public function test_an_admin_sees_the_switch_of_any_player(): void
    {
        $response = $this->actingAs($this->admin())->get('/progression/Owner');

        $this->assertNotNull($response->viewData('privacy'));
    }

    public function test_other_players_and_guests_do_not_see_the_switch(): void
    {
        $other = $this->player(['name' => 'Other']);

        $this->actingAs($other)->get('/progression/Owner')->assertOk()->assertDontSee('data-privacy-switch', false);
        auth()->logout();
        $this->get('/progression/Owner')->assertOk()->assertDontSee('data-privacy-switch', false);
    }

    public function test_the_page_carries_the_next_milestones_strings(): void
    {
        $response = $this->get('/progression/Owner');

        $this->assertSame(__('progression.next_title'), $response->viewData('config')['messages']['next_title']);
    }

    public function test_the_owner_switches_the_progression_to_private(): void
    {
        $this->actingAs($this->owner)
            ->putJson("/api/progressions/{$this->owner->active_progression_id}/visibility", ['public' => false])
            ->assertOk()
            ->assertJson(['public' => false]);

        $this->assertFalse(Progression::find($this->owner->active_progression_id)->is_public);
    }
}
