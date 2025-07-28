<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Stage;
use Illuminate\Support\Collection;


class UserRepository
{

    /**
     * Get size best user by progress points
     *
     * @param integer $size
     * @return Collection<User>
     */
    public function getBestUsersByProgressPoints(int $size)
    {
        return User::orderBy('progress_point', 'desc')->take($size)->get();
    }

    /**
     * Get user by username
     *
     * @param string $username
     * @return user
     */
    public function getUserByName($username)
    {
        return User::where('name', $username)->first();
    }

    /**
     * Get user by uuid
     *
     * @param int $uuid
     * @return User
     */
    public function getUserByUuid($uuid)
    {
        return User::where('uuid', $uuid)->first();
    }

    /**
     * Get size top voter player
     *
     * @param integer $size
     * @return Collection<User>
     */
    public function getTopVoters(int $size)
    {
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
    public function getTopUsersByPoint(int $size)
    {
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
    public function createUser(string $name, string $uuid)
    {
        $startStageId = Stage::where('number', 1)->first()->id;

        $progression = \App\Models\Progression::create([
            'max_stage_id' => $startStageId,
            'completed_milestones' => [],
        ]);

        $user = User::create([
            'name' => $name,
            'uuid' => $uuid,
            'money' => 100000, //! TODO Remove that (For testing purposes only!)
            'progress_point' => 0,
            'stage_id' => $startStageId,
            'day_streak' => 0,
            'grade' => 'citizen',
            'role' => 'user',
            'solo_progression_id' => $progression->id,
            'active_progression_id' => $progression->id, # Default active progression is the player's solo progression
        ]);

        return $user;
    }
}
