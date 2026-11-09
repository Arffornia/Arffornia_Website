<?php

namespace App\Http\Controllers;

use App\Models\PendingReward;
use App\Services\RewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reward delivery for the game server: list a player's pending rewards, claim one idempotently, record the daily
 * login streak. Rewards are typed data (App\Rewards\RewardCatalog); the game server maps each type to a safe action.
 */
class RewardController extends Controller
{
    private const PLAYER_UUID_RULES = ['required', 'string', 'regex:/^[0-9a-f]{32}$/'];

    public function __construct(private readonly RewardService $rewardService)
    {
    }

    public function pending(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), ['player_uuid' => self::PLAYER_UUID_RULES]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $player = $this->rewardService->findPlayer($validator->validated()['player_uuid']);
        if ($player === null) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'rewards' => $this->rewardService->pendingFor($player)->map(fn (PendingReward $r) => $this->present($r))->values(),
        ]);
    }

    public function claim(Request $request, int $reward): JsonResponse
    {
        $validator = Validator::make(
            ['player_uuid' => $request->input('player_uuid'), 'idempotency_key' => $request->header('Idempotency-Key')],
            [
                'player_uuid' => self::PLAYER_UUID_RULES,
                'idempotency_key' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{16,64}$/'],
            ]
        );
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = $validator->validated();

        $player = $this->rewardService->findPlayer($data['player_uuid']);
        if ($player === null) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        [$outcome, $claimed] = $this->rewardService->claim($player, $reward, $data['idempotency_key']);

        return match ($outcome) {
            RewardService::CLAIM_OK, RewardService::CLAIM_REPLAYED => response()->json([
                'status' => $outcome,
                'reward' => $this->present($claimed),
            ]),
            RewardService::CLAIM_NOT_FOUND => response()->json(['message' => __('api.reward.not_found')], Response::HTTP_NOT_FOUND),
            RewardService::CLAIM_ALREADY_CLAIMED => response()->json(['message' => __('api.reward.already_claimed')], Response::HTTP_CONFLICT),
            default => response()->json(['message' => __('api.reward.not_claimable')], Response::HTTP_CONFLICT),
        };
    }

    public function dayStreak(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['player_uuid' => self::PLAYER_UUID_RULES]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $player = $this->rewardService->findPlayer($validator->validated()['player_uuid']);
        if ($player === null) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        return response()->json($this->rewardService->recordDailyConnection($player));
    }

    private function present(PendingReward $reward): array
    {
        return [
            'id' => $reward->id,
            'source' => $reward->source,
            'created_at' => $reward->created_at?->toIso8601String(),
            'contents' => $reward->contents,
        ];
    }
}
