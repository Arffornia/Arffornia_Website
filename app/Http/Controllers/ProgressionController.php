<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use App\Services\ProgressionService;
use Illuminate\Support\Facades\Validator;
use Log;
use Symfony\Component\HttpFoundation\Response;

class ProgressionController extends Controller
{
    protected ProgressionService $progressionService;
    protected UserService $userService;

    public function __construct(ProgressionService $progressionService, UserService $userService)
    {
        $this->progressionService = $progressionService;
        $this->userService = $userService;
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

    public function addMilestone(Request $request): JsonResponse
    {
        $user = $this->getUserFromRequest($request);
        if (!$user) {
            return response()->json(['message' => 'Player not found.'], Response::HTTP_NOT_FOUND);
        }

        $milestoneId = $request->input('milestone_id');
        if (!is_numeric($milestoneId) || !Milestone::find($milestoneId)) {
            return response()->json(['message' => 'Invalid milestone_id.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->progressionService->addMilestone($user, $milestoneId);
        return response()->json(['message' => 'Milestone added successfully.']);
    }

    public function removeMilestone(Request $request): JsonResponse
    {
        $user = $this->getUserFromRequest($request);
        if (!$user) {
            return response()->json(['message' => 'Player not found.'], Response::HTTP_NOT_FOUND);
        }

        $milestoneId = $request->input('milestone_id');
        if (!is_numeric($milestoneId)) {
            return response()->json(['message' => 'Invalid milestone_id.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->progressionService->removeMilestone($user, $milestoneId);
        return response()->json(['message' => 'Milestone removed successfully.']);
    }

    public function listMilestones(Request $request): JsonResponse
    {
        $user = $this->getUserFromRequest($request);
        if (!$user) {
            return response()->json(['message' => 'Player not found.'], Response::HTTP_NOT_FOUND);
        }

        $user->load('activeProgression');

        return response()->json([
            'completed_milestones' => $user->activeProgression->completed_milestones ?? []
        ]);
    }
}
