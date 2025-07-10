<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\Milestone;
use App\Services\UserService;
use App\Services\StagesService;
use App\Models\MilestoneClosure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use function PHPSTORM_META\map;

class StagesController extends Controller
{
    private StagesService $stagesService;
    private UserService $userService;


    public function __construct(StagesService $stagesService, UserService $userService)
    {
        $this->stagesService = $stagesService;
        $this->userService = $userService;
    }

    /**
     * Return the all stages information
     *
     * @return  array
     */
    private function getStagesInfo()
    {
        $isAdmin = auth()->check() && auth()->user()->hasRole('admin');

        return [
            'stages' => Stage::all()->map(function ($stage) {
                return [
                    'id' => $stage->id,
                    'number' => $stage->number,
                ];
            }),
            'milestones' => Milestone::all()->map(function ($milestone) {
                return [
                    'id' => $milestone->id,
                    'stage_id' => $milestone->stage_id,
                    'icon_type' => $milestone->icon_type,
                    'x' => $milestone->x,
                    'y' => $milestone->y,
                ];
            }),
            'milestone_closure' => MilestoneClosure::all(),
            'isAdmin' => $isAdmin,
        ];
    }


    /**
     * Get all stages information (export)
     *
     * @return array
     */
    public function exportStages()
    {
        return response()->json([
            'stages' => Stage::all(),
            'milestones' => Milestone::all(),
            'milestone_closure' => MilestoneClosure::all(),
        ]);
    }

    /**
     * Get player stages information
     *
     * @param  string  $playerUuid
     * @return array
     */
    private function playerStagesInfo(string $playerUuid)
    {
        $user = $this->userService->getUserByUuid($playerUuid);
        $playerProgress = $this->stagesService->getMilestoneByUsername($user);
        $isAdmin = auth()->check() && auth()->user()->hasRole('admin');

        if ($user) {
            return [
                'stages' =>  Stage::all(),
                'milestones' =>  Milestone::all(),
                'milestone_closure' => MilestoneClosure::all(),
                'playerProgress' => $playerProgress,
                'isAdmin' => $isAdmin,
            ];
        }

        abort(404, 'Username not found');
    }
    /**
     * Get all stages information as JSON
     *
     * @return JsonResponse
     */
    public function stagesJson()
    {
        return response()->json($this->getStagesInfo());
    }

    /**
     * Get player stage information as JSON
     *
     * @param int $playerUuid
     * @return JsonResponse
     */
    public function playerStagesJson($playerUuid)
    {
        return response()->json($this->playerStagesInfo($playerUuid));
    }


    /**
     * Get node by node ID
     *
     * @param int $nodeId
     * @return JsonResponse
     */
    public function getMilestoneById(Milestone $milestone)
    {
        // Items relation can be loaded here if needed in the future
        // $milestone->load('items');
        return response()->json($milestone);
    }

    /**
     * Update a milestone
     *
     * @param Request $request
     * @param Milestone $milestone
     * @return JsonResponse
     */
    public function updateMilestone(Request $request, Milestone $milestone)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reward_progress_points' => 'required|integer|min:0',
            'icon_type' => 'required|string|in:tech,pipe,magic,default',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $milestone->update($validator->validated());

        return response()->json($milestone);
    }

    /**
     * Load stages view
     *
     * @return View
     */
    public function loadStagesView()
    {
        return view('pages.stages', $this->getStagesInfo());
    }

    /**
     * Load player stage view
     *
     * @param int $playerUuid
     * @return View
     */
    public function loadPlayerStageView($playerUuid)
    {
        return view('pages.stages', $this->playerStagesInfo($playerUuid));
    }
}
