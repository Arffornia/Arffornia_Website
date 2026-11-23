<?php

namespace App\Http\Controllers;

use App\Services\NewsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function latestJson(Request $request): JsonResponse
    {
        $validated = $request->validate(['size' => 'sometimes|integer|min:1|max:5']);
        $size = (int) ($validated['size'] ?? 1);

        $news = $this->newsService->getNewestNews($size)->map(fn ($item) => [
            'id' => $item->id,
            'title' => $item->title,
            'date' => $item->created_at?->toDateString(),
            'url' => url("/news/{$item->id}"),
        ])->values();

        return response()
            ->json(['news' => $news])
            ->header('Cache-Control', 'public, max-age=300');
    }
}
