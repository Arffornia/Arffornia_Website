<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_keeps_formatting_and_utf8_text(): void
    {
        $html = '<h1>Title</h1><p>Été <strong>bold</strong> <em>it</em></p><ul><li>one</li></ul>';

        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function test_empty_input(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean('   '));
    }

    public function test_keeps_safe_links_and_images(): void
    {
        $clean = HtmlSanitizer::clean('<a href="https://arffornia.com/shop" title="Shop">shop</a><img src="/images/a.png" alt="A">');

        $this->assertStringContainsString('href="https://arffornia.com/shop"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $clean);
        $this->assertStringContainsString('<img src="/images/a.png" alt="A">', $clean);
    }

    public static function unsafeInputs(): array
    {
        return [
            'script tag' => ['<p>a</p><script>alert(1)</script>'],
            'event handler' => ['<img src="x" onerror="alert(1)">'],
            'javascript url' => ['<a href="javascript:alert(1)">x</a>'],
            'obfuscated javascript url' => ["<a href=\"java\tscript:alert(1)\">x</a>"],
            'entity encoded javascript url' => ['<a href="&#106;avascript:alert(1)">x</a>'],
            'data url' => ['<img src="data:text/html;base64,PHNjcmlwdD4=">'],
            'iframe' => ['<iframe src="https://example.com"></iframe>'],
            'svg' => ['<svg onload="alert(1)"><script>alert(1)</script></svg>'],
            'style attribute' => ['<p style="background:url(javascript:alert(1))">x</p>'],
            'closing the wrapper' => ['</div><script>alert(1)</script>'],
            'noscript breakout' => ['<noscript><p title="</noscript><img src=x onerror=alert(1)>"></noscript>'],
            'unknown tag with handler' => ['<details open ontoggle="alert(1)">x</details>'],
            'comment' => ['<!--<script>alert(1)</script>-->'],
        ];
    }

    #[DataProvider('unsafeInputs')]
    public function test_removes_unsafe_markup(string $html): void
    {
        $clean = strtolower(HtmlSanitizer::clean($html));

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('data:', $clean);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/', $clean);
        $this->assertStringNotContainsString('style=', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringNotContainsString('<svg', $clean);
    }

    public function test_unknown_tags_are_unwrapped_and_their_text_kept(): void
    {
        $this->assertSame('<p>keep me</p>', HtmlSanitizer::clean('<p><font color="red">keep me</font></p>'));
    }
}
