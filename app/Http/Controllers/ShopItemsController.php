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

    public function bestSellers($size)
    {
        return $this->shopItemsService->getBestSellers($size);
    }

    public function bestSellersJson($size): JsonResponse
    {
        $data = $this->bestSellers($size);
        return response()->json($data);
    }

    public function newest($size)
    {
        return $this->shopItemsService->getNewest($size);
    }

    public function newestJson($size): JsonResponse
    {
        $data = $this->newest($size);
        return response()->json($data);
    }

    public function sales($size)
    {
        return $this->shopItemsService->getSales($size);
    }

    public function salesJson($size): JsonResponse
    {
        $data = $this->sales($size);
        return response()->json($data);
    }
}
