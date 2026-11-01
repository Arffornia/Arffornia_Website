<?php

namespace Tests\Feature\Api;

use App\Models\ServiceAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * POST /api/auth/token/svc, used by the mod and the proxy to get a bearer token.
 */
class AuthTokenContractTest extends ApiTestCase
{
    private string $clientId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientId = (string) Str::uuid();
        ServiceAccount::create([
            'name' => 'minecraft',
            'client_id' => $this->clientId,
            'client_secret' => Hash::make('good-secret'),
            'roles' => 'progression_editor,user_editor,team_editor',
        ]);
    }

    public function test_valid_credentials_return_a_working_token(): void
    {
        $token = $this->postJson('/api/auth/token/svc', ['client_id' => $this->clientId, 'client_secret' => 'good-secret'])
            ->assertOk()
            ->assertJsonStructure(['token'])
            ->json('token');

        $this->assertIsString($token);

        // The token carries the account roles: it can call a progression_editor route
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/migration/items-to-migrate')->assertOk();
    }

    public function test_wrong_secret_is_401(): void
    {
        $this->postJson('/api/auth/token/svc', ['client_id' => $this->clientId, 'client_secret' => 'bad'])
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_unknown_client_is_401(): void
    {
        $this->postJson('/api/auth/token/svc', ['client_id' => (string) Str::uuid(), 'client_secret' => 'good-secret'])
            ->assertUnauthorized();
    }

    public function test_missing_fields_are_422(): void
    {
        $this->postJson('/api/auth/token/svc', [])->assertUnprocessable()->assertJsonValidationErrors(['client_id', 'client_secret']);
    }

    public function test_session_token_requires_a_logged_in_user(): void
    {
        $this->postJson('/api/auth/token/session')->assertUnauthorized();

        $player = $this->player();
        $this->actingAs($player)->postJson('/api/auth/token/session')->assertOk()->assertJsonStructure(['token']);
    }
}
