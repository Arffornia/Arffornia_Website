<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private function sessionSecureFor(array $env): mixed
    {
        $keys = ['APP_ENV', 'SESSION_SECURE_COOKIE'];
        $saved = [];
        foreach ($keys as $key) {
            $saved[$key] = getenv($key);
            $value = $env[$key] ?? null;
            $value === null ? putenv($key) : putenv("$key=$value");
            unset($_ENV[$key], $_SERVER[$key]);
            if ($value !== null) {
                $_ENV[$key] = $_SERVER[$key] = $value;
            }
        }

        try {
            \Illuminate\Support\Env::enablePutenv();
            $config = require base_path('config/session.php');

            return $config['secure'];
        } finally {
            foreach ($saved as $key => $value) {
                $value === false ? putenv($key) : putenv("$key=$value");
                unset($_ENV[$key], $_SERVER[$key]);
                if ($value !== false) {
                    $_ENV[$key] = $_SERVER[$key] = $value;
                }
            }
        }
    }

    public function test_session_cookie_is_secure_in_production_by_default(): void
    {
        $this->assertTrue($this->sessionSecureFor(['APP_ENV' => 'production']));
    }

    public function test_session_cookie_is_not_forced_secure_outside_production(): void
    {
        $this->assertFalse($this->sessionSecureFor(['APP_ENV' => 'local']));
    }

    public function test_session_secure_cookie_env_overrides_the_default(): void
    {
        $this->assertFalse($this->sessionSecureFor(['APP_ENV' => 'production', 'SESSION_SECURE_COOKIE' => 'false']));
        $this->assertTrue($this->sessionSecureFor(['APP_ENV' => 'local', 'SESSION_SECURE_COOKIE' => 'true']));
    }

    private function nginxTemplate(): string
    {
        return file_get_contents(base_path('.docker/nginx/nginx_template_local.conf'));
    }

    /** @return array{0: string, 1: string} the template before and from the first `location` directive */
    private function splitAtFirstLocation(): array
    {
        // Ignore comment lines, then split at the first line that starts a location block.
        $lines = array_filter(
            explode("\n", $this->nginxTemplate()),
            fn (string $line) => ! str_starts_with(ltrim($line), '#')
        );
        $parts = preg_split('/^\s*location\s/m', implode("\n", $lines), 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /** Headers declared at server level (before the first location block). */
    private function serverLevelHeaders(): array
    {
        $serverLevel = $this->splitAtFirstLocation()[0];
        preg_match_all('/^\s*add_header\s+(\S+)\s+"([^"]*)"\s+always;/m', $serverLevel, $matches, PREG_SET_ORDER);

        $headers = [];
        foreach ($matches as $match) {
            $headers[strtolower($match[1])] = $match[2];
        }

        return $headers;
    }

    public function test_nginx_template_sends_the_security_headers(): void
    {
        $headers = $this->serverLevelHeaders();

        $this->assertSame('SAMEORIGIN', $headers['x-frame-options'] ?? null);
        $this->assertSame('nosniff', $headers['x-content-type-options'] ?? null);
        $this->assertSame('strict-origin-when-cross-origin', $headers['referrer-policy'] ?? null);
        $this->assertArrayHasKey('permissions-policy', $headers);
        $this->assertMatchesRegularExpression('/^max-age=\d{7,}/', $headers['strict-transport-security'] ?? '');
        $this->assertArrayNotHasKey('x-xss-protection', $headers, 'X-XSS-Protection is obsolete');
    }

    public function test_nginx_template_has_a_report_only_csp(): void
    {
        $csp = $this->serverLevelHeaders()['content-security-policy-report-only'] ?? '';

        foreach (["default-src 'self'", "object-src 'none'", "base-uri 'self'", "frame-ancestors 'self'"] as $directive) {
            $this->assertStringContainsString($directive, $csp);
        }
    }

    public function test_no_location_block_overrides_the_server_headers(): void
    {
        // nginx drops all inherited add_header directives in a block that declares its own.
        $locations = $this->splitAtFirstLocation()[1];

        $this->assertStringNotContainsString('add_header', $locations);
    }

    public function test_nginx_template_braces_are_balanced(): void
    {
        $template = $this->nginxTemplate();

        $this->assertSame(substr_count($template, '{'), substr_count($template, '}'));
    }
}
