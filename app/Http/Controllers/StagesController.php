<?php

namespace App\Http\Controllers;

use App\Http\Requests\playerInfoRequest;
use App\Models\User;
use App\Models\Stage;
use App\Models\Milestone;
use App\Models\MilestoneUser;
use App\Services\StagesService;
use App\Models\MilestoneClosure;

class StagesController extends Controller
{
    private StagesService $service;

    public function __construct(StagesService $service) {
        $this->service = $service;
    }

    public function getStartStage() {
        return Stage::where('number', 1)->first();
    }

    public function getStageById(int $id) {
        return Stage::where('id', $id)->first();  
    }

    private function stagesInfo() {
        $stages = Stage::all();
        $milestones = Milestone::all();
        $milestone_closure = MilestoneClosure::all();

        return [
            'stages' => $stages,
            'milestones' => $milestones,
            'milestone_closure' => $milestone_closure,
        ];
    }

    /**
     * Get player stages information
     *
     * @param  string  $playerName
     * @return array
     */
    private function playerStagesInfo(string $playerName) {
        $user = $this->service->getUserByName($playerName);
        $playerProgress = $this->service->getMilestoneByUsername($user);

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
        return response()->json($this->stagesInfo());
    }

    public function playerStagesJson($playerName) {
        return response()->json($this->playerStagesInfo($playerName));
    }

    public function loadStagesView() {
        return view('pages.stages', $this->stagesInfo());
    }

    public function loadPlayerStageView($playerName) {
        return view('pages.stages', $this->playerStagesInfo($playerName));
    }

}
