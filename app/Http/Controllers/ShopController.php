<?php

namespace App\Http\Controllers;

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
    public function shopView()
    {
        return view(
            'pages.shop',
            [
                'newestItems' => $this->shopItemsService->getNewest(6),
                'saleItems' => $this->shopItemsService->getDiscounts(6),
                'bestSellerItems' => $this->shopItemsService->getBestSellers(6),
            ]
        );
    }
}
