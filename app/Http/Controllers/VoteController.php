<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Services\VoteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VoteController extends Controller
{
    private VoteService $voteService;
    private UserService $userService;

    public function __construct(VoteService $voteService, UserService $userService)
    {
        $this->voteService = $voteService;
        $this->userService = $userService;
    }

    public function showVotePage(): View
    {
        return view('pages.vote', [
            'votingSites' => config('voting.sites'),
            'monthlyTop' => $this->voteService->getCurrentMonthTopVoters(10),
            'allTimeTop' => $this->voteService->getAllTimeTopVoters(10),
        ]);
    }

    public function verifyVote(Request $request): JsonResponse
    {
        $siteKey = $request->validate(['site' => 'required|string'])['site'];

        $result = $this->voteService->verifyAndReward($request->user(), $siteKey, $request);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Handles incoming votes from the NuVotifier proxy plugin.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleNuvotifierVote(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:16',
            'service_name' => 'required|string|max:255',
            'ip_address' => 'nullable|ip',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();
        $user = $this->userService->getUserByName($validated['username']);

        if (!$user) {
            Log::warning("Received NuVotifier vote for non-existent user: " . $validated['username']);
            return response()->json(['message' => 'User not found, vote ignored.'], Response::HTTP_OK);
        }

        $result = $this->voteService->recordTrustedVoteAndReward(
            $user,
            $validated['service_name'],
            $validated['ip_address'] ?? $request->ip()
        );

        return response()->json($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Get top N players for the current month.
     */
    public function bestPlayerByVoteJson($size): JsonResponse
    {
        $data = $this->voteService->getCurrentMonthTopVoters($size)
            ->map(fn($user) => ['name' => $user->name, 'uuid' => $user->uuid, 'value' => $user->votes_count]);

        return response()->json($data);
    }

    /**
     * Get top N players of all time.
     */
    public function bestPlayerByVoteAllTimeJson($size): JsonResponse
    {
        $data = $this->voteService->getAllTimeTopVoters($size)
            ->map(fn($user) => ['name' => $user->name, 'uuid' => $user->uuid, 'value' => $user->votes_count]);

        return response()->json($data);
    }
}
