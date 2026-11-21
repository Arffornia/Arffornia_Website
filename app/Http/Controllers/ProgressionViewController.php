<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Contracts\View\View;

class ProgressionViewController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function catalogView(): View
    {
        $user = auth()->user();

        return $this->render($user?->name);
    }

    public function playerView(string $playerName): View
    {
        $user = $this->userService->getUserByName($playerName);
        abort_if($user === null, 404);

        return $this->render($user->name);
    }

    private function render(?string $playerName): View
    {
        abort_unless(config('progression.viewer') === 'fixture', 404);

        $fixture = asset('progression-fixture');

        return view('pages.progression', [
            'playerName' => $playerName,
            'config' => [
                'modelUrl' => "{$fixture}/model.json",
                'iconsUrl' => "{$fixture}/icons.json",
                'stateUrl' => $playerName !== null ? "{$fixture}/state-demo.json" : null,
                'iconBase' => config('progression.icon_base'),
                'locale' => app()->getLocale() === 'fr' ? 'fr' : 'en',
                'title' => $playerName,
            ],
        ]);
    }
}
