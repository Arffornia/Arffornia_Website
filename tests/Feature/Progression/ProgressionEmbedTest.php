<?php

namespace Tests\Feature\Progression;

use App\Models\Progression;
use App\Models\User;
use App\Progression\EmbedTokens;
use Illuminate\Testing\TestResponse;

/** Spec 018 D2 a: the in-game viewer page, its read-only player token and targeting by key. */
class ProgressionEmbedTest extends ProgressionTestCase
{
    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        config(['progression.viewer' => 'api']);
        $a = $this->milestone(['name' => 'a']);
        $this->link($a, $this->milestone(['name' => 'b']));
        $this->milestone(['name' => 'f', 'stage_id' => $this->stage(2)->id]);
        $this->publish()->assertCreated();
        $this->player = $this->player(['name' => 'Alex']);
    }

    private function mint(): string
    {
        return $this->asService('progression_editor')
            ->postJson('/api/progression/embed-tokens', ['player_uuid' => $this->player->uuid])
            ->assertOk()
            ->json('token');
    }

    private function stateWith(?string $token): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $token === null ? $this->getJson('/api/embed/progression/state') : $this->withToken($token)->getJson('/api/embed/progression/state');
    }

    private function target(?string $key): TestResponse
    {
        return $this->asService('progression_editor')
            ->postJson('/api/progression/set-target', ['player_uuid' => $this->player->uuid, 'milestone_key' => $key]);
    }

    private function currentTarget(): ?int
    {
        return Progression::find($this->player->active_progression_id)->current_milestone_id;
    }

    public function test_the_game_server_mints_a_short_lived_token_and_the_page_url(): void
    {
        $response = $this->asService('progression_editor')->postJson('/api/progression/embed-tokens', ['player_uuid' => $this->player->uuid]);

        $response->assertOk()->assertJsonStructure(['token', 'expires_at', 'url'])->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(rtrim(config('app.url'), '/').'/embed/progression', $response->json('url'));
        $this->assertEqualsWithDelta(now()->getTimestamp() + 900, $response->json('expires_at'), 5);
    }

    public function test_the_page_url_uses_the_public_site_address_when_the_game_server_calls_an_internal_host(): void
    {
        config(['app.url' => 'https://staging.arffornia.com']);

        $response = $this->asService('progression_editor')->postJson(
            'http://arffornia-website-chart.arffornia-staging.svc.cluster.local/api/progression/embed-tokens',
            ['player_uuid' => $this->player->uuid],
        );

        $this->assertSame('https://staging.arffornia.com/embed/progression', $response->assertOk()->json('url'));
    }

    public function test_minting_a_token_for_an_unknown_or_malformed_player_fails(): void
    {
        $this->asService('progression_editor')->postJson('/api/progression/embed-tokens', ['player_uuid' => str_repeat('a', 32)])->assertNotFound();
        $this->asService('progression_editor')->postJson('/api/progression/embed-tokens', ['player_uuid' => '../../etc'])->assertUnprocessable();
    }

    public function test_the_token_reads_the_players_own_state_even_when_it_is_private(): void
    {
        Progression::whereKey($this->player->active_progression_id)->update(['is_public' => false]);

        $this->stateWith($this->mint())
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('progression_id', $this->player->active_progression_id)
            ->assertJsonPath('player.name', 'Alex')
            ->assertJsonPath('next', ['arffornia/a']);
    }

    public function test_state_without_a_valid_token_is_refused(): void
    {
        $token = $this->mint();
        [$prefix, $payload, $signature] = explode('.', $token);
        $forged = rtrim(strtr(base64_encode(json_encode(['sub' => $this->admin()->id, 'aud' => 'progression-embed', 'exp' => now()->addHour()->getTimestamp()])), '+/', '-_'), '=');

        foreach ([null, 'garbage', "{$prefix}.{$forged}.{$signature}", "{$prefix}.{$payload}.x{$signature}", $this->serviceToken('progression_editor')] as $bad) {
            $this->stateWith($bad)->assertUnauthorized();
        }
    }

    public function test_an_expired_token_is_refused(): void
    {
        $token = app(EmbedTokens::class)->issue($this->player)['token'];

        $this->travel(16)->minutes();

        $this->stateWith($token)->assertUnauthorized();
    }

    public function test_the_page_has_no_site_chrome_and_never_carries_a_token(): void
    {
        $response = $this->get('/embed/progression?locale=fr');

        $response->assertOk();
        $response->assertSee('api\\/embed\\/progression\\/state', false);
        $response->assertSee(__('progression.embed.target_set', [], 'fr'));
        $response->assertDontSee('headerContainer', false);
        $response->assertDontSee('pe1.', false);
    }

    public function test_the_page_is_off_with_the_viewer(): void
    {
        config(['progression.viewer' => 'off']);

        $this->get('/embed/progression')->assertNotFound();
    }

    public function test_an_available_milestone_can_be_targeted_by_key(): void
    {
        $this->target('arffornia/a')->assertOk()->assertJsonPath('target', 'arffornia/a');

        $this->stateWith($this->mint())->assertJsonPath('target', 'arffornia/a');
    }

    public function test_a_milestone_that_is_not_available_cannot_be_targeted(): void
    {
        $this->target('arffornia/b')->assertConflict()->assertJsonPath('reason', 'milestone_unavailable');
        $this->target('arffornia/f')->assertConflict()->assertJsonPath('reason', 'milestone_unavailable');
        $this->target('arffornia/nope')->assertConflict()->assertJsonPath('reason', 'unknown_milestone');

        $this->assertNull($this->currentTarget());
    }

    public function test_a_done_milestone_cannot_be_targeted(): void
    {
        $this->sendEvents($this->player->active_progression_id, [$this->completed('evt-00000001', 'arffornia/a')])->assertOk();

        $this->target('arffornia/a')->assertConflict()->assertJsonPath('reason', 'milestone_unavailable');
        $this->target('arffornia/b')->assertOk();
    }

    public function test_a_target_is_cleared_with_a_null_id_as_before(): void
    {
        $this->target('arffornia/a')->assertOk();

        $this->asService('progression_editor')
            ->postJson('/api/progression/set-target', ['player_uuid' => $this->player->uuid, 'milestone_id' => null])
            ->assertOk();

        $this->assertNull($this->currentTarget());
    }
}
