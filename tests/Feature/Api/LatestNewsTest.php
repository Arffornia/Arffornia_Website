<?php

namespace Tests\Feature\Api;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * GET /api/news/latest feeds the News tile of the in-game title screen (spec 007).
 */
class LatestNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_newest_headline_with_its_page_url(): void
    {
        News::factory()->create(['title' => 'Old', 'created_at' => Carbon::parse('2026-10-01 10:00')]);
        $newest = News::factory()->create(['title' => 'Season 2 opens', 'created_at' => Carbon::parse('2026-11-20 18:30')]);

        $this->getJson('/api/news/latest')
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=300, public')
            ->assertExactJson(['news' => [[
                'id' => $newest->id,
                'title' => 'Season 2 opens',
                'date' => '2026-11-20',
                'url' => url("/news/{$newest->id}"),
            ]]]);
    }

    public function test_size_returns_several_headlines_newest_first(): void
    {
        foreach (['2026-11-01', '2026-11-03', '2026-11-02'] as $day) {
            News::factory()->create(['title' => $day, 'created_at' => Carbon::parse($day)]);
        }

        $this->getJson('/api/news/latest?size=2')
            ->assertOk()
            ->assertJsonCount(2, 'news')
            ->assertJsonPath('news.0.title', '2026-11-03')
            ->assertJsonPath('news.1.title', '2026-11-02');
    }

    public function test_returns_an_empty_list_when_there_is_no_news(): void
    {
        $this->getJson('/api/news/latest')
            ->assertOk()
            ->assertExactJson(['news' => []]);
    }

    public function test_rejects_a_size_outside_one_to_five(): void
    {
        $this->getJson('/api/news/latest?size=6')->assertUnprocessable();
        $this->getJson('/api/news/latest?size=0')->assertUnprocessable();
    }
}
