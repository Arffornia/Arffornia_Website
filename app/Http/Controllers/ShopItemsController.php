<?php

namespace App\Http\Controllers;

use App\Services\ShopItemsService;
use Illuminate\Http\JsonResponse;

class ShopItemsController extends Controller
{
    private ShopItemsService $shopItemsService;

    public function __construct(ShopItemsService $shopItemsService)
    {
        $this->shopItemsService = $shopItemsService;
    }

    public function bestSellers($size): array
    {
        return $this->shopItemsService->getBestSellers($size);
    }

    public function bestSellersJson($size): JsonResponse
    {
        $data = $this->bestSellers($size);
        return response()->json($data);
    }
}
