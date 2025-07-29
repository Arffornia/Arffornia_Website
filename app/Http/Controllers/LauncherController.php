<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Services\LauncherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Routing\ResponseFactory;


class LauncherController extends Controller
{
    private LauncherService $launcherService;

    public function __construct(LauncherService $launcherService)
    {
        $this->launcherService = $launcherService;
    }

    /**
     * Get deployed launcher images
     *
     * @return JsonResponse
     */
    public function getLauncherImages()
    {
        return response()->json($this->launcherService->getLauncherImages());
    }
}
