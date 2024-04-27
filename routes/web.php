<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StagesController;

// Home
Route::get('/', [HomeController::class, 'homeView']);

// Profile
Route::get('/profile', [UserController::class, 'profileView']);
Route::get('/profile/{username}', [UserController::class, 'profileView']);

// rules
Route::get('/reglement', function() { return view('pages.reglement'); });

// Stages
Route::get('stages', [StagesController::class, 'loadStagesView']);
Route::get('stages/{playerName}', [StagesController::class, 'loadPlayerStageView']);

// News
Route::get('news', [NewsController::class, 'allNewsView']);
Route::get('news/{newsId}', [NewsController::class, 'newsView']);


// Authentification
Route::get('/msLogin', [UserController::class, 'msAuth']);
Route::get('/connect', [UserController::class, 'msAuthCallback']);

Route::get('/login', [UserController::class, 'loginView']);
Route::post('/login', [UserController::class, 'authenticateUser']);

Route::get('/register', [UserController::class, 'registerView']);
Route::post('/register', [UserController::class, 'createUser']);

Route::post('/profile', [UserController::class, 'logoutUser']);


// Admin
Route::group(['middleware'=> ['admin']], function() {
    Route::get('/admin', [UserController::class,'adminPanelView']);
});

