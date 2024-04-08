<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function playerProfil($playerName) {
        $user = User::where('name', $playerName)->first();

        if($user) {
            return response()->json([
                'money' => $user->money,
                'vote_count' => $user->getVoteCount(),
            ]);           
        } 

        return response()->json(['error' => 'player name not found.'], Response::HTTP_NOT_FOUND);
    }
}
