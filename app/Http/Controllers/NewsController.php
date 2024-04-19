<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function allNewsView() {
        $newsList = News::all();
        return view('pages.news.allNews', compact('newsList'));
    }

    public function newsView($newsId) {
        $news = News::findOrFail('id', $newsId);
        return view('pages.news.news', compact('news'));
    }
}
