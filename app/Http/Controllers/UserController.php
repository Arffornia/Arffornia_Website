<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stage;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Http\Response;

use App\Services\StagesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Routing\ResponseFactory;


class UserController extends Controller
{
    private UserService $userService;
    private StagesService $stageService;

    public function __construct(UserService $userService, StagesService $stageService)
    {
        $this->userService = $userService;
        $this->stageService = $stageService;
    }

    // [API] Get player profil
    /**
     * Get player profile information
     *
     * @param string $playerName
     * @return JsonResponse
     */
    public function playerProfile(string $playerName)
    {
        $user = $this->userService->getUserByName($playerName);

        if ($user) {
            return response()->json([
                'name' => $user->name,
                'uuid' => $user->uuid,
                'money' => $user->money,
                'grade' => $user->grade,
                'day_streak' => $user->day_streak,
                'vote_count' => $user->getVoteCount(),

            ]);
        }

        return response()->json(['error' => 'player name not found.'], Response::HTTP_NOT_FOUND);
    }

    /**
     * Get player profile information
     *
     * @param string $playerUuid
     * @return JsonResponse
     */
    public function playerProfileByUuid(string $playerUuid)
    {
        $user = $this->userService->getUserByUuid($playerUuid);

        if ($user) {
            return response()->json([
                'name' => $user->name,
                'uuid' => $user->uuid,
                'money' => $user->money,
                'grade' => $user->grade,
                'day_streak' => $user->day_streak,
                'vote_count' => $user->getVoteCount(),
            ]);
        }

        return response()->json(['error' => 'player name not found.'], Response::HTTP_NOT_FOUND);
    }

    /**
     * Check new player
     * Update the player name if it change
     *
     * @param string $playerUuid
     * @return void
     */
    public function checkNewPlayer(string $playerUuid)
    {
        $playerUuid = $this->userService->getCleanPlayerUuid($playerUuid);
        $user = $this->userService->getUserByUuid($playerUuid);

        if (!$user) {
            // check if name and uuid is valid
            $pseudo = $this->userService->getPlayerNameFromUuid($playerUuid);
            if ($pseudo) {
                $this->userService->createUser($pseudo, $playerUuid);
            }
        }
    }


    /**
     * Load the loading view
     *
     * @return View
     */
    public function loginView()
    {
        if (request()->has(['code', 'state'])) {
            return $this->msAuthCallback();
        }

        return view('pages.users.login');
    }

    /**
     * Log out the user and redirect the user to the home page
     *
     * @param Request $request
     * @return void
     */
    public function logoutUser(Request $request)
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message', 'You have been logged out !');
    }

    /**
     * Load the profile view
     *
     * @param mixed $user
     * @return View
     */
    public function profileView($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            abort(404);
        }

        return view('pages.users.profile', [
            'user' => $user,
            'stage_number' => $this->stageService->getStageById($user->stage_id)->number,
        ]);
    }

    /**
     * Load the profile view of a specific player
     *
     * @param string $playerName
     * @return View
     */
    public function profileViewByName($playerName)
    {
        $user = $this->userService->getUserByName($playerName);
        return $this->profileView($user);
    }

    /**
     * Load the profile view of a specific player
     *
     * @param int $playerUuid
     * @return View
     */
    public function profileViewByUuid($playerUuid)
    {
        $user = $this->userService->getUserByUuid($playerUuid);
        return $this->profileView($user);
    }

    /**
     * Redirect the user to the MS auth
     *
     * @return RedirectResponse
     */
    public function msAuth()
    {
        return redirect()->away($this->userService->getMsAuthRedirectUrl());
    }

    public function msAuthCallback()
    {
        $user = $this->userService->getUserFromMsAuthCallback();

        if ($user) {
            return redirect('/')->with('message', 'Welcome ' . $user->name . ' !');
        }


        // TODO add message with error
        return view('pages.users.login');
    }

    /**
     * Get size best player by points
     *
     * @param int $size
     * @return Collection<User>
     */
    public function bestPlayerByPoint($size): array
    {
        $topUsersByPoint = $this->userService->getTopUsersByPoint($size)
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'uuid' => $user->uuid,
                    'value' => $user->progress_point,
                ];
            });

        return $topUsersByPoint->toArray();
    }

    /**
     * Get size best player by points as JSON
     *
     * @param int $size
     * @return JsonResponse
     */
    public function bestPlayerByPointJson($size): JsonResponse
    {
        $data = $this->bestPlayerByPoint($size);
        return response()->json($data);
    }
}
