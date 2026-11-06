<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/ip', fn (Request $request) => response()->json([
            'ip' => $request->ip(),
            'secure' => $request->secure(),
        ]));
    }

    private function requestFrom(string $remoteAddr, array $headers = [])
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $remoteAddr])
            ->withHeaders($headers)
            ->getJson('/_test/ip');
    }

    public function test_forwarded_headers_are_ignored_by_default(): void
    {
        config(['trustedproxy.proxies' => null]);

        $this->requestFrom('10.42.0.7', ['X-Forwarded-For' => '203.0.113.9'])
            ->assertJson(['ip' => '10.42.0.7']);
    }

    public function test_forwarded_for_is_used_when_the_caller_is_a_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => '10.42.0.0/16, 10.43.0.0/16']);

        $this->requestFrom('10.42.0.7', ['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https'])
            ->assertJson(['ip' => '203.0.113.9', 'secure' => true]);
    }

    public function test_forwarded_for_is_ignored_when_the_caller_is_not_trusted(): void
    {
        config(['trustedproxy.proxies' => '10.42.0.0/16']);

        $this->requestFrom('198.51.100.20', ['X-Forwarded-For' => '203.0.113.9'])
            ->assertJson(['ip' => '198.51.100.20']);
    }
}
