<?php

namespace App\Http\Controllers;

use App\Services\VoteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class VoteController extends Controller
{
    private VoteService $voteService;

    public function __construct(VoteService $voteService)
    {
        $this->voteService = $voteService;
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
