<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\User;
use App\Services\HomeService;
use App\Services\NewsService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    private HomeService $homeService;
    private UserService $userService;
    private NewsService $newsService;

    public function __construct(HomeService $homeService,
                                UserService $userService,
                                NewsService $newsService)
    {
        $this->homeService = $homeService;
        $this->userService = $userService;
        $this->newsService = $newsService;
    }

    /**
     * Load the home view
     *
     * @return View
     */
    public function homeView() {
        return view('pages.home',
            [
                'bestAllTimePlayers' => $this->userService->getBestUsersByProgressPoints(3),
                'newsList' => $this->newsService->getNewestNews(3),
            ]);
    }
}
