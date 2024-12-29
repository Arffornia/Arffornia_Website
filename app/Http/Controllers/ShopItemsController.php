<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\ShopItemsService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Collection;

class ShopItemsController extends Controller
{
    private ShopItemsService $shopItemsService;

    public function __construct(ShopItemsService $shopItemsService)
    {
        $this->shopItemsService = $shopItemsService;
    }

    /**
     * Return the size best sallers
     *
     * @param int $size
     * @return Collection<ShopItem>
     */
    public function bestSellers($size)
    {
        return $this->shopItemsService->getBestSellers($size);
    }

    /**
     * Return the size best sellers as JSON
     *
     * @param int $size
     * @return JsonResponse
     */
    public function bestSellersJson($size)
    {
        $data = $this->bestSellers($size);
        return response()->json($data);
    }

    /**
     *  Return the size newest items
     *
     * @param int $size
     * @return Collection<ShopItem>
     */
    public function newest($size)
    {
        return $this->shopItemsService->getNewest($size);
    }

    /**
     * Return the size newest items as JSON
     *
     * @param int $size
     * @return JsonResponse
     */
    public function newestJson($size): JsonResponse
    {
        $data = $this->newest($size);
        return response()->json($data);
    }

    /**
     * Return the size discounts items
     *
     * @param int $size
     * @return Collection<ShopItem>
     */
    public function sales($size)
    {
        return $this->shopItemsService->getDiscounts($size);
    }

    /**
     * Return the size discounts items as JSON
     *
     * @param int $size
     * @return JsonResponse
     */
    public function salesJson($size): JsonResponse
    {
        $data = $this->sales($size);
        return response()->json($data);
    }
}
