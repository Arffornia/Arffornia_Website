<?php

namespace App\Services;

use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserSale;
use App\Models\PendingReward;
use App\Repositories\ShopItemsRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class ShopItemsService
{
    private ShopItemsRepository $repository;

    public function __construct(ShopItemsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Fetches items intended for real money purchase.
     *
     * @return Collection<ShopItem>
     */
    public function getRealMoneyItems(): Collection
    {
        $items = $this->repository->getRealMoneyItems();

        foreach ($items as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $items;
    }

    /**
     * Get the top best-selling items.
     *
     * @param int $size
     * @return Collection<ShopItem>
     */
    public function getBestSellers(int $size): Collection
    {
        $size = min(25, max(0, $size));
        $bestSellers = $this->repository->getBestSellers($size);

        foreach ($bestSellers as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $bestSellers;
    }

    /**
     * Get the newest shop items.
     *
     * @param int $size
     * @return Collection<ShopItem>
     */
    public function getNewest(int $size): Collection
    {
        $size = min(25, max(0, $size));
        $newest = $this->repository->getNewest($size);

        foreach ($newest as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $newest;
    }

    /**
     * Get items currently on sale.
     *
     * @param int $size
     * @return Collection<ShopItem>
     */
    public function getDiscounts(int $size): Collection
    {
        $size = min(25, max(0, $size));
        $sales = $this->repository->getDiscounts($size);

        foreach ($sales as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $sales;
    }

    /**
     * Get a single shop item by its ID.
     *
     * @param int $id
     * @return ShopItem|null
     */
    public function getItemById(int $id): ?ShopItem
    {
        $item = $this->repository->findItemById($id);

        if ($item) {
            $item->img_url = url($item->img_url);
        }

        return $item;
    }

    /**
     * Handles the purchase of an item using in-game currency.
     *
     * @param User $user
     * @param ShopItem $item
     * @return array
     */
    public function purchaseItem(User $user, ShopItem $item): array
    {
        if ($item->is_unique) {
            $isAlreadyOwned = UserSale::where('user_id', $user->id)
                ->where('shop_item_id', $item->id)
                ->exists();

            if ($isAlreadyOwned) {
                return ['success' => false, 'message' => 'You already own this item.'];
            }
        }

        $price = $item->promo_price && $item->promo_price < $item->price ? $item->promo_price : $item->price;

        if ($user->money < $price) {
            return ['success' => false, 'message' => 'You do not have enough money.'];
        }

        if (empty($item->commands)) {
            return ['success' => false, 'message' => 'This item is not configured for delivery.'];
        }

        try {
            DB::transaction(function () use ($user, $item, $price) {
                $user->money -= $price;
                $user->save();

                UserSale::create([
                    'user_id' => $user->id,
                    'shop_item_id' => $item->id,
                ]);

                PendingReward::create([
                    'user_id' => $user->id,
                    'shop_item_id' => $item->id,
                    'commands' => $item->commands,
                    'status' => 'pending',
                ]);
            });
        } catch (Exception $e) {
            report($e);
            return ['success' => false, 'message' => 'An error occurred during the transaction. Please try again.'];
        }

        return ['success' => true, 'message' => 'You have successfully purchased ' . $item->name . '!'];
    }
}
