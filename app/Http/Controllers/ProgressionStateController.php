<?php

namespace App\Http\Controllers;

use App\Models\Progression;
use App\Models\ServiceAccount;
use App\Models\User;
use App\Progression\Keys;
use App\Progression\ProgressionStateService;
use App\Progression\ProgressionStatsService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/** Spec 017: a progression's state, the events the game sends, and admin statistics. */
class ProgressionStateController extends Controller
{
    public function __construct(
        private readonly ProgressionStateService $states,
        private readonly ProgressionStatsService $stats,
        private readonly UserService $users,
    ) {
    }

    /** Public by default; a private progression is visible to its members, admins and the game server only. */
    public function show(Request $request, Progression $progression): JsonResponse
    {
        if (!$progression->is_public && !$this->canSeePrivate($request, $progression)) {
            return response()->json(['message' => __('api.progression.not_found')], Response::HTTP_NOT_FOUND);
        }
        $state = $this->states->state($progression);

        return $state === null
            ? response()->json(['message' => __('api.progression.no_model')], Response::HTTP_NOT_FOUND)
            : response()->json($state);
    }

    /** The state of a player's active progression, for the website's player page. */
    public function showForPlayer(Request $request, string $playerUuid): JsonResponse
    {
        $user = preg_match('/^[0-9a-f]{32}$/', $playerUuid) ? $this->users->getUserByUuid($playerUuid) : null;
        if ($user === null || $user->activeProgression === null) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        return $this->show($request, $user->activeProgression);
    }

    /** A member switches the progression between public and private. */
    public function visibility(Request $request, Progression $progression): JsonResponse
    {
        $validated = $request->validate(['public' => 'required|boolean']);
        $viewer = $request->user();
        if (!($viewer instanceof User) || (!$viewer->hasRole('admin') && !$this->isMember($viewer, $progression))) {
            return response()->json(['message' => __('api.forbidden')], Response::HTTP_FORBIDDEN);
        }

        $progression->is_public = (bool) $validated['public'];
        $progression->save();

        return response()->json(['progression_id' => $progression->id, 'public' => $progression->is_public]);
    }

    private function canSeePrivate(Request $request, Progression $progression): bool
    {
        $viewer = auth('sanctum')->user() ?? $request->user();
        if ($viewer instanceof ServiceAccount) {
            return $viewer->hasAnyRole(['admin', 'progression_editor']);
        }

        return $viewer instanceof User && ($viewer->hasRole('admin') || $this->isMember($viewer, $progression));
    }

    private function isMember(User $user, Progression $progression): bool
    {
        return (int) $user->active_progression_id === $progression->id || (int) $user->solo_progression_id === $progression->id;
    }

    public function events(Request $request, Progression $progression): JsonResponse
    {
        $snapshot = $this->states->snapshot();
        if ($snapshot === null) {
            return response()->json(['message' => __('api.progression.no_model')], Response::HTTP_CONFLICT);
        }

        $validated = $request->validate([
            'events' => 'required|array|min:1|max:100',
            'events.*.id' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{8,64}$/', 'distinct'],
            'events.*.type' => ['required', Rule::in([ProgressionStateService::EVENT_MILESTONE, ProgressionStateService::EVENT_MODULE])],
            'events.*.milestone' => ['required_if:events.*.type,' . ProgressionStateService::EVENT_MILESTONE, 'string', 'max:128', 'regex:' . Keys::MILESTONE_PATTERN],
            'events.*.stage' => ['required_if:events.*.type,' . ProgressionStateService::EVENT_MODULE, 'integer', 'min:1'],
            'events.*.item' => ['required_if:events.*.type,' . ProgressionStateService::EVENT_MODULE, 'string', 'max:191', 'regex:' . Keys::ITEM_PATTERN],
            'events.*.count' => ['required_if:events.*.type,' . ProgressionStateService::EVENT_MODULE, 'integer', 'min:1', 'max:1000000000'],
            'events.*.occurred_at' => ['sometimes', 'integer', 'min:1577836800000', 'max:' . (now()->addDay()->getTimestampMs())],
            'events.*.actor' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9a-f]{32}$/'],
        ]);

        $outcome = $this->states->apply($progression, $validated['events'], $snapshot);
        if ($outcome['retired']) {
            return response()->json(['message' => __('api.progression.retired')], Response::HTTP_CONFLICT);
        }

        return response()->json([
            'results' => $outcome['results'],
            'state' => $this->states->state($progression->refresh()),
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = $this->stats->stats();

        return $stats === null
            ? response()->json(['message' => __('api.progression.no_model')], Response::HTTP_NOT_FOUND)
            : response()->json($stats);
    }
}
