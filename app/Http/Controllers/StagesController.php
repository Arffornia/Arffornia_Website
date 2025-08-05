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

use App\Models\MilestoneRequirement;
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
                    'name' => $milestone->name,
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
        $progression = $user->activeProgression;

        return [
            'milestones' => Milestone::all()->map(function ($milestone) {
                return [
                    'id' => $milestone->id,
                    'icon_type' => $milestone->icon_type,
                    'x' => $milestone->x,
                    'y' => $milestone->y,
                ];
            }),

            'milestone_closure' => MilestoneClosure::all()->map(function ($closure) {
                return [
                    'milestone_id' => $closure->milestone_id,
                    'descendant_id' => $closure->descendant_id,
                ];
            }),

            'playerProgress' => [
                'completed_milestones' => $progression->completed_milestones ?? [],
                'current_target_id' => $progression->current_milestone_id ?? null,
                'max_stage_number' => $progression->maxStage->number
            ]
        ];
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
            'stage_id' => 'sometimes|required|integer|exists:stages,id'
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

    /**
     * Update the position of a milestone.
     *
     * @param Request $request
     * @param Milestone $milestone
     * @return JsonResponse
     */
    public function updateMilestonePosition(Request $request, Milestone $milestone): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'x' => 'required|integer',
            'y' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $milestone->update($validator->validated());

        return response()->json($milestone);
    }

    public function storeUnlock(Request $request, Milestone $milestone): JsonResponse
    {
        $data = $request->validate([
            'item_id' => 'required|string',
            'display_name' => 'nullable|string',
            'recipe_id_to_ban' => 'required|string',
            'shop_price' => 'nullable|integer',
            'image_path' => 'nullable|string',
        ]);

        $unlock = $milestone->unlocks()->create($data);
        return response()->json($unlock, Response::HTTP_CREATED);
    }

    public function updateUnlock(Request $request, MilestoneUnlock $unlock): JsonResponse
    {
        $data = $request->validate([
            'item_id' => 'required|string',
            'display_name' => 'nullable|string',
            'recipe_id_to_ban' => 'required|string',
            'shop_price' => 'nullable|integer',
            'image_path' => 'nullable|string',
        ]);

        $unlock->update($data);
        return response()->json($unlock);
    }

    public function destroyUnlock(MilestoneUnlock $unlock): JsonResponse
    {
        $unlock->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function storeRequirement(Request $request, Milestone $milestone): JsonResponse
    {
        $data = $request->validate([
            'item_id' => 'required|string',
            'display_name' => 'nullable|string',
            'amount' => 'required|integer|min:1',
            'image_path' => 'nullable|string',
        ]);

        $requirement = $milestone->requirements()->create($data);
        return response()->json($requirement, Response::HTTP_CREATED);
    }

    public function updateRequirement(Request $request, MilestoneRequirement $requirement): JsonResponse
    {
        $data = $request->validate([
            'item_id' => 'required|string',
            'display_name' => 'nullable|string',
            'amount' => 'required|integer|min:1',
            'image_path' => 'nullable|string',
        ]);

        $requirement->update($data);
        return response()->json($requirement);
    }

    public function destroyRequirement(MilestoneRequirement $requirement): JsonResponse
    {
        $requirement->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Store a new stage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeStage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'reward_progress_points' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();

        $validatedData['number'] = $this->stagesService->getNextStageNumber();

        $stage = $this->stagesService->createStage($validatedData);

        return response()->json($stage, Response::HTTP_CREATED);
    }


    /**
     * Delete a stage.
     *
     * @param Stage $stage
     * @return JsonResponse
     */
    public function destroyStage(Stage $stage): JsonResponse
    {
        $result = $this->stagesService->deleteStage($stage);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], Response::HTTP_CONFLICT);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
