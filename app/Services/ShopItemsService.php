<?php
namespace App\Services;

use App\Repositories\ShopItemsRepository;



class ShopItemsService {

    private ShopItemsRepository $repository;

    public function __construct(ShopItemsRepository $repository) {
        $this->repository = $repository;
    }

    public function getBestSellers($size)
    {
        $size = min(25, max(0, $size));
        return $this->repository->getBestSellers($size);
    }
}
