<?php
namespace App\Repositories;

use App\Models\News;

class NewsRepository {
    public function getNewestNews(int $size) {
        return News::orderBy("created_at", 'desc')->take($size)->get();
    }

    public function getAllNewestNews() {
        return News::orderBy("created_at", 'desc')->get();
    }

    public function getNews(int $id) {
        return News::where('id', $id)->findOrFail();
    }
}