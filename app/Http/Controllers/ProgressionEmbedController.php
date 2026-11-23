<?php

namespace App\Http\Controllers;

use App\Progression\EmbedTokens;
use App\Progression\ProgressionStateService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The progression viewer inside the game (spec 018 D2 a): a page without site chrome, the token the
 * game server mints for a player, and the state that token reads.
 */
class ProgressionEmbedController extends Controller
{
    public function __construct(
        private readonly EmbedTokens $tokens,
        private readonly ProgressionStateService $states,
        private readonly UserService $users,
    ) {}

    /** The game server asks for a player's token with its service account. */
    public function issue(Request $request): JsonResponse
    {
        $validated = $request->validate(['player_uuid' => ['required', 'string', 'regex:/^[0-9a-f]{32}$/']]);
        $user = $this->users->getUserByUuid($validated['player_uuid']);
        if ($user === null) {
            return response()->json(['message' => __('api.player.not_found')], Response::HTTP_NOT_FOUND);
        }

        return response()->json([...$this->tokens->issue($user), 'url' => route('embed.progression')])
            ->header('Cache-Control', 'no-store');
    }

    public function page(Request $request): View
    {
        $mode = config('progression.viewer');
        abort_unless(in_array($mode, ['fixture', 'api'], true), 404);
        $locale = in_array($request->query('locale'), ['en', 'fr'], true) ? $request->query('locale') : (app()->getLocale() === 'fr' ? 'fr' : 'en');
        app()->setLocale($locale);
        $fixture = asset('progression-fixture');

        return view('embed.progression', [
            'config' => [
                'mode' => $mode,
                'modelUrl' => $mode === 'api' ? url('/api/progression/model') : "{$fixture}/model.json",
                'iconsUrl' => $mode === 'api' ? url('/icons/manifest.json') : "{$fixture}/icons.json",
                'iconBase' => $mode === 'api' ? url('/icons/128').'/' : config('progression.icon_base'),
                'stateUrl' => url('/api/embed/progression/state'),
                'locale' => $locale,
                'messages' => [
                    'no_model' => __('progression.no_model'),
                    'no_session' => __('progression.embed.no_session'),
                    'target_set' => __('progression.embed.target_set'),
                    'target_clear' => __('progression.embed.target_clear'),
                    'target_done' => __('progression.embed.target_done'),
                    'target_locked' => __('progression.embed.target_locked'),
                    'target_pending' => __('progression.embed.target_pending'),
                    'target_refused' => __('progression.embed.target_refused'),
                ],
            ],
        ]);
    }

    /** The state of the token holder's active progression, the same document as GET /api/progressions/{id}/state. */
    public function state(Request $request): JsonResponse
    {
        $user = $this->tokens->verify($request->bearerToken());
        if ($user === null) {
            return response()->json(['message' => __('api.auth.unauthenticated')], Response::HTTP_UNAUTHORIZED);
        }
        $progression = $user->activeProgression;
        $state = $progression === null ? null : $this->states->state($progression);
        if ($state === null) {
            return response()->json(['message' => __($progression === null ? 'api.progression.not_found' : 'api.progression.no_model')], Response::HTTP_NOT_FOUND);
        }

        return response()->json([...$state, 'player' => ['name' => $user->name]])->header('Cache-Control', 'no-store');
    }
}
