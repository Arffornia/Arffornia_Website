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
     * @param  string  $playerName
     * @return array
     */
    private function playerStagesInfo(string $playerName) {
        $user = $this->userService->getUserByName($playerName);
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

    public function playerStagesJson($playerName) {
        return response()->json($this->playerStagesInfo($playerName));
    }

    public function loadStagesView() {
        return view('pages.stages', $this->getStagesInfo());
    }

    public function loadPlayerStageView($playerName) {
        return view('pages.stages', $this->playerStagesInfo($playerName));
    }

}
