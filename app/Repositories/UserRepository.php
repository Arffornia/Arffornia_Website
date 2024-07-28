<?php
namespace App\Repositories;

use App\Models\User;
use App\Models\Stage;

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

    public function createUser(string $name, string $uuid) {
        $startStageId = Stage::where('number', 1)->first()->id;

        $formFields['name'] = $name;
        $formFields['uuid'] = $uuid;
        $formFields['money'] = 0;
        $formFields['progress_point'] = 0;
        $formFields['stage_id'] = $startStageId;
        $formFields['day_streak'] = 0;
        $formFields['grade'] = 'citizen';

        // Create User
        return User::create($formFields);
    }
}
