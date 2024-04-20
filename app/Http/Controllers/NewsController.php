<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function allNewsView() {
        $newsList = News::orderBy('created_at', 'desc')->get();
        return view('pages.news.allNews', compact('newsList'));
    }

    public function newsView($newsId) {
        $news = News::where('id', $newsId)->first();

        if($news){
            return view('pages.news.news', compact('news'));
        }
    }
}
