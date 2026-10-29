<?php

namespace Tests\Feature;

use App\Models\ShopItem;
use App\Models\Stage;
use App\Models\User;
use App\Models\UserSale;
use App\Services\ShopItemsService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Runs two purchases in parallel processes against a real database.
 * Only meaningful on a server database with row locks (Postgres): skipped on sqlite.
 *
 *   DB_CONNECTION=pgsql DB_HOST=... DB_DATABASE=... vendor/bin/phpunit --filter ShopPurchaseConcurrencyTest
 */
class ShopPurchaseConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Needs Postgres (DB_CONNECTION=pgsql).');
        }
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Needs the pcntl extension.');
        }
    }

    public function test_concurrent_purchases_cannot_overspend(): void
    {
        Stage::factory()->create();
        $user = User::factory()->create(['money' => 150]);
        $item = ShopItem::create([
            'name' => 'Diamond pack',
            'description' => 'Some diamonds',
            'img_url' => 'diamond.png',
            'category_id' => 1,
            'real_price' => 100,
            'promo_price' => 0,
            'is_unique' => false,
            'commands' => ['give {player} minecraft:diamond 1'],
        ]);

        // Close the parent's connection so children open their own.
        DB::disconnect();

        $children = [];
        for ($i = 0; $i < 4; $i++) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                DB::reconnect();
                $buyer = User::find($user->id);
                app(ShopItemsService::class)->purchaseItem($buyer, ShopItem::find($item->id));
                DB::disconnect();
                // Leave the child without running PHPUnit shutdown handlers.
                posix_kill(getmypid(), SIGKILL);
            }
            $children[] = $pid;
        }
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
        }

        DB::reconnect();
        $this->assertEquals(50, $user->fresh()->money);
        $this->assertSame(1, UserSale::where('user_id', $user->id)->count());
    }
}
