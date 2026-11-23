<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Progression\ProgressionVisibility;
use App\Services\UserService;
use Illuminate\Contracts\View\View;

class ProgressionViewController extends Controller
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    public function catalogView(): View
    {
        return $this->render(auth()->user());
    }

    public function playerView(string $playerName): View
    {
        $user = $this->userService->getUserByName($playerName);
        abort_if($user === null, 404);

        return $this->render($user);
    }

    private function render(?User $player): View
    {
        $mode = config('progression.viewer');
        abort_unless(in_array($mode, ['fixture', 'api'], true), 404);
        $fixture = asset('progression-fixture');

        return view('pages.progression', [
            'playerName' => $player?->name,
            'mode' => $mode,
            'privacy' => $this->privacy($player),
            'statsUrl' => $this->statsUrl($player),
            'config' => [
                'mode' => $mode,
                'modelUrl' => $mode === 'api' ? url('/api/progression/model') : "{$fixture}/model.json",
                'iconsUrl' => $mode === 'api' ? url('/icons/manifest.json') : "{$fixture}/icons.json",
                'stateUrl' => match (true) {
                    $player === null => null,
                    $mode === 'api' => url("/api/players/{$player->uuid}/progression/state"),
                    default => "{$fixture}/state-demo.json",
                },
                'iconBase' => $mode === 'api' ? url('/icons/128').'/' : config('progression.icon_base'),
                'locale' => app()->getLocale() === 'fr' ? 'fr' : 'en',
                'title' => $player?->name,
                'messages' => [
                    'private' => __('progression.private'),
                    'no_model' => __('progression.no_model'),
                    'next_title' => __('progression.next_title'),
                    'next_empty' => __('progression.next_empty'),
                    'privacy_failed' => __('progression.privacy_failed'),
                ],
                'notice' => match (true) {
                    auth()->guest() => ['text' => __('progression.login_hint'), 'action' => ['label' => __('progression.login_cta'), 'href' => route('login')]],
                    $mode === 'fixture' => ['text' => __('progression.preview')],
                    default => null,
                },
            ],
        ]);
    }

    /** Hidden where the stats page would answer 404, so the link never leads to one. */
    private function statsUrl(?User $player): ?string
    {
        $progression = $player?->activeProgression;
        if ($progression === null || !app(ProgressionVisibility::class)->canSee($progression)) {
            return null;
        }

        return route('progression.player.stats', ['playerName' => $player->name]);
    }

    /**
     * The public/private switch (spec 019 Q4), shown only to the members of the player's active
     * progression and to admins: the ones the visibility route accepts.
     *
     * @return array{url: string, public: bool}|null
     */
    private function privacy(?User $player): ?array
    {
        $progression = $player?->activeProgression;
        $viewer = auth()->user();
        if ($progression === null || !($viewer instanceof User)) {
            return null;
        }
        if (!$viewer->hasRole('admin') && !ProgressionVisibility::isMember($viewer, $progression)) {
            return null;
        }

        return ['url' => url("/api/progressions/{$progression->id}/visibility"), 'public' => $progression->is_public !== false];
    }
}
