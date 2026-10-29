<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Small limits keep the tests fast; the mechanism is the same as in production.
        config([
            'ratelimit.auth_token' => 3,
            'ratelimit.session_token' => 3,
            'ratelimit.shop_buy' => 2,
            'ratelimit.vote_verify' => 2,
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function user(): User
    {
        Stage::factory()->create();

        return User::factory()->create(['money' => 0]);
    }

    private function assertLimitedAfter(int $allowed, callable $request): void
    {
        for ($i = 0; $i < $allowed; $i++) {
            $this->assertNotSame(429, $request()->getStatusCode(), "request #" . ($i + 1) . " should not be limited");
        }

        $request()->assertStatus(429);
    }

    public function test_service_token_endpoint_is_limited_per_ip(): void
    {
        $this->assertLimitedAfter(3, fn () => $this->postJson('/api/auth/token/svc', [
            'client_id' => '00000000-0000-0000-0000-000000000000',
            'client_secret' => 'wrong',
        ]));
    }

    public function test_microsoft_token_endpoint_is_limited_per_ip(): void
    {
        // An empty body fails validation (422) before any outbound call.
        $this->assertLimitedAfter(3, fn () => $this->postJson('/api/auth/token/ms', []));
    }

    public function test_token_endpoints_share_the_per_ip_budget_but_not_across_ips(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/token/ms', [])->assertStatus(422);
        }
        $this->postJson('/api/auth/token/svc', [])->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->postJson('/api/auth/token/svc', [])
            ->assertStatus(422);
    }

    public function test_session_token_endpoint_is_limited_per_user(): void
    {
        Sanctum::actingAs($this->user(), ['user']);

        $this->assertLimitedAfter(3, fn () => $this->postJson('/api/auth/token/session'));
    }

    public function test_web_shop_buy_is_limited_per_user(): void
    {
        $user = $this->user();

        // Unknown item: 404, but the throttle still counts the attempt.
        $this->assertLimitedAfter(2, fn () => $this->actingAs($user)->postJson('/shop/buy/999'));
    }

    public function test_shop_buy_limit_is_per_user(): void
    {
        $first = $this->user();
        $second = User::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($first)->postJson('/shop/buy/999');
        }
        $this->actingAs($first)->postJson('/shop/buy/999')->assertStatus(429);
        $this->actingAs($second)->postJson('/shop/buy/999')->assertStatus(404);
    }

    public function test_vote_verify_is_limited_per_user(): void
    {
        $user = $this->user();

        // Unknown site: 422 from the service, no outbound call.
        $this->assertLimitedAfter(2, fn () => $this->actingAs($user)->postJson('/vote/verify', ['site' => 'unknown']));
    }

    public function test_limits_have_safe_defaults(): void
    {
        $config = require base_path('config/ratelimit.php');

        foreach (['auth_token', 'session_token', 'shop_buy', 'vote_verify'] as $key) {
            $this->assertIsInt($config[$key]);
            $this->assertGreaterThan(0, $config[$key]);
        }
        $this->assertNotNull(RateLimiter::limiter('auth-token'));
        $this->assertNotNull(RateLimiter::limiter('shop-buy'));
        $this->assertNotNull(RateLimiter::limiter('vote-verify'));
        $this->assertNotNull(RateLimiter::limiter('session-token'));
    }
}
