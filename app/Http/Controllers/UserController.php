<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stage;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Http\Response;

use App\Services\StagesService;
use Illuminate\Http\JsonResponse;


class UserController extends Controller
{
    private UserService $userService;
    private StagesService $stageService;

    public function __construct(UserService $userService, StagesService $stageService) {
        $this->userService = $userService;
        $this->stageService = $stageService;
    }

    // [API] Get player profil
    public function playerProfile(string $playerName) {
        $user = $this->userService->getUserByName($playerName);

        if($user) {
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

    public function playerProfileByUuid(string $playerUuid) {
        $user = $this->userService->getUserByUuid($playerUuid);

        if($user) {
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

    // [API] Check new player
    public function checkNewPlayer(string $playerUuid) {
        $playerUuid = $this->userService->getCleanPlayerUuid($playerUuid);
        $user = $this->userService->getUserByUuid($playerUuid);

        if(!$user) {
            // check if name and uuid is valid
            $pseudo = $this->userService->getPlayerNameFromUuid($playerUuid);
            if($pseudo) {
                $this->userService->createUser($pseudo, $playerUuid);
            }
        }
    }

    /*
        Login
    */

    public function loginView()
{
    if (request()->has(['code', 'state'])) {
        return $this->msAuthCallback();
    }

    return view('pages.users.login');
}


    /*
        Register
    */

    public function registerView() {
        return view('pages.users.register');
    }

    /*
        Log-out
    */

    public function logoutUser(Request $request) {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message', 'You have been logged out !');

    }

    /*
        Profile
    */

    public function profileView($username = null) {
        if($username) {
            $user = $this->userService->getUserByName($username);
        } else {
            if(!auth()->check()) {
                return redirect('/')->with('message', '⚠ You are not logged !');
            }

            $user = auth()->user();
        }
        if($user != null) {
            return view('pages.users.profile', [
                'user' => $user,
                'stage_number' => $this->stageService->getStageById($user->stage_id)->number,
            ]);
        }
    }

    public function msAuth()
    {
        return redirect()->away($this->userService->getMsAuthRedirectUrl());
    }

    public function msAuthCallback()
    {
        $user = $this->userService->getUserFromMsAuthCallback();

        if($user) {
            return redirect('/')->with('message', 'Welcome ' . $user->name . ' !');
        }


        // TODO add message with error
        return view('pages.users.login');

    }

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

    public function bestPlayerByPointJson($size): JsonResponse
    {
        $data = $this->bestPlayerByPoint($size);
        return response()->json($data);
    }
}
