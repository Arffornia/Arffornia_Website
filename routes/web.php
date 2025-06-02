<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StagesController;
use App\Http\Controllers\AdminPanelManager;
use App\Http\Controllers\ShopController;

// Home
Route::get('/', [HomeController::class, 'homeView']);

// Profile
Route::get('/profile', [UserController::class, 'profileView']);
Route::get('/profile/{playerName}', [UserController::class, 'profileViewByName']);
Route::get('/profile/uuid/{playerUuid}', [UserController::class, 'profileViewByUuid']);

// rules
Route::get('/reglement', function () {
    return view('pages.reglement');
});

// Stages
Route::get('stages', [StagesController::class, 'loadStagesView']);
Route::get('stages/{playerUuid}', [StagesController::class, 'loadPlayerStageView']);

// News
Route::get('news', [NewsController::class, 'allNewsView']);
Route::get('news/{newsId}', [NewsController::class, 'newsView']);

// Shop
Route::get('shop', [ShopController::class, 'shopView']);


// Authentification
Route::get('/login/MS-Auth', [UserController::class, 'msAuth']);
Route::get('/login/connect', [UserController::class, 'msAuthCallback']);

Route::get('/login', [UserController::class, 'loginView'])->name('login');

Route::post('/profile/logout', [UserController::class, 'logoutUser']);

// Admin
Route::middleware(['auth', "admin"])->group(function () {
    Route::get('/admin', [AdminPanelManager::class, 'adminPanelView'])->name("adminPanel");

    Route::get('/admin/launcherVersions', [AdminPanelManager::class, 'launcherVersionsView'])->name("launcherVersions");
    Route::post('/admin/launcherVersions', [AdminPanelManager::class, 'uploadNewLauncherVersion'])->name("launcherVersions.upload");

    Route::get('/admin/launcherImages', [AdminPanelManager::class, 'launcherImagesView'])->name("launcherImages");
    Route::post('/admin/launcherImages', [AdminPanelManager::class, 'uploadNewLauncherImage'])->name("launcherImages.upload");
});
