<?php
namespace App\Repositories;

use App\Models\News;

class NewsRepository {
    /**
     * Get size newest news
     *
     * @param integer $size
     * @return Collection<News>
     */
    public function getNewestNews(int $size) {
        return News::orderBy("created_at", 'desc')->take($size)->get();
    }

    /**
     * Get all newest news
     *
     * @return Collection<News>
     */
    public function getAllNewestNews() {
        return News::orderBy("created_at", 'desc')->get();
    }

    /**
     * Get news by id
     *
     * @param integer $id
     * @return Collection<News>
     */
    public function getNews(int $id) {
        return News::findOrFail($id);
    }
}
