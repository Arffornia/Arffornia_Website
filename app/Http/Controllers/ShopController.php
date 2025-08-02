<?php

namespace App\Http\Controllers;

use App\Models\ShopItem;
use App\Services\ShopItemsService;
use Illuminate\Contracts\View\View;

class ShopController extends Controller
{
    private ShopItemsService $shopItemsService;

    public function __construct(ShopItemsService $shopItemsService)
    {
        $this->shopItemsService = $shopItemsService;
    }

    /**
     * Load the shop view
     *
     * @return View
     */
    public function shopView(): View
    {
        return $this->loadShop();
    }

    /**
     * Load the shop view with a specific item pre-selected.
     *
     * @param ShopItem $item
     * @return View
     */
    public function shopItemView(ShopItem $item): View
    {
        return $this->loadShop($item->id);
    }

    /**
     * Helper to load the shop view with optional initial item.
     *
     * @param int|null $initialItemId
     * @return View
     */
    private function loadShop(int $initialItemId = null): View
    {
        return view(
            'pages.shop',
            [
                'newestItems' => $this->shopItemsService->getNewest(6),
                'saleItems' => $this->shopItemsService->getDiscounts(6),
                'bestSellerItems' => $this->shopItemsService->getBestSellers(6),
                'initialItemId' => $initialItemId,
            ]
        );
    }
}
