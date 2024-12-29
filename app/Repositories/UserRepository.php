<?php
namespace App\Repositories;

use App\Models\User;
use App\Models\Stage;
use Illuminate\Support\Collection;


class UserRepository {

    /**
     * Get size best user by progress points
     *
     * @param integer $size
     * @return Collection<User>
     */
    public function getBestUsersByProgressPoints(int $size) {
        return User::orderBy('progress_point', 'desc')->take($size)->get();
    }

    /**
     * Get user by username
     *
     * @param string $username
     * @return user
     */
    public function getUserByName($username) {
        return User::where('name', $username)->first();
    }

    /**
     * Get user by uuid
     *
     * @param int $uuid
     * @return User
     */
    public function getUserByUuid($uuid) {
        return User::where('uuid', $uuid)->first();
    }

    /**
     * Get size top voter player
     *
     * @param integer $size
     * @return Collection<User>
     */
    public function getTopVoters(int $size) {
        return User::withCount('votes')
                        ->orderByDesc('votes_count')
                        ->limit($size)
                        ->get();
    }

    /**
     * Get size top user by points
     *
     * @param integer $size
     * @return Collection<User>
     */
    public function getTopUsersByPoint(int $size) {
        return User::orderByDesc('progress_point')
                        ->limit($size)
                        ->get();
    }

    /**
     * Create a new user and return it
     *
     * @param string $name
     * @param string $uuid
     * @return User
     */
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
