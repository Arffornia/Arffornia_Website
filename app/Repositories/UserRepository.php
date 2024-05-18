<?php
namespace App\Repositories;

use App\Models\User;

class UserRepository {
    public function getBestUsersByProgressPoints(int $size) {
        return User::orderBy('progress_point', 'desc')->take($size)->get();
    }

    public function getUserByName($username) {
        return User::where('name', $username)->first();
    }

    public function getUserByUuid($uuid) {
        return User::where('uuid', $uuid)->first();
    }

    public function getTopVoters(int $size) {
        return User::withCount('votes')
                        ->orderByDesc('votes_count')
                        ->limit($size)
                        ->get();
    }
}
