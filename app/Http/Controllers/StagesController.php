<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stage;
use App\Models\Milestone;
use App\Models\MilestoneUser;
use App\Models\MilestoneClosure;

class StagesController extends Controller
{
    public function getUserByName($username) {
        return User::where('name', $username)->first();
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

    private function playerStagesInfo($playerName) {
        $stages = Stage::all();
        $milestones = Milestone::all();
        $milestone_closure = MilestoneClosure::all();
        
        $user = $this->getUserByName($playerName);
        if(!$user) {
            abort(404, 'Username not found');
        }
        

        $playerProgress = MilestoneUser::where('user_id', $user->id)->get();

        return [
            'stages' => $stages,
            'milestones' => $milestones,
            'milestone_closure' => $milestone_closure,
            'playerProgress' => $playerProgress,
        ];
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
