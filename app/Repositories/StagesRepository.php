<?php
namespace App\Repositories;

use App\Models\User;
use App\Models\MilestoneUser;

class StagesRepository {

    public function getUserByName($username) {
        return User::where('name', $username)->first();
    }

    
    /**
     * Get milestone by user
     *
     * @param  User  $user
     * @return Collection<Milestone>
     */
    public function getMilestoneByUsername(User $user) {
        return MilestoneUser::where('user_id', $user->id)->get();;
    }
}