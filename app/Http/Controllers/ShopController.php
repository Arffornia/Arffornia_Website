<?php

namespace App\Http\Controllers;

use App\Services\ShopItemsService;

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
    public function shopView() {
        return view('pages.shop',
            [
                'newestItems' => $this->shopItemsService->getNewest(3),
                'saleItems' => $this->shopItemsService->getDiscounts(3),
                'bestSellerItems' => $this->shopItemsService->getBestSellers(3),
            ]);
    }
}
