<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Progression\ItemIcons;
use App\Progression\SnapshotStore;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class ProgressionViewController extends Controller
{
    public function __construct(
        private UserService $userService,
        private SnapshotStore $snapshots,
        private ItemIcons $icons,
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

    /** Item renders of the published snapshot, for the viewer in api mode. */
    public function icons(): JsonResponse
    {
        abort_unless(config('progression.viewer') === 'api', 404);
        $snapshot = $this->snapshots->latest();
        abort_if($snapshot === null, 404);

        return response()->json($this->icons->forSnapshot($snapshot->version, $snapshot->etag, json_decode($snapshot->document, true)))
            ->setPublic()->setMaxAge(300)->setEtag($snapshot->etag);
    }

    private function render(?User $player): View
    {
        $mode = config('progression.viewer');
        abort_unless(in_array($mode, ['fixture', 'api'], true), 404);
        $fixture = asset('progression-fixture');

        return view('pages.progression', [
            'playerName' => $player?->name,
            'mode' => $mode,
            'config' => [
                'mode' => $mode,
                'modelUrl' => $mode === 'api' ? url('/api/progression/model') : "{$fixture}/model.json",
                'iconsUrl' => $mode === 'api' ? route('progression.icons') : "{$fixture}/icons.json",
                'stateUrl' => match (true) {
                    $player === null => null,
                    $mode === 'api' => url("/api/players/{$player->uuid}/progression/state"),
                    default => "{$fixture}/state-demo.json",
                },
                'iconBase' => config('progression.icon_base'),
                'locale' => app()->getLocale() === 'fr' ? 'fr' : 'en',
                'title' => $player?->name,
                'messages' => [
                    'private' => __('progression.private'),
                    'no_model' => __('progression.no_model'),
                ],
                'notice' => match (true) {
                    auth()->guest() => ['text' => __('progression.login_hint'), 'action' => ['label' => __('progression.login_cta'), 'href' => route('login')]],
                    $mode === 'fixture' => ['text' => __('progression.preview')],
                    default => null,
                },
            ],
        ]);
    }
}
