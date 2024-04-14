<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', [HomeController::class, 'homeView']);
Route::get('/profile', [UserController::class, 'profileView']);
Route::get('/profile/{username}', [UserController::class, 'profileView']);


// Authentification
Route::get('/login', [UserController::class, 'loginView']);
Route::post('/login', [UserController::class, 'authenticateUser']);

Route::get('/register', [UserController::class, 'registerView']);
Route::post('/register', [UserController::class, 'createUser']);

Route::post('/profile', [UserController::class, 'logoutUser']);
