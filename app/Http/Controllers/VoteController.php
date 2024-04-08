<?php

namespace App\Http\Controllers;

use App\Models\User;


class VoteController extends Controller
{
    public function bestPlayerByVote($size) {
        if($size < 0) {
            $size *= -1;
        }

        if($size > 25) {
            $size = 25;
        }
    
        $topVoters = User::withCount('votes')
        ->orderByDesc('votes_count')
        ->limit($size)
        ->get()
        ->map(function ($user) {
            return [
                'name' => $user->name,
                'vote_count' => $user->votes_count,
            ];
        });

        return response()->json($topVoters);
    }
}
