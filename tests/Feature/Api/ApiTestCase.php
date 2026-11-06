<?php

namespace Tests\Feature\Api;

use App\Models\Milestone;
use App\Models\Progression;
use App\Models\ServiceAccount;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Base class for API contract tests.
 *
 * These tests pin the status codes and JSON shapes that the NeoForge mod (Arffornia_Mods
 * ArfforniaApiService / ArfforniaApiDtos), the Velocity proxy (Arffornia_Proxy ApiService) and the
 * launcher (Arffornia_Launcher_V.5.2 arfforniaApi.js) rely on. Changing them breaks those clients.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Stage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stage = Stage::factory()->create();
    }

    /** A player with its own solo progression, as created on first login. */
    protected function player(array $attributes = []): User
    {
        $progression = Progression::create(['max_stage_id' => $this->stage->id, 'completed_milestones' => []]);

        return User::factory()->create(array_merge([
            'uuid' => str_replace('-', '', (string) Str::uuid()),
            'solo_progression_id' => $progression->id,
            'active_progression_id' => $progression->id,
        ], $attributes));
    }

    protected function milestone(array $attributes = []): Milestone
    {
        return Milestone::create(array_merge([
            'name' => 'Basic machines',
            'description' => 'First steps',
            'stage_id' => $this->stage->id,
            'reward_progress_points' => 10,
            'icon_type' => 'tech',
            'x' => 1,
            'y' => 2,
        ], $attributes));
    }

    /** Bearer token for a service account with the given comma-separated roles. */
    protected function serviceToken(string $roles): string
    {
        $account = ServiceAccount::create([
            'name' => 'svc-' . Str::random(6),
            'client_id' => (string) Str::uuid(),
            'client_secret' => Hash::make('secret'),
            'roles' => $roles,
        ]);

        return $account->createToken($account->name, $account->getRoles())->plainTextToken;
    }

    /** Send a JSON request with a bearer token, resetting the auth guards between calls. */
    protected function asService(string $roles): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->serviceToken($roles));
    }
}
