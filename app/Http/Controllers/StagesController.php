<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\Milestone;
use App\Services\UserService;
use Illuminate\Http\Response;
use App\Services\StagesService;
use App\Models\MilestoneClosure;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Routing\ResponseFactory;

class StagesController extends Controller
{
    private StagesService $stagesService;
    private UserService $userService;


    public function __construct(StagesService $stagesService, UserService $userService) {
        $this->stagesService = $stagesService;
        $this->userService = $userService;
    }

    /**
     * Return the all stages information
     *
     * @return  Collection<Stage>
     */
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
     * @return Collection<Stage>
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

    /**
     * Get all stages information as JSON
     *
     * @return JsonResponse
     */
    public function stagesJson() {
        return response()->json($this->getStagesInfo());
    }

    /**
     * Get player stage information as JSON
     *
     * @param int $playerUuid
     * @return JsonResponse
     */
    public function playerStagesJson($playerUuid) {
        return response()->json($this->playerStagesInfo($playerUuid));
    }

    /**
     * Load stages view
     *
     * @return View
     */
    public function loadStagesView() {
        return view('pages.stages', $this->getStagesInfo());
    }

    /**
     * Load player stage view
     *
     * @param int $playerUuid
     * @return View
     */
    public function loadPlayerStageView($playerUuid) {
        return view('pages.stages', $this->playerStagesInfo($playerUuid));
    }

}
