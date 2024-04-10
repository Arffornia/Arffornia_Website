<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{   

    // [API] Get player profil
    public function playerProfile($playerName) {
        $user = User::where('name', $playerName)->first();

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
        $formFields = $request->validate([
            'name' => ['required', 'min:3'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => 'required|confirmed|min:6'
        ]);

        // Hash Password
        $formFields['password'] = bcrypt($formFields['password']);

        // Create User
        $user = User::create($formFields);

        // Login
        auth()->login($user);

        return redirect('/')->with('message', 'User created and logged in');
    }

    /*
        Log-out
    */

    public function logout(Request $request) {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message', 'You have been logged out!');

    }
}
