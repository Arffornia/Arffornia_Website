<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stage;
use App\Services\StagesService;
use Illuminate\Http\Request;
use App\Services\UserService;

use Illuminate\Http\Response;
use Arffornia\MinecraftOauth\MinecraftOauth;
use Arffornia\MinecraftOauth\Exceptions\MinecraftOauthException;

class UserController extends Controller
{
    private UserService $userService;
    private StagesService $stageService;

    public function __construct(UserService $userService, StagesService $stageService) {
        $this->userService = $userService;
        $this->stageService = $stageService;
    }

    // [API] Get player profil
    public function playerProfile($playerName) {
        $user = $this->userService->getUserByName($playerName);

        if($user) {
            return response()->json([
                'money' => $user->money,
                'vote_count' => $user->getVoteCount(),
            ]);
        }

        return response()->json(['error' => 'player name not found.'], Response::HTTP_NOT_FOUND);
    }

    /*
        Login
    */

    public function loginView() {
        return view('pages.users.login');
    }

    public function authenticateUser(Request $request) {
        $formFields = $request->validate([
            'name' => 'required',
            'password' => 'required'
        ]);

        if(auth()->attempt($formFields)) {
            $request->session()->regenerate();

            return redirect('/')->with('message', 'You are now logged in!');
        }

        return back()->withErrors(['email' => 'Invalid Credentials'])->onlyInput('email');
    }

    /*
        Register
    */

    public function registerView() {
        return view('pages.users.register');
    }



    public function createUser(Request $request) {
        $startStageId = Stage::where('number', 1)->first()->id;

        $formFields = $request->validate([
            'name' => ['required', 'min:3'],
            'password' => 'required|confirmed|min:6'
        ]);

        $formFields['money'] = 0;
        $formFields['progress_point'] = 0;
        $formFields['stage_id'] = $startStageId;

        // Hash Password
        $formFields['password'] = bcrypt($formFields['password']);

        // Create User
        $user = User::create($formFields);

        // Login
        auth()->login($user);

        return redirect('/')->with('message', 'User created and logged in !');
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


        $clientId = env('AZURE_OAUTH_CLIENT_ID');
        $redirectUri = urlencode(env('AZURE_OAUTH_REDIRECT_URI'));

        $authUrl = "https://login.live.com/oauth20_authorize.srf?client_id=$clientId&response_type=code&redirect_uri=$redirectUri&scope=XboxLive.signin%20offline_access&state=NOT_NEEDED";

        return redirect()->away($authUrl);
    }

    public function msAuthCallback()
    {
        $clientId = env('AZURE_OAUTH_CLIENT_ID');
        $redirectUri = env('AZURE_OAUTH_REDIRECT_URI');
        $clientSecret = env('AZURE_OAUTH_CLIENT_SECRET');

        try {
            $profile = (new MinecraftOauth)->fetchProfile(
                $clientId,
                $clientSecret,
                $_GET['code'],
                $redirectUri,
            );

            dump('Minecraft UUID: ' . $profile->uuid());
            dump( 'Minecraft Username: ' . $profile->username());
            dump( 'Minecraft Skin URL: ' . $profile->skins()[0]->url());
            dump( 'Minecraft Cape URL: ' . $profile->capes()[0]->url());
        } catch (MinecraftOauthException $e) {
            dump( $e->getMessage());
        }
    }

    public function adminPanelView() {
        return view('admin.admin_panel');
    }
}
