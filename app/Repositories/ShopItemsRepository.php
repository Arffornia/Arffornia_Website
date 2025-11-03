<?php

namespace App\Repositories;

use App\Models\ShopItem;
use Illuminate\Database\Eloquent\Collection;

class ShopItemsRepository
{
    public function getRealMoneyItems(): Collection
    {
        return ShopItem::where('payment_type', 'real_money')->orderBy('price')->get();
    }

    public function getBestSellers(int $size): Collection
    {
        return ShopItem::where('payment_type', 'coins')
            ->withCount('userSales')
            ->orderByDesc('user_sales_count')
            ->limit($size)
            ->get();
    }

    public function getNewest(int $size): Collection
    {
        return ShopItem::where('payment_type', 'coins')
            ->where('show_in_newest', true)
            ->latest()
            ->limit($size)
            ->get();
    }

    public function getDiscounts(int $size): Collection
    {
        return ShopItem::where('payment_type', 'coins')
            ->where('allow_discounts', true)
            ->whereNotNull('promo_price')
            ->whereColumn('promo_price', '<', 'price')
            ->orderByRaw('(price - promo_price) / price DESC')
            ->limit($size)
            ->get();
    }

    public function findItemById(int $id): ?ShopItem
    {
        return ShopItem::find($id);
    }
}
