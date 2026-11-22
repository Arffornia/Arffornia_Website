<?php

namespace Tests\Feature\Progression;

use App\Models\Progression;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Spec 019 item 10: the admin and player statistics pages show the numbers of a known seed.
 *
 * Seed: stage 1 has a..e (threshold 80 %), stage 2 has f. Fast does a..d on 12 Nov, which opens stage 2;
 * Slow does a; Idle does nothing. Fast played 1 h on 10 Nov and 30 min on 12 Nov.
 */
class ProgressionStatsPagesTest extends ProgressionTestCase
{
    private User $fast;

    protected function setUp(): void
    {
        parent::setUp();
        config(['progression.viewer' => 'api']);
        Carbon::setTestNow('2026-11-10 09:00:00');
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            $this->milestone(['name' => $name]);
        }
        $this->milestone(['name' => 'f', 'stage_id' => $this->stage(2)->id]);
        $this->publish()->assertCreated();

        $this->fast = $this->player(['name' => 'Fast']);
        $slow = $this->player(['name' => 'Slow']);
        $this->player(['name' => 'Idle']);

        Carbon::setTestNow('2026-11-12 18:00:00');
        $this->sendEvents($this->fast->active_progression_id, array_map(
            fn (string $key, int $i) => [...$this->completed("evt-fast-{$i}", "arffornia/{$key}"), 'actor' => $this->fast->uuid],
            ['a', 'b', 'c', 'd'], [1, 2, 3, 4],
        ))->assertOk();
        $this->sendEvents($slow->active_progression_id, [$this->completed('evt-slow-1', 'arffornia/a')])->assertOk();
        DB::table('player_playtime')->insert([
            ['user_id' => $this->fast->id, 'day' => '2026-11-10', 'seconds' => 3600],
            ['user_id' => $this->fast->id, 'day' => '2026-11-12', 'seconds' => 1800],
        ]);
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_page_shows_the_funnel_the_stalls_activity_and_playtime(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/progression/stats');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertCell($html, 'data-stat="progressions"', '3');
        $this->assertCell($html, 'data-stat="active"', '2');
        $this->assertCell($html, 'data-stat="playtime-total"', '1 h 30');
        $this->assertRow($html, 'data-stage="1"', ['data-col="at"' => '2', 'data-col="reached"' => '3', 'data-col="reached-rate"' => '100%']);
        $this->assertRow($html, 'data-stage="2"', ['data-col="at"' => '1', 'data-col="reached"' => '1', 'data-col="reached-rate"' => '33%']);
        $this->assertRow($html, 'data-milestone="arffornia/e"', ['data-col="waiting"' => '2', 'data-col="completed"' => '0']);
        $this->assertRow($html, 'data-day="2026-11-12"', ['data-col="completed"' => '5']);
    }

    public function test_admin_page_is_for_admins_only(): void
    {
        $this->actingAs($this->fast)->get('/admin/progression/stats')->assertForbidden();
    }

    public function test_player_page_shows_progress_rank_time_per_stage_recent_milestones_and_team(): void
    {
        $response = $this->get('/progression/Fast/stats');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertCell($html, 'data-stat="overall"', '4');
        $this->assertStringContainsString('67%', $html);
        $this->assertCell($html, 'data-stat="open-stage"', '2');
        $this->assertCell($html, 'data-stat="rank"', '#1');
        $this->assertStringContainsString('among 2 progressions', $html);
        $this->assertCell($html, 'data-stat="playtime"', '1 h 30');
        $this->assertRow($html, 'data-stage="1"', ['data-col="progress"' => '4 / 5', 'data-col="playtime"' => '1 h']);
        $this->assertRow($html, 'data-stage="2"', ['data-col="progress"' => '0 / 1', 'data-col="playtime"' => '30 min']);
        $this->assertRow($html, 'data-milestone="arffornia/d"', []);
        $this->assertRow($html, 'data-day="2026-11-10"', []);
        $response->assertSeeInOrder(['Fast', '4'], false);
    }

    public function test_player_stats_route_returns_the_same_numbers(): void
    {
        $this->getJson("/api/progressions/{$this->fast->active_progression_id}/stats")
            ->assertOk()
            ->assertJsonPath('done', 4)
            ->assertJsonPath('rank', 1)
            ->assertJsonPath('stages.0.playtime_seconds', 3600)
            ->assertJsonPath('stages.0.seconds', 2 * 86400 + 9 * 3600)
            ->assertJsonPath('members.0.completed', 4)
            ->assertJsonPath('stages.1.name', 'Stage 2')
            ->assertJsonPath('recent.0.key', 'arffornia/d')
            ->assertJsonCount(14, 'playtime_days')
            ->assertJsonPath('playtime_days.13', ['day' => '2026-11-12', 'seconds' => 1800]);
    }

    public function test_a_private_progression_hides_its_stats(): void
    {
        Progression::whereKey($this->fast->active_progression_id)->update(['is_public' => false]);

        $this->get('/progression/Fast/stats')->assertNotFound();
        $this->getJson("/api/progressions/{$this->fast->active_progression_id}/stats")->assertNotFound();
        $this->actingAs($this->fast)->get('/progression/Fast/stats')->assertOk();
    }

    private function assertCell(string $html, string $attribute, string $expected): void
    {
        $this->assertMatchesRegularExpression('#' . preg_quote($attribute, '#') . '[^>]*>\s*' . preg_quote($expected, '#') . '\s*<#', $html);
    }

    /** @param array<string, string> $cells */
    private function assertRow(string $html, string $rowAttribute, array $cells): void
    {
        $this->assertSame(1, preg_match('#<li\b[^>]*' . preg_quote($rowAttribute, '#') . '[^>]*>(.*?)</li>#s', $html, $row), "row {$rowAttribute}");
        foreach ($cells as $attribute => $expected) {
            $this->assertCell($row[1], $attribute, $expected);
        }
    }
}
