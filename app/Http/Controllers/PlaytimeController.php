<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/** Daily playtime per player, sent by the game server (spec 019 Q5) for fair stage-time statistics. */
class PlaytimeController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    /** The game sends the day's running total; a late or replayed report never lowers it. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_uuid' => ['required', 'string', 'regex:/^[0-9a-f]{32}$/'],
            'day' => ['required', 'date_format:Y-m-d', 'after_or_equal:2020-01-01', 'before_or_equal:' . now()->addDay()->toDateString()],
            'seconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ]);

        $user = $this->users->getUserByUuid($validated['player_uuid']);
        if ($user === null) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        $row = ['user_id' => $user->id, 'day' => $validated['day']];
        DB::transaction(function () use ($row, $validated) {
            DB::table('player_playtime')->insertOrIgnore([...$row, 'seconds' => 0]);
            DB::table('player_playtime')->where($row)->where('seconds', '<', $validated['seconds'])->update(['seconds' => $validated['seconds']]);
        });

        return response()->json([
            'day' => $validated['day'],
            'seconds' => (int) DB::table('player_playtime')->where($row)->value('seconds'),
        ]);
    }
}
