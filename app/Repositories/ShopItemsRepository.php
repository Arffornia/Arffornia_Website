<?php
namespace App\Repositories;

use App\Models\ShopItem;

class ShopItemsRepository {
    public function getBestSellers(int $size)
    {
        return ShopItem::withCount('userSales')
                        ->orderByDesc('user_sales_count')
                        ->limit($size)
                        ->get();
    }

    public function getNewest(int $size)
    {
        return ShopItem::latest()
                        ->limit($size)
                        ->get();
    }

    public function getSales(int $size)
    {
        return ShopItem::where('promo_price', '>=', '0')
                        ->inRandomOrder()
                        ->limit($size)
                        ->get();
    }
}
