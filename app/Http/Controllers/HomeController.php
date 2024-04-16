<?php

namespace App\Http\Controllers;

use App\Models\User;

class HomeController extends Controller
{
    public function getBestAllTimePlayers($size) {
        return User::orderBy('progress_point', 'desc')->take($size)->get();    
    }

    public function homeView() {
        $voteController = new VoteController();
        $bestAllTimePlayers = $this->getBestAllTimePlayers(3);

        return view('pages.home', 
            [
                'bestAllTimePlayers' => $bestAllTimePlayers,
            ]);
    }
}
