<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{   
    public function getUserByName($username) {
        return User::where('name', $username)->first();
    }


    // [API] Get player profil
    public function playerProfile($playerName) {
        $user = $this->getUserByName($playerName);

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
        $startStageId = Stage::where('number', 1)->first();

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
            $user = $this->getUserByName($username);
        } else {
            if(!auth()->check()) {
                return redirect('/')->with('message', '⚠ You are not logged !');
            }
    
            $user = auth()->user();
        }
        
        if($user != null) {
            return view('pages.users.profile', [
                'user' => $user,
                'stage_number' => Stage::where('id', $user->stage_id)->first()->number
            ]);
        }
    }
}
