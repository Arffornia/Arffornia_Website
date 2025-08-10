<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\StagesController;
use App\Http\Controllers\LauncherController;
use App\Http\Controllers\ShopItemsController;
use App\Http\Controllers\ProgressionController;
use App\Http\Controllers\AdminPanelManager;

Route::get('best_player_vote/{size}', [VoteController::class, 'bestPlayerByVoteJson']);
Route::get('best_player_point/{size}', [UserController::class, 'bestPlayerByPointJson']);

Route::get('profile/{playerName}', [UserController::class, 'playerProfile']);
Route::get('profile/uuid/{playerUuid}/', [UserController::class, 'playerProfileByUuid']);
Route::get('checkNewPlayer/{playerUuid}', [UserController::class, 'checkNewPlayer']);

Route::get('stages', [StagesController::class, 'stagesJson']);
Route::get('stages/player/get/{playerUuid}', [StagesController::class, 'playerStagesJson']);
Route::get('milestone/get/{milestone}', [StagesController::class, 'getMilestoneById']);

Route::get('progression/config', [StagesController::class, 'getProgressionConfigForMod']);
Route::get('progression/{progression}', [ProgressionController::class, 'getProgressionById']);

Route::get('launcherImages', [LauncherController::class, 'getLauncherImages']);

// Shop
Route::get('shop/bestSallers/{size}', [ShopItemsController::class, 'bestSellersJson']);
Route::get('shop/newest/{size}', [ShopItemsController::class, 'newestJson']);
Route::get('shop/sales/{size}', [ShopItemsController::class, 'salesJson']);
Route::get('shop/item/{item}', [ShopItemsController::class, 'itemDetailsJson']);
Route::post('shop/buy/{item}', [ShopItemsController::class, 'buyItem'])->middleware('auth:sanctum');

// Get auth token routes:
Route::post('/auth/token/session', [UserController::class, 'getAuthTokenBySession'])->middleware('auth:sanctum');;
Route::post('/auth/token/ms', [UserController::class, 'getAuthTokenByMSAuth']);
Route::post('/auth/token/svc', [UserController::class, 'getAuthTokenBySvcCredentials']);

// Recipes
Route::get('recipes', [RecipeController::class, 'index']);
Route::get('recipes/type/{type}', [RecipeController::class, 'showByType']);

Route::middleware(['auth:sanctum', 'anyRole:admin'])->group(function () {
    Route::post('stages/export', [StagesController::class, 'exportStages']);

    // Stage Management
    Route::post('stages', [StagesController::class, 'storeStage']);
    Route::delete('stages/{stage}', [StagesController::class, 'destroyStage']);

    // Milestone Management
    Route::put('milestones/{milestone}', [StagesController::class, 'updateMilestone']);
    Route::post('milestones', [StagesController::class, 'storeMilestone']);
    Route::delete('milestones/{milestone}', [StagesController::class, 'destroyMilestone']);

    Route::post('milestone-closures', [StagesController::class, 'storeLink']);
    Route::delete('milestone-closures', [StagesController::class, 'destroyLink']);
    Route::put('milestones/{milestone}/position', [StagesController::class, 'updateMilestonePosition']);

    // Unlock Milestones managment
    Route::post('milestones/{milestone}/unlocks', [StagesController::class, 'storeUnlock']);
    Route::put('unlocks/{unlock}', [StagesController::class, 'updateUnlock']);
    Route::delete('unlocks/{unlock}', [StagesController::class, 'destroyUnlock']);

    // Required Milestones managment
    Route::post('milestones/{milestone}/requirements', [StagesController::class, 'storeRequirement']);
    Route::put('requirements/{requirement}', [StagesController::class, 'updateRequirement']);
    Route::delete('requirements/{requirement}', [StagesController::class, 'destroyRequirement']);

    // Recipe Management
    Route::post('unlocks/{unlock}/recipe', [RecipeController::class, 'storeOrUpdate']);

    // Launcher images Management
    Route::put('launcherImages/{image}/toggle-prod', [AdminPanelManager::class, 'toggleProdStatus']);
});

Route::middleware(['auth:sanctum', 'anyRole:admin,team_editor'])->group(function () {
    Route::post('/teams/create', [TeamController::class, 'create']);
    Route::post('/teams/player/join', [TeamController::class, 'playerJoin']);
    Route::post('/teams/player/leave', [TeamController::class, 'playerLeave']);
    Route::post('/teams/disband', [TeamController::class, 'disband']);
});

Route::middleware(['auth:sanctum', 'anyRole:admin,progression_editor'])->group(function () {
    Route::post('/progression/add', [ProgressionController::class, 'addMilestone']);
    Route::post('/progression/remove', [ProgressionController::class, 'removeMilestone']);
    Route::post('/progression/list', [ProgressionController::class, 'listMilestones']);

    Route::post('/progression/set-target', [ProgressionController::class, 'setTargetMilestone']);
});


Route::middleware(['auth:sanctum', 'anyRole:admin,user_editor'])->group(function () {
    Route::post('/player/ensure-exists', [UserController::class, 'ensurePlayerExists']);
});
