<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', function () { return view('pages.home'); });

// Authentification
Route::get('/login', [UserController::class, 'loginView']);
Route::post('/login', [UserController::class, 'authenticateUser']);

Route::get('/register', [UserController::class, 'registerView']);
Route::post('/register', [UserController::class, 'createUser']);

Route::post('/logout', [UserController::class, 'logoutUser']);