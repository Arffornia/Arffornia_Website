<?php

namespace Tests\Feature;

use App\Models\PendingReward;
use App\Models\ShopItem;
use App\Models\Stage;
use App\Models\User;
use App\Models\UserSale;
use App\Services\ShopItemsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Stage::factory()->create();
    }

    private function makeItem(array $attributes = []): ShopItem
    {
        return ShopItem::create(array_merge([
            'name' => 'Diamond pack',
            'description' => 'Some diamonds',
            'img_url' => 'diamond.png',
            'category_id' => 1,
            'real_price' => 100,
            'promo_price' => 0,
            'is_unique' => false,
            'commands' => ['give {player} minecraft:diamond 1'],
        ], $attributes));
    }

    private function makeUser(float $money): User
    {
        return User::factory()->create(['money' => $money]);
    }

    public function test_successful_purchase_debits_and_records_the_sale_and_reward(): void
    {
        $user = $this->makeUser(150);
        $item = $this->makeItem();

        $response = $this->actingAs($user)->postJson("/shop/buy/{$item->id}");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(50, $user->fresh()->money);
        $this->assertSame(1, UserSale::where('user_id', $user->id)->where('shop_item_id', $item->id)->count());
        $reward = PendingReward::where('user_id', $user->id)->sole();
        $this->assertSame('pending', $reward->status);
        $this->assertSame($item->commands, $reward->commands);
    }

    public function test_promo_price_is_used_when_set(): void
    {
        $user = $this->makeUser(100);
        $item = $this->makeItem(['promo_price' => 30]);

        $this->actingAs($user)->postJson("/shop/buy/{$item->id}")->assertJson(['success' => true]);

        $this->assertEquals(70, $user->fresh()->money);
    }

    public function test_insufficient_funds_is_rejected_without_side_effects(): void
    {
        $user = $this->makeUser(99);
        $item = $this->makeItem();

        $this->actingAs($user)->postJson("/shop/buy/{$item->id}")
            ->assertOk()
            ->assertJson(['success' => false, 'message' => 'You do not have enough money.']);

        $this->assertEquals(99, $user->fresh()->money);
        $this->assertSame(0, UserSale::count());
        $this->assertSame(0, PendingReward::count());
    }

    public function test_unique_item_cannot_be_bought_twice(): void
    {
        $user = $this->makeUser(1000);
        $item = $this->makeItem(['is_unique' => true]);

        $this->actingAs($user)->postJson("/shop/buy/{$item->id}")->assertJson(['success' => true]);
        $this->actingAs($user)->postJson("/shop/buy/{$item->id}")
            ->assertJson(['success' => false, 'message' => 'You already own this item.']);

        $this->assertEquals(900, $user->fresh()->money);
        $this->assertSame(1, UserSale::count());
    }

    public function test_item_without_commands_is_rejected(): void
    {
        $user = $this->makeUser(1000);
        $item = $this->makeItem(['commands' => null]);

        $this->actingAs($user)->postJson("/shop/buy/{$item->id}")
            ->assertJson(['success' => false, 'message' => 'This item is not configured for delivery.']);

        $this->assertEquals(1000, $user->fresh()->money);
    }

    public function test_balance_is_read_from_the_database_not_from_a_stale_model(): void
    {
        $user = $this->makeUser(150);
        $item = $this->makeItem();
        $service = app(ShopItemsService::class);

        // Two copies of the same user loaded before any purchase, as two concurrent requests would have.
        $first = User::find($user->id);
        $second = User::find($user->id);

        $this->assertTrue($service->purchaseItem($first, $item)['success']);
        $result = $service->purchaseItem($second, $item);

        $this->assertFalse($result['success']);
        $this->assertSame('You do not have enough money.', $result['message']);
        $this->assertEquals(50, $user->fresh()->money);
        $this->assertSame(1, UserSale::count());
        $this->assertGreaterThanOrEqual(0, $user->fresh()->money);
    }

    public function test_purchase_requires_authentication(): void
    {
        $item = $this->makeItem();

        $this->postJson("/shop/buy/{$item->id}")->assertUnauthorized();
        $this->assertSame(0, UserSale::count());
    }
}
