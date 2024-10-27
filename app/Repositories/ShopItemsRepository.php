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
}
