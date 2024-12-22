<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Services\LauncherService;
use Illuminate\Contracts\Routing\ResponseFactory;


class LauncherController extends Controller
{
    private LauncherService $launcherService;

    public function __construct(LauncherService $launcherService) {
        $this->launcherService = $launcherService;
    }

    /**
     * [Obselete] Return the current (or dev) launcher version
     *
     * @param string $dev
     * @return JsonResponse
     */
    public function getLauncherInfo($dev = null) {
        return response()->json($this->launcherService->getLauncherInfo($dev === 'dev'));
    }

    /**
     * Get deployed launcher images
     *
     * @return JsonResponse
     */
    public function getLauncherImages() {
        return response()->json($this->launcherService->getLauncherImages());
    }

    /**
     * !!TODO Get launcher bootstrap
     *
     * @return void
     */
    public function downloadBootstrap() {
    }

    /**
     * [Obselete] Get the currrent launcher . jar
     *
     * @return JsonResponse
     */
    public function downloadLauncher() {
        return response()->download(public_path("files/ArfforniaLauncher.jar"));
    }
}
