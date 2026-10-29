<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Stage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutputEscapingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Stage::factory()->create();
    }

    private function news(string $content): News
    {
        $news = new News();
        $news->title = 'Title';
        $news->content = $content;
        $news->imgUrl = 'images/a.png';
        $news->save();

        return $news;
    }

    public function test_news_page_keeps_formatting(): void
    {
        $news = $this->news('<h1>Patch notes</h1><p>New <strong>mods</strong></p>');

        $this->get('/news/' . $news->id)
            ->assertOk()
            ->assertSee('<h1>Patch notes</h1><p>New <strong>mods</strong></p>', false);
    }

    public function test_news_page_strips_active_content(): void
    {
        $news = $this->news('<p>Hello</p><script>window.pwned=1</script><img src="x" onerror="window.pwned=1"><a href="javascript:window.pwned=1">x</a>');

        $response = $this->get('/news/' . $news->id)->assertOk();

        $response->assertSee('<p>Hello</p>', false);
        $response->assertDontSee('window.pwned', false);
        $response->assertDontSee('onerror', false);
    }

    public function test_flash_message_is_encoded_as_a_javascript_string(): void
    {
        $message = 'Success to upload: `${window.pwned=1}`</script><script>window.pwned=1</script>.png !';

        $response = $this->withSession(['message' => $message])->get('/')->assertOk();

        $response->assertSee('const message = ' . json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';', false);
        $response->assertDontSee('</script><script>window.pwned', false);
        // Backticks and ${ are plain characters inside a double-quoted string, not a template literal.
        $response->assertDontSee('const message = `', false);
    }

    public function test_error_flash_message_uses_the_error_type(): void
    {
        $this->withSession(['error' => 'Authentication failed.'])->get('/')
            ->assertOk()
            ->assertSee('const message = "Authentication failed.";', false)
            ->assertSee('const type = "error";', false);
    }
}
