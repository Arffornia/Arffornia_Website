<?php

namespace App\Services;

use App\Models\PendingReward;
use App\Models\ShopItem;
use App\Models\User;
use App\Models\UserSale;
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
     * Get size best seller
     *
     * @param [type] $size
     * @return Collection<User>
     */
    public function getBestSellers($size)
    {
        $size = min(25, max(0, $size));
        $bestSellers = $this->repository->getBestSellers($size);

        foreach ($bestSellers as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $bestSellers;
    }

    /**
     * get size newest shop items
     *
     * @param [type] $size
     * @return Collection<ShopItem>
     */
    public function getNewest($size)
    {
        $size = min(25, max(0, $size));
        $newest = $this->repository->getNewest($size);

        foreach ($newest as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $newest;
    }

    /**
     * Get size discounts
     *
     * @param [type] $size
     * @return Collection<ShopItem>
     */
    public function getDiscounts($size)
    {
        $size = min(25, max(0, $size));
        $sales = $this->repository->getDiscounts($size);

        foreach ($sales as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $sales;
    }

    /**
     * Get a single shop item by ID
     *
     * @param integer $id
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
     * Handles the purchase of an item by a user.
     *
     * @param User $user
     * @param ShopItem $item
     * @return array
     */
    public function purchaseItem(User $user, ShopItem $item): array
    {
        // Ensure the item has commands to execute
        if (empty($item->commands)) {
            return ['success' => false, 'message' => 'This item is not configured for delivery.'];
        }

        $price = $item->promo_price > 0 ? $item->promo_price : $item->real_price;

        try {
            // Balance and ownership are checked and updated while holding a lock on the
            // buyer's row, so concurrent purchases by the same user are serialized.
            $failure = DB::transaction(function () use ($user, $item, $price) {
                /** @var User $buyer */
                $buyer = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

                if ($item->is_unique) {
                    $isAlreadyOwned = UserSale::where('user_id', $buyer->id)
                        ->where('shop_item_id', $item->id)
                        ->exists();

                    if ($isAlreadyOwned) {
                        return 'You already own this item.';
                    }
                }

                if ($buyer->money < $price) {
                    return 'You do not have enough money.';
                }

                // 1. Deduct money from the user
                $buyer->money -= $price;
                $buyer->save();

                // 2. Log the sale
                UserSale::create([
                    'user_id' => $buyer->id,
                    'shop_item_id' => $item->id,
                ]);

                // 3. Create the pending reward for the in-game mod to pick up
                PendingReward::create([
                    'user_id' => $buyer->id,
                    'shop_item_id' => $item->id,
                    'commands' => $item->commands,
                    'status' => 'pending',
                ]);

                return null;
            });
        } catch (Exception $e) {
            report($e);
            return ['success' => false, 'message' => 'An error occurred during the transaction. Please try again.'];
        }

        if ($failure !== null) {
            return ['success' => false, 'message' => $failure];
        }

        // Keep the caller's model in sync with the database
        $user->refresh();

        return ['success' => true, 'message' => 'You have successfully purchased ' . $item->name . '! Your items will be delivered in-game shortly.'];
    }
}
