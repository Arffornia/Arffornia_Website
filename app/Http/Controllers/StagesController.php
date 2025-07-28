<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\Milestone;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Models\MilestoneUnlock;
use App\Services\StagesService;
use function PHPSTORM_META\map;
use App\Models\MilestoneClosure;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

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
        if (!$user) {
            abort(404, 'User not found');
        }

        $user->load('activeProgression');
        $isAdmin = auth()->check() && auth()->user()->hasRole('admin');

        if ($user) {
            return [
                'stages' =>  Stage::all(),
                'milestones' => Milestone::all(),
                'milestone_closure' => MilestoneClosure::all(),
                'playerProgress' => [
                    'completed_milestones' => $user->activeProgression->completed_milestones ?? []
                ],
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
     * Get detailed information for a single milestone, including its unlocks and requirements.
     *
     * @param Milestone $milestone
     * @return JsonResponse
     */
    public function getMilestoneById(Milestone $milestone): JsonResponse
    {
        // Eager load the relationships to prevent N+1 query problems.
        $milestone->load(['unlocks', 'requirements']);

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
            // 'icon_type' => 'required|string|in:tech,pipe,magic,default',
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

    /**
     * Store a new milestone.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeMilestone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stage_id' => 'required|integer|exists:stages,id',
            'icon_type' => 'required|string|in:tech,pipe,magic,default',
            'x' => 'required|integer',
            'y' => 'required|integer',
            'reward_progress_points' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $milestone = $this->stagesService->createMilestone($validator->validated());

        return response()->json($milestone, Response::HTTP_CREATED);
    }

    /**
     * Delete a milestone.
     *
     * @param Milestone $milestone
     * @return JsonResponse
     */
    public function destroyMilestone(Milestone $milestone): JsonResponse
    {
        $this->stagesService->deleteMilestone($milestone->id);
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Store a new milestone link.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source_id' => 'required|integer|exists:milestones,id',
            'target_id' => 'required|integer|exists:milestones,id|different:source_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $link = $this->stagesService->createLink(
            $validator->validated()['source_id'],
            $validator->validated()['target_id']
        );

        if (!$link) {
            return response()->json(['message' => 'Link already exists.'], Response::HTTP_CONFLICT);
        }

        return response()->json($link, Response::HTTP_CREATED);
    }

    /**
     * Delete a milestone link.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroyLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source_id' => 'required|integer|exists:milestones,id',
            'target_id' => 'required|integer|exists:milestones,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $deleted = $this->stagesService->deleteLink(
            $validator->validated()['source_id'],
            $validator->validated()['target_id']
        );

        if (!$deleted) {
            return response()->json(['message' => 'Link not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Provides a lean JSON response specifically for the NeoForge mod.
     * Contains only the data necessary for the mod to function, like the banned recipe list.
     *
     * @return JsonResponse
     */
    public function getProgressionConfigForMod(): JsonResponse
    {
        $bannedRecipes = MilestoneUnlock::pluck('recipe_id_to_ban')->all();

        return response()->json([
            'banned_recipes' => $bannedRecipes
        ]);
    }
}
