<?php

namespace App\Services;


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
        if ($item->is_unique) {
            $isAlreadyOwned = UserSale::where('user_id', operator: $user->id)
                ->where('shop_item_id', $item->id)
                ->exists();

            if ($isAlreadyOwned) {
                return ['success' => false, 'message' => 'You already own this item.'];
            }
        }

        $price = $item->promo_price > 0 ? $item->promo_price : $item->real_price;

        if ($user->money < $price) {
            return ['success' => false, 'message' => 'You do not have enough money.'];
        }

        try {
            DB::transaction(function () use ($user, $item, $price) {
                $user->money -= $price;
                $user->save();

                UserSale::create([
                    'user_id' => $user->id,
                    'shop_item_id' => $item->id,
                ]);

                // TODO add event to give player's items
            });
        } catch (Exception $e) {
            report($e);
            return ['success' => false, 'message' => 'An error occurred during the transaction. Please try again.'];
        }

        return ['success' => true, 'message' => 'You have successfully purchased ' . $item->name . '!'];
    }
}
