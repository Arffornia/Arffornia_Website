<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stage;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Http\Response;

use App\Models\ServiceAccount;
use App\Services\StagesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Routing\ResponseFactory;
use Arffornia\MinecraftOauth\Exceptions\MinecraftOauthException;



class UserController extends Controller
{
    private UserService $userService;
    private StagesService $stageService;

    public function __construct(UserService $userService, StagesService $stageService)
    {
        $this->userService = $userService;
        $this->stageService = $stageService;
    }

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
                'active_progression_id' => $user->active_progression_id,
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
                'active_progression_id' => $user->active_progression_id,
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
            abort(404, 'User not found.');
        }

        $user->load('activeProgression.maxStage');

        return view('pages.users.profile', [
            'user' => $user,
            'stage_number' => $user->activeProgression->maxStage->number ?? 1,
            'current_milestone_name' => $user->activeProgression->currentTargetedMilestone->name ?? 'None',
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

    /**
     * Login the User with the MS OAuth UI Flow
     *
     * @return RedirectResponse|View
     */
    public function msAuthCallback()
    {
        try {
            $user = $this->userService->getUserFromMsAuthCallback();

            if ($user) {
                return redirect('/')->with('message', 'Welcome ' . $user->name . ' !');
            }


            // TODO add message with error
            return view('pages.users.login');
        } catch (MinecraftOauthException $e) {
            dump($e->getMessage());

            /*
                TODO:

                Add a flash message, with e getmessage
            */
            abort(401, 'Authentication failed. Please try again.');
        }
    }

    /**
     * Get auth token using a Microsoft access_token
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAuthTokenByMSAuth(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        try {
            // Validate the access token and get Mojang user
            $user = $this->userService->getUserWithAccessToken($request->input('access_token'));

            if (!$user) {
                return response()->json(['message' => 'Not authenticated'], 401);
            }

            $token = $this->genApiTokenWithUserScope($user);

            return response()->json(['token' => $token]);
        } catch (MinecraftOauthException) {
            return response()->json(['message' => 'Authentication failed. Please try again.'], 401);
        }
    }

    /**
     * Get auth token using the current user session (laravel session)
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAuthTokenBySession(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = $this->genApiTokenWithUserScope($user);

        return response()->json(['token' => $token]);
    }

    /**
     * Get auth token using service account credentials.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAuthTokenBySvcCredentials(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => 'required|string|uuid',
            'client_secret' => 'required|string',
        ]);

        $serviceAccount = ServiceAccount::where('client_id', $request->input('client_id'))->first();

        if (!$serviceAccount || !Hash::check($request->input('client_secret'), $serviceAccount->client_secret)) {
            return response()->json(['message' => 'Invalid service account credentials'], 401);
        }

        $token = $serviceAccount->createToken(
            $serviceAccount->name,
            $serviceAccount->getRoles()
        )->plainTextToken;

        return response()->json(['token' => $token]);
    }


    /**
     * Generate the API token with User's scope
     * @param \App\Models\User $user
     * @return string
     */
    private function genApiTokenWithUserScope(User $user): string
    {
        return $user->createToken('api-token', $user->getRoles())->plainTextToken;
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

    /**
     * Ensures a player exists in the database. If not, it creates them.
     * This is called by the Minecraft server on player join.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ensurePlayerExists(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|string|size:32',
            'username' => 'required|string|max:16',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();
        $cleanUuid = $this->userService->getCleanPlayerUuid($data['uuid']);

        $user = $this->userService->getUserByUuid($cleanUuid);
        $username = $data['username'];

        if ($user) {
            if ($user->name !== $username) {
                $user->name = $username;
                $user->save();

                return response()->json(['status' => 'updated']);
            }

            return response()->json(['status' => 'exists']);
        }

        try {
            $this->userService->createUser($username, $cleanUuid);
            Log::info("User created on first join: " . $username);
            return response()->json(['status' => 'created'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error("Failed to create user on first join for UUID {$cleanUuid}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create user.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
