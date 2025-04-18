<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Services\VoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;

class VoteController extends Controller
{
    private VoteService $voteService;
    private UserService $userService;


    public function __construct(VoteService $voteService,
                                UserService $userService)
    {
        $this->voteService = $voteService;
        $this->userService = $userService;
    }

    /**
     * Get size best players by vote
     *
     * @param int $size
     * @return Collection<User>
     */
    public function bestPlayerByVote($size): array
    {
        $topVoters = $this->userService->getTopVoters($size)
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'uuid' => $user->uuid,
                    'value' => $user->votes_count,
                ];
            });

        return $topVoters->toArray();
    }

    /**
     * Get size best players by vote as JSON
     *
     * @param [type] $size
     * @return JsonResponse
     */
    public function bestPlayerByVoteJson($size): JsonResponse
    {
        $data = $this->bestPlayerByVote($size);
        return response()->json($data);
    }
}
