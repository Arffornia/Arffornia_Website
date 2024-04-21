<?php
namespace App\Repositories;

use App\Models\User;
use App\Models\MilestoneUser;
use App\Models\Stage;

class StagesRepository {
    
    /**
     * Get milestone by user
     *
     * @param  User  $user
     * @return Collection<Milestone>
     */
    public function getMilestoneByUsername(User $user) {
        return MilestoneUser::where('user_id', $user->id)->get();;
    }

    public function getStageById(int $id) {
        return Stage::where('id', $id)->first();  
    }

    public function getStartStage() {
        return Stage::where('number', 1)->first();
    }

}