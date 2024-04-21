<?php

namespace App\Http\Controllers;

use App\Services\NewsService;

class NewsController extends Controller
{
    private NewsService $newsService;

    public function __construct(NewsService $newsService) {
        $this->newsService = $newsService;
    }

    public function allNewsView() {
        return view('pages.news.allNews', 
            [
                'newsList' => $this->newsService->getAllNewestNews(),
            ]);
    }

    public function newsView($newsId) {
        return view('pages.news.news', 
            [
                'news' => $this->newsService->getNews($newsId),
            ]);
    }
}
