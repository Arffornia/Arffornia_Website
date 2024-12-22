<?php

namespace App\Http\Controllers;

use App\Services\NewsService;
use Illuminate\Contracts\View\View;

class NewsController extends Controller
{
    private NewsService $newsService;

    public function __construct(NewsService $newsService) {
        $this->newsService = $newsService;
    }

    /**
     * Load news page
     *
     * @return View
     */
    public function allNewsView() {
        return view('pages.news.allNews',
            [
                'newsList' => $this->newsService->getAllNewestNews(),
            ]);
    }

    /**
     * Load single new view
     *
     * @param int $newsId
     * @return View
     */
    public function newsView($newsId) {
        return view('pages.news.news',
            [
                'news' => $this->newsService->getNews($newsId),
            ]);
    }
}
