<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards `sitemap:generate`, the only consumer of spatie/laravel-sitemap (major bumps: 7 -> 8).
 * The command writes public/sitemap.xml, which is tracked in git, so the file is restored afterwards.
 */
class SitemapCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    private ?string $original = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = public_path('sitemap.xml');
        $this->original = is_file($this->path) ? file_get_contents($this->path) : null;
    }

    protected function tearDown(): void
    {
        if ($this->original === null) {
            @unlink($this->path);
        } else {
            file_put_contents($this->path, $this->original);
        }

        parent::tearDown();
    }

    public function test_it_writes_static_pages_and_news_entries(): void
    {
        $news = new News;
        $news->title = 'Sitemap news';
        $news->content = 'Body';
        $news->imgUrl = 'https://example.test/news.png';
        $news->save();

        $this->artisan('sitemap:generate')->assertSuccessful();

        $xml = simplexml_load_file($this->path);
        $this->assertNotFalse($xml, 'sitemap.xml is not valid XML');
        $this->assertSame('urlset', $xml->getName());

        $locations = [];
        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        foreach (['/reglement', '/stages', '/shop', '/news', "/news/{$news->id}"] as $path) {
            $this->assertContains(url($path), $locations);
        }
        $this->assertCount(6, $locations);
    }
}
