<?php
namespace App\Services;

use App\Repositories\ShopItemsRepository;



class ShopItemsService {

    private ShopItemsRepository $repository;

    public function __construct(ShopItemsRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * Get size best seller
     *
     * @param [type] $size
     * @return Collection<User>
     */
    public function getBestSellers($size)
    {
        $size = min(25, max(0, $size));
        $bestSellers = $this->repository->getBestSellers($size);

        foreach ($bestSellers as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $bestSellers;
    }

    /**
     * get size newest shop items
     *
     * @param [type] $size
     * @return Collection<ShopItem>
     */
    public function getNewest($size)
    {
        $size = min(25, max(0, $size));
        $newest = $this->repository->getNewest($size);

        foreach ($newest as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $newest;
    }

    /**
     * Get size discounts
     *
     * @param [type] $size
     * @return Collection<ShopItem>
     */
    public function getDiscounts($size)
    {
        $size = min(25, max(0, $size));
        $sales = $this->repository->getDiscounts($size);

        foreach ($sales as &$item) {
            $item->img_url = url($item->img_url);
        }

        return $sales;
    }
}
