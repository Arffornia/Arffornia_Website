<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Models\Progression;
use Illuminate\Http\JsonResponse;
use App\Services\ProgressionService;
use App\Progression\Keys;
use App\Progression\ProgressionVisibility;
use App\Repositories\TeamRepository;
use Illuminate\Support\Facades\Validator;
use Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class ProgressionController extends Controller
{
    protected ProgressionService $progressionService;
    protected UserService $userService;
    protected TeamRepository $teamRepository;

    public function __construct(ProgressionService $progressionService, UserService $userService, TeamRepository $teamRepository, private readonly ProgressionVisibility $visibility)
    {
        $this->progressionService = $progressionService;
        $this->userService = $userService;
        $this->teamRepository = $teamRepository;
    }

    private function getUserFromRequest(Request $request)
    {
        $uuid = $request->input('player_uuid');

        $validator = Validator::make(['player_uuid' => $uuid], [
            'player_uuid' => 'required|string|size:32'
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return $this->userService->getUserByUuid($uuid);
    }

    /** A progression by id; a private one answers as a missing one (spec 019). */
    public function getProgressionById(string $progression): JsonResponse
    {
        $progression = Progression::find($progression);
        if ($progression === null || !$this->visibility->canSee($progression)) {
            return response()->json(['message' => __('api.progression.not_found')], Response::HTTP_NOT_FOUND);
        }
        $progression->load('currentTargetedMilestone');
        return response()->json(array_merge($progression->toArray(), [
            'retired' => $this->teamRepository->isProgressionRetired($progression->id),
        ]));
    }

    public function removeMilestone(Request $request): JsonResponse
    {
        $user = $this->getUserFromRequest($request);
        if (!$user) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        $milestoneId = $request->input('milestone_id');
        if (!is_numeric($milestoneId)) {
            return response()->json(['message' => __('api.progression.invalid_milestone_id')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->progressionService->removeMilestone($user, $milestoneId);
        return response()->json(['message' => __('api.progression.milestone_removed')]);
    }

    public function listMilestones(Request $request): JsonResponse
    {
        $user = $this->getUserFromRequest($request);
        if (!$user) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        $user->load('activeProgression');

        return response()->json([
            'completed_milestones' => $user->activeProgression->completed_milestones ?? []
        ]);
    }

    /**
     * Sets the player's currently targeted milestone.
     * This is called by the player from the in-game GUI.
     */
    public function setTargetMilestone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // The milestone_id can be nullable to allow clearing the target
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'milestone_key' => ['sometimes', 'nullable', 'string', 'max:128', 'regex:' . Keys::MILESTONE_PATTERN],
        ]);

        $user = $this->getUserFromRequest($request);
        if (!$user instanceof User) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        if (isset($validated['milestone_key'])) {
            $outcome = $this->progressionService->setTargetMilestoneByKey($user, $validated['milestone_key']);

            return $outcome === 'ok'
                ? response()->json(['message' => __('api.progression.target_updated'), 'target' => $validated['milestone_key']])
                : response()->json(['message' => __('api.progression.target_refused'), 'reason' => $outcome], Response::HTTP_CONFLICT);
        }

        $success = $this->progressionService->setTargetMilestone($user, $validated['milestone_id'] ?? null);

        if ($success) {
            return response()->json(['message' => __('api.progression.target_updated')]);
        }

        return response()->json(['message' => __('api.progression.target_update_failed')], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
