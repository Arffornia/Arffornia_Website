<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\User;

class HomeController extends Controller
{
    public function getBestAllTimePlayers($size) {
        return User::orderBy('progress_point', 'desc')->take($size)->get();    
    }

    public function homeView() {
        $bestAllTimePlayers = $this->getBestAllTimePlayers(3);
        $newsList = News::orderBy("created_at", 'desc')->take(3)->get();

        return view('pages.home', compact('bestAllTimePlayers', 'newsList'));
    }
}
