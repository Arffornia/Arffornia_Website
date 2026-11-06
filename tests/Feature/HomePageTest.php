<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public static function playerCounts(): array
    {
        return [
            'no players' => [0],
            'one player' => [1],
            'two players' => [2],
            'three players' => [3],
            'more than three players' => [5],
        ];
    }

    #[DataProvider('playerCounts')]
    public function test_home_page_renders_whatever_the_number_of_players(int $count): void
    {
        Stage::factory()->create();
        $players = User::factory($count)->create();

        $response = $this->get('/');

        $response->assertOk();

        // The podium shows at most the 3 best players, ranked by progress points
        $best = $players->sortByDesc('progress_point')->values()->take(3);
        foreach ($best as $index => $player) {
            $response->assertSee('#' . ($index + 1), false);
            $response->assertSee('data-username="' . e($player->name) . '"', false);
        }
        $this->assertSame(min($count, 3), substr_count($response->getContent(), 'class="skin_viewer"'));
    }
}
