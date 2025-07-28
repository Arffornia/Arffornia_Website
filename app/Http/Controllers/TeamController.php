<?php

namespace App\Http\Controllers;

use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Handles the request when a player joins a team.
     */
    public function playerJoin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'player_uuid' => 'required|string|size:32',
            'team_uuid' => 'required|string|uuid',
            'team_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();
        $success = $this->teamService->handlePlayerJoin($data['player_uuid'], $data['team_uuid'], $data['team_name']);

        if ($success) {
            return response()->json(['message' => 'Player joined team successfully.'], Response::HTTP_OK);
        }

        return response()->json(['message' => 'Failed to process player join.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Handles the request when a player leaves a team.
     */
    public function playerLeave(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'player_uuid' => 'required|string|size:32',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $success = $this->teamService->handlePlayerLeave($validator->validated()['player_uuid']);

        if ($success) {
            return response()->json(['message' => 'Player left team successfully.'], Response::HTTP_OK);
        }

        return response()->json(['message' => 'Failed to process player leave.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Handles the request to disband a team.
     */
    public function disband(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_uuid' => 'required|string|uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $success = $this->teamService->handleTeamDisband($validator->validated()['team_uuid']);

        if ($success) {
            return response()->json(['message' => 'Team disbanded successfully.'], Response::HTTP_OK);
        }

        return response()->json(['message' => 'Failed to disband team.'], Response::HTTP_NOT_FOUND);
    }
}
