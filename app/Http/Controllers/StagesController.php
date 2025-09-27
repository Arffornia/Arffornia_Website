<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
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

use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
     * Exports all progression-related data into a single JSON response.
     * This includes stages, milestones with their requirements and unlocks (including recipes),
     * and the links between milestones.
     *
     * @return JsonResponse
     */
    public function exportStages(): JsonResponse
    {
        return response()->json([
            'stages' => Stage::orderBy('number')->get(),
            'milestones' => Milestone::with(['requirements', 'unlocks.recipe'])->get(),
            'milestone_closure' => MilestoneClosure::all(),
        ]);
    }

    /**
     * Imports progression data from a JSON file, replacing all existing data.
     * This operation is transactional; it will either fully succeed or fail without
     * leaving the database in a partially updated state.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importStages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stages' => 'required|array',
            'milestones' => 'required|array',
            'milestone_closure' => 'present|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid file format.', 'errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();
        Log::info('Starting import process (v4 - with sequence reset).', [
            'stage_count' => count($data['stages']),
            'milestone_count' => count($data['milestones'])
        ]);

        DB::beginTransaction();
        try {
            Schema::disableForeignKeyConstraints();
            Log::info('Foreign key constraints disabled.');

            $tablesToTruncate = ['recipes', 'milestone_unlocks', 'milestone_requirements', 'milestone_closure'];
            foreach ($tablesToTruncate as $table) {
                DB::table($table)->truncate();
            }
            Log::info('Relation tables truncated.');

            foreach ($data['stages'] as $stageData) {
                Stage::updateOrCreate(['id' => $stageData['id']], collect($stageData)->except(['created_at', 'updated_at'])->toArray());
            }
            Log::info('Stages processed.');

            foreach ($data['milestones'] as $milestoneData) {
                Milestone::updateOrCreate(
                    ['id' => $milestoneData['id']],
                    collect($milestoneData)->except(['requirements', 'unlocks', 'created_at', 'updated_at'])->toArray()
                );
            }
            Log::info('Milestones table updated.');

            foreach ($data['milestones'] as $milestoneData) {
                foreach ($milestoneData['requirements'] ?? [] as $req) {
                    MilestoneRequirement::updateOrCreate(['id' => $req['id']], collect($req)->except(['image_url'])->toArray());
                }

                foreach ($milestoneData['unlocks'] ?? [] as $unlock) {
                    $unlockRecord = MilestoneUnlock::updateOrCreate(
                        ['id' => $unlock['id']],
                        collect($unlock)->except(['recipe', 'image_url'])->toArray()
                    );
                    if (!empty($unlock['recipe'])) {
                        Recipe::updateOrCreate(['id' => $unlock['recipe']['id']], collect($unlock['recipe'])->toArray());
                    }
                }
            }
            Log::info('Relation data processed via updateOrCreate.');

            if (!empty($data['milestone_closure'])) {
                MilestoneClosure::insert($data['milestone_closure']);
            }
            Log::info('MilestoneClosure data inserted.');

            $importedMilestoneIds = collect($data['milestones'])->pluck('id');
            Milestone::whereNotIn('id', $importedMilestoneIds)->whereDoesntHave('progressions')->delete();
            $importedStageIds = collect($data['stages'])->pluck('id');
            Stage::whereNotIn('id', $importedStageIds)->whereDoesntHave('progressions')->delete();
            Log::info('Old, unused data cleaned up.');

            $tablesWithSequences = ['stages', 'milestones', 'milestone_requirements', 'milestone_unlocks', 'recipes', 'milestone_closure'];
            foreach ($tablesWithSequences as $table) {
                $maxId = DB::table($table)->max('id');
                if ($maxId) {
                    DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), ?, true)", [$maxId]);
                }
            }
            Log::info('PostgreSQL sequences have been reset.');

            Schema::enableForeignKeyConstraints();
            DB::commit();
            Log::info('Import transaction committed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Schema::enableForeignKeyConstraints();
            Log::error('An error occurred during import. Operation rolled back.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            report($e);
            return response()->json(['message' => 'An error occurred during import.', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['message' => 'Progression data imported successfully. Please refresh the page.']);
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
            'milestones' => Milestone::with('stage')->get()->map(function ($milestone) {
                $stageNumber = $milestone->stage ? $milestone->stage->number : 1;

                return [
                    'id' => $milestone->id,
                    'icon_type' => $milestone->icon_type,
                    'x' => $milestone->x,
                    'y' => $milestone->y,
                    'stage_number' => $stageNumber,
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
        $milestone->load(['unlocks.recipe', 'requirements', 'stage']);
        $responseData = $milestone->toArray();

        if ($milestone->stage) {
            $responseData['stage_number'] = $milestone->stage->number;
        }

        return response()->json($responseData);
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
        $result = $this->stagesService->createLink(
            $validator->validated()['source_id'],
            $validator->validated()['target_id']
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], Response::HTTP_CONFLICT);
        }

        return response()->json($result['link'], Response::HTTP_CREATED);
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
        $bannedRecipes = MilestoneUnlock::pluck('recipes_to_ban')
            ->flatten()
            ->unique()
            ->values()
            ->all();

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
            'recipes_to_ban' => 'required|array',
            'recipes_to_ban.*' => 'string|distinct',
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
            'recipes_to_ban' => 'required|array',
            'recipes_to_ban.*' => 'string|distinct',
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
