<?php
namespace App\Services;

use App\Models\News;
use App\Repositories\NewsRepository;

class NewsService {

    private NewsRepository $repository;

    public function __construct(NewsRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * Get size newest news
     *
     * @param integer $size
     * @return Collection<News>
     */
    public function getNewestNews(int $size) {
        return $this->repository->getNewestNews($size);
    }

    /**
     * Get all newest news
     *
     * @return Collection<News>
     */
    public function getAllNewestNews() {
        return $this->repository->getAllNewestNews();
    }

    /**
     * Get news by id
     *
     * @param integer $id
     * @return News
     */
    public function getNews(int $id) {
        return $this->repository->getNews($id);
    }
}
