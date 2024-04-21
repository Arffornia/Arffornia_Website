<?php
namespace App\Services;

use App\Repositories\NewsRepository;

class NewsService {

    private NewsRepository $repository;

    public function __construct(NewsRepository $repository) {
        $this->repository = $repository;
    }

    public function getNewestNews(int $size) {
        return $this->repository->getNewestNews($size);
    }

    public function getAllNewestNews() {
        return $this->repository->getAllNewestNews();
    }

    public function getNews(int $id) {
        return $this->repository->getNews($id);
    }
}