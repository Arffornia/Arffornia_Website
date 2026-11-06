<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartingMoneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Stage::factory()->create();
    }

    public function test_new_players_start_with_500_by_default(): void
    {
        $user = app(UserRepository::class)->createUser('Steve', '069a79f444e94726a5befca90e38aaf5');

        $this->assertSame(500, (int) $user->fresh()->money);
    }

    public function test_starting_money_is_configurable(): void
    {
        config(['shop.starting_money' => 250]);

        $user = app(UserRepository::class)->createUser('Alex', '853c80ef3c3749fdaa49938b674adae6');

        $this->assertSame(250, (int) $user->fresh()->money);
    }
}
