<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\Milestone;
use App\Services\StagesService;
use App\Models\MilestoneClosure;
use App\Services\UserService;

class StagesController extends Controller
{
    private StagesService $stagesService;
    private UserService $userService;


    public function __construct(StagesService $stagesService, UserService $userService) {
        $this->stagesService = $stagesService;
        $this->userService = $userService;
    }

    private function getStagesInfo() {
        return [
            'stages' => Stage::all(),
            'milestones' => Milestone::all(),
            'milestone_closure' => MilestoneClosure::all(),
        ];
    }

    /**
     * Get player stages information
     *
     * @param  string  $playerUuid
     * @return array
     */
    private function playerStagesInfo(string $playerUuid) {
        $user = $this->userService->getUserByUuid($playerUuid);
        $playerProgress = $this->stagesService->getMilestoneByUsername($user);

        if($user) {
            return [
                'stages' =>  Stage::all(),
                'milestones' =>  Milestone::all(),
                'milestone_closure' => MilestoneClosure::all(),
                'playerProgress' => $playerProgress,
            ];
        }

        abort(404, 'Username not found');
    }

    public function stagesJson() {
        return response()->json($this->getStagesInfo());
    }

    public function playerStagesJson($playerUuid) {
        return response()->json($this->playerStagesInfo($playerUuid));
    }

    public function loadStagesView() {
        return view('pages.stages', $this->getStagesInfo());
    }

    public function loadPlayerStageView($playerUuid) {
        return view('pages.stages', $this->playerStagesInfo($playerUuid));
    }

}
