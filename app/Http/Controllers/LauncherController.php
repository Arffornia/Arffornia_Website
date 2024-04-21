<?php

namespace App\Http\Controllers;

use App\Services\LauncherService;


class LauncherController extends Controller
{
    private LauncherService $launcherService;

    public function __construct(LauncherService $launcherService) {
        $this->launcherService = $launcherService;
    }

    public function getLauncherInfo($dev = null) {
        return response()->json($this->launcherService->getLauncherHash($dev === 'dev'));
    }

    public function getLauncherImages() {
        return response()->json($this->launcherService->getLauncherImages());
    }
}
