<?php

namespace App\Repositories;

use App\Models\ShopItem;
use Illuminate\Database\Eloquent\Collection;

class ShopItemsRepository
{
    /**
     * Get best seller
     *
     * @param integer $size
     * @return Collection<ShopItem>
     */
    public function getBestSellers(int $size)
    {
        return ShopItem::withCount('userSales')
            ->orderByDesc('user_sales_count')
            ->limit($size)
            ->get();
    }

    /**
     * Get size newest items
     *
     * @param integer $size
     * @return Collection<ShopItem>
     */
    public function getNewest(int $size)
    {
        return ShopItem::latest()
            ->limit($size)
            ->get();
    }

    /**
     * Get size discounts items
     *
     * @param integer $size
     * @return Collection<ShopItem>
     */
    public function getDiscounts(int $size)
    {
        return ShopItem::where('promo_price', '>', 0)
            ->whereColumn('promo_price', '<', 'real_price')
            ->orderByRaw('(real_price - promo_price) / real_price DESC')
            ->limit($size)
            ->get();
    }
}
