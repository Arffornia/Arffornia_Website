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

    /**
     * Get stage by id
     *
     * @param integer $id
     * @return Stage
     */
    public function getStageById(int $id) {
        return Stage::where('id', $id)->first();
    }

    /**
     * Get starting stage
     * The starting stage at the beginning stage for each player
     *
     * @return Stage
     */
    public function getStartStage() {
        return Stage::where('number', 1)->first();
    }

}
