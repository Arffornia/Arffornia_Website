<?php

namespace App\Http\Controllers;

use App\Models\Progression;
use App\Progression\PlayerStatsService;
use App\Progression\ProgressionStatsService;
use App\Progression\ProgressionVisibility;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/** Spec 019 statistics: the admin page, the player page and the player stats route. */
class ProgressionStatsController extends Controller
{
    public function __construct(
        private readonly ProgressionStatsService $adminStats,
        private readonly PlayerStatsService $playerStats,
        private readonly ProgressionVisibility $visibility,
        private readonly UserService $users,
    ) {
    }

    public function adminView(): View
    {
        return view('admin.progression_stats', ['stats' => $this->adminStats->stats()]);
    }

    /** A private progression answers 404, as a missing one. */
    public function playerView(string $playerName): View
    {
        $player = $this->users->getUserByName($playerName);
        $progression = $player?->activeProgression;
        abort_if($progression === null || !$this->visibility->canSee($progression), 404);

        return view('pages.progression_stats', [
            'playerName' => $player->name,
            'stats' => $this->playerStats->stats($progression),
        ]);
    }

    /** Same visibility as the stats page: a private team is 404 to anyone outside it but admins. */
    public function teamView(string $playerName): View
    {
        $player = $this->users->getUserByName($playerName);
        $progression = $player?->activeProgression;
        abort_if($progression === null || !$this->visibility->canSee($progression), 404);

        return view('pages.progression_team', [
            'playerName' => $player->name,
            'team' => $this->playerStats->team($progression),
        ]);
    }

    public function show(string $progression): JsonResponse
    {
        $found = Progression::find($progression);
        if ($found === null || !$this->visibility->canSee($found)) {
            return response()->json(['message' => __('api.progression.not_found')], Response::HTTP_NOT_FOUND);
        }
        $stats = $this->playerStats->stats($found);

        return $stats === null
            ? response()->json(['message' => __('api.progression.no_model')], Response::HTTP_NOT_FOUND)
            : response()->json($stats);
    }
}
