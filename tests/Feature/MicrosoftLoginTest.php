<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use App\Services\UserService;
use Arffornia\MinecraftOauth\Exceptions\MinecraftOauthException;
use Arffornia\MinecraftOauth\MinecraftOauth;
use Arffornia\MinecraftOauth\MinecraftProfile;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MicrosoftLoginTest extends TestCase
{
    use RefreshDatabase;

    private const STATE_KEY = UserService::OAUTH_STATE_SESSION_KEY;

    protected function setUp(): void
    {
        parent::setUp();
        Stage::factory()->create();
        config([
            'app.azure.oauth.client.id' => 'client-id',
            'app.azure.oauth.client.secret' => 'client-secret',
            'app.azure.oauth.redirect_uri' => 'https://example.test/login/connect',
        ]);
        Http::preventStrayRequests();
    }

    private function fakeOauth(): Mockery\MockInterface
    {
        $mock = Mockery::mock(MinecraftOauth::class);
        $this->app->instance(MinecraftOauth::class, $mock);

        return $mock;
    }

    private function profile(): MinecraftProfile
    {
        return new MinecraftProfile([
            'id' => '069a79f4-44e9-4726-a5be-fca90e38aaf5',
            'name' => 'Notch',
            'skins' => [],
            'capes' => [],
        ]);
    }

    public function test_login_redirect_carries_a_random_state_stored_in_the_session(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://login.live.com/oauth20_authorize.srf?', $location);

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('client-id', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('https://example.test/login/connect', $query['redirect_uri']);
        $this->assertSame('XboxLive.signin offline_access', $query['scope']);
        $this->assertSame(40, strlen($query['state']));
        $this->assertSame($query['state'], session(self::STATE_KEY));
    }

    public function test_each_login_gets_a_new_state(): void
    {
        $this->get('/login');
        $first = session(self::STATE_KEY);
        $this->get('/login');

        $this->assertNotSame($first, session(self::STATE_KEY));
    }

    public function test_callback_with_valid_state_logs_the_player_in(): void
    {
        $this->fakeOauth()->shouldReceive('fetchProfileWithOAuthUI')
            ->once()
            ->withArgs(fn ($id, $secret, $code, $uri) => $id === 'client-id' && $code === 'the-code')
            ->andReturn($this->profile());

        $response = $this->withSession([self::STATE_KEY => 'expected-state'])
            ->get('/login/connect?code=the-code&state=expected-state');

        $response->assertRedirect('/')->assertSessionHas('message', 'Welcome Notch!');
        $this->assertAuthenticated();
        $this->assertSame('Notch', User::where('uuid', '069a79f444e94726a5befca90e38aaf5')->sole()->name);
        $this->assertNull(session(self::STATE_KEY), 'state must be single use');
    }

    public function test_callback_with_wrong_state_is_rejected(): void
    {
        $this->fakeOauth()->shouldNotReceive('fetchProfileWithOAuthUI');

        $this->withSession([self::STATE_KEY => 'expected-state'])
            ->get('/login/connect?code=the-code&state=other-state')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_callback_without_state_query_param_is_rejected(): void
    {
        $this->fakeOauth()->shouldNotReceive('fetchProfileWithOAuthUI');

        $this->withSession([self::STATE_KEY => 'expected-state'])
            ->get('/login/connect?code=the-code')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_callback_with_array_state_is_rejected(): void
    {
        $this->fakeOauth()->shouldNotReceive('fetchProfileWithOAuthUI');

        $this->withSession([self::STATE_KEY => 'expected-state'])
            ->get('/login/connect?code=the-code&state[]=expected-state')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_callback_without_state_in_session_is_rejected(): void
    {
        $this->fakeOauth()->shouldNotReceive('fetchProfileWithOAuthUI');

        $this->get('/login/connect?code=the-code&state=anything')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_callback_with_error_from_microsoft_redirects_with_a_message(): void
    {
        $this->fakeOauth()->shouldNotReceive('fetchProfileWithOAuthUI');

        $this->withSession([self::STATE_KEY => 'expected-state'])
            ->get('/login/connect?error=access_denied&error_description=cancelled&state=expected-state')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_callback_without_code_redirects_with_a_message(): void
    {
        $this->fakeOauth()->shouldNotReceive('fetchProfileWithOAuthUI');

        $this->withSession([self::STATE_KEY => 'expected-state'])
            ->get('/login/connect?state=expected-state')
            ->assertRedirect('/')
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public static function exchangeFailures(): array
    {
        return [
            'library error' => [fn () => new MinecraftOauthException('nope')],
            'network error' => [fn () => new ConnectException('timeout', new PsrRequest('POST', 'https://login.live.com/oauth20_token.srf'))],
        ];
    }

    #[DataProvider('exchangeFailures')]
    public function test_failed_code_exchange_redirects_with_a_message(\Closure $makeException): void
    {
        $this->fakeOauth()->shouldReceive('fetchProfileWithOAuthUI')
            ->andThrow($makeException());

        $this->withSession([self::STATE_KEY => 'expected-state'])
            ->get('/login/connect?code=the-code&state=expected-state')
            ->assertRedirect('/')
            ->assertSessionHas('error', 'Authentication failed. Please try again.');

        $this->assertGuest();
    }

    public function test_login_renames_a_returning_player_from_the_microsoft_profile(): void
    {
        User::factory()->create(['uuid' => '069a79f444e94726a5befca90e38aaf5', 'name' => 'OldName']);
        $this->fakeOauth()->shouldReceive('fetchProfileWithOAuthUI')->andReturn($this->profile());

        $this->withSession([self::STATE_KEY => 'expected-state'])
            ->get('/login/connect?code=the-code&state=expected-state');

        $this->assertSame('Notch', User::where('uuid', '069a79f444e94726a5befca90e38aaf5')->sole()->name);
    }
}
