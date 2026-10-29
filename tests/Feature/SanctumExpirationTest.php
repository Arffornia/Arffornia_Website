<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SanctumExpirationTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('auth:sanctum')->get('/_test/whoami', fn () => response()->json(['ok' => true]));

        Stage::factory()->create();
        $this->token = User::factory()->create()->createToken('test')->plainTextToken;
    }

    private function whoami()
    {
        // Fresh guard state for every request
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->token)->getJson('/_test/whoami');
    }

    public function test_tokens_do_not_expire_when_expiration_is_unset(): void
    {
        config(['sanctum.expiration' => null]);

        $this->travel(365)->days();

        $this->whoami()->assertOk();
    }

    public function test_tokens_expire_after_the_configured_minutes(): void
    {
        config(['sanctum.expiration' => 60]);

        $this->travel(59)->minutes();
        $this->whoami()->assertOk();

        $this->travel(2)->minutes();
        $this->whoami()->assertUnauthorized();
    }

    public function test_expired_tokens_are_pruned_daily(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command, 'sanctum:prune-expired'));

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', $events->first()->expression);
    }
}
