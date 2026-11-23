<?php

namespace Tests\Feature\Progression;

use App\Models\Progression;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Spec 019: the link from the player page to the stats, and the team view (who did what, by `completed_by`).
 *
 * Seed: Alice and Bob share the progression of team "Builders". Alice did a and b, Bob did c, d came
 * without an actor. Eve plays solo on her own progression.
 */
class ProgressionTeamViewTest extends ProgressionTestCase
{
    private User $alice;
    private User $bob;
    private User $eve;
    private int $teamProgression;

    protected function setUp(): void
    {
        parent::setUp();
        config(['progression.viewer' => 'api']);
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $this->milestone(['name' => $name]);
        }
        $this->publish()->assertCreated();

        $this->alice = $this->player(['name' => 'Alice']);
        $this->teamProgression = $this->alice->active_progression_id;
        $team = Team::create(['id' => (string) Str::uuid(), 'name' => 'Builders', 'progression_id' => $this->teamProgression]);
        $this->alice->update(['team_id' => $team->id]);
        $this->bob = $this->player(['name' => 'Bob']);
        $this->bob->update(['team_id' => $team->id, 'active_progression_id' => $this->teamProgression]);
        $this->eve = $this->player(['name' => 'Eve']);

        $this->sendEvents($this->teamProgression, [
            [...$this->completed('evt-team-1', 'arffornia/a'), 'actor' => $this->alice->uuid],
            [...$this->completed('evt-team-2', 'arffornia/b'), 'actor' => $this->alice->uuid],
            [...$this->completed('evt-team-3', 'arffornia/c'), 'actor' => $this->bob->uuid],
            $this->completed('evt-team-4', 'arffornia/d'),
        ])->assertOk();
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
    }

    public function test_player_page_links_to_the_player_statistics(): void
    {
        $response = $this->get('/progression/Alice');

        $response->assertOk();
        $response->assertSee('href="' . route('progression.player.stats', ['playerName' => 'Alice']) . '"', false);
    }

    public function test_player_page_hides_the_statistics_link_of_a_private_progression_from_outsiders(): void
    {
        $this->makeTeamPrivate();

        $this->get('/progression/Alice')->assertOk()->assertDontSee('data-stats-link', false);
        $this->actingAs($this->eve)->get('/progression/Alice')->assertOk()->assertDontSee('data-stats-link', false);
    }

    public function test_player_page_keeps_the_statistics_link_of_a_private_progression_for_members(): void
    {
        $this->makeTeamPrivate();

        $this->actingAs($this->bob)->get('/progression/Alice')->assertOk()->assertSee('data-stats-link', false);
    }

    public function test_statistics_page_links_to_the_team_view(): void
    {
        $response = $this->get('/progression/Alice/stats');

        $response->assertOk();
        $response->assertSee('href="' . route('progression.player.team', ['playerName' => 'Alice']) . '"', false);
    }

    public function test_team_view_lists_who_did_what(): void
    {
        $response = $this->get('/progression/Bob/team');

        $response->assertOk();
        $this->assertMatchesRegularExpression('#data-stat="team"[^>]*>\s*Builders\s*<#', $response->getContent());
        $this->assertSame([
            'Alice' => ['arffornia/b', 'arffornia/a'],
            'Bob' => ['arffornia/c'],
            '' => ['arffornia/d'],
        ], $this->milestonesPerMember($response->getContent()));
    }

    public function test_team_view_of_a_solo_player_is_named_after_the_player(): void
    {
        $response = $this->get('/progression/Eve/team');

        $response->assertOk();
        $this->assertMatchesRegularExpression('#data-stat="team"[^>]*>\s*Eve\s*<#', $response->getContent());
        $this->assertSame(['Eve' => []], $this->milestonesPerMember($response->getContent()));
    }

    public function test_team_view_of_a_private_progression_is_not_found_for_guests_and_other_players(): void
    {
        $this->makeTeamPrivate();

        $this->get('/progression/Alice/team')->assertNotFound();
        $this->actingAs($this->eve)->get('/progression/Alice/team')->assertNotFound();
    }

    public function test_team_view_of_a_private_progression_is_shown_to_members_and_admins(): void
    {
        $this->makeTeamPrivate();

        $this->actingAs($this->bob)->get('/progression/Alice/team')->assertOk();
        $this->actingAs($this->admin())->get('/progression/Alice/team')->assertOk();
    }

    public function test_team_view_of_an_unknown_player_is_not_found(): void
    {
        $this->get('/progression/Nobody/team')->assertNotFound();
    }

    private function makeTeamPrivate(): void
    {
        Progression::whereKey($this->teamProgression)->update(['is_public' => false]);
    }

    /** @return array<string, list<string>> milestone keys per member card, in page order */
    private function milestonesPerMember(string $html): array
    {
        preg_match_all('#<section class="ps-card" data-member="([^"]*)">(.*?)</section>#s', $html, $cards, PREG_SET_ORDER);
        $result = [];
        foreach ($cards as [, $member, $body]) {
            preg_match_all('#data-milestone="([^"]+)"#', $body, $keys);
            $result[$member] = $keys[1];
        }

        return $result;
    }
}
