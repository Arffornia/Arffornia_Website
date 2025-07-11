<?php

use App\Http\Controllers\LauncherController;
use App\Http\Controllers\ShopItemsController;
use App\Http\Controllers\StagesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('best_player_vote/{size}', [VoteController::class, 'bestPlayerByVoteJson']);
Route::get('best_player_point/{size}', [UserController::class, 'bestPlayerByPointJson']);

Route::get('profile/{playerName}', [UserController::class, 'playerProfile']); // Get the profile of a player
Route::get('profile/uuid/{playerUuid}/', [UserController::class, 'playerProfileByUuid']); // Get the profile of a player
Route::get('checkNewPlayer/{playerUuid}', [UserController::class, 'checkNewPlayer']);

Route::get('stages', [StagesController::class, 'stagesJson']);
Route::get('stages/player/get/{playerUuid}', [StagesController::class, 'playerStagesJson']);
Route::get('milestone/get/{nodeId}', [StagesController::class, 'getMilestoneById']);

Route::get('launcherVersionInfo/{dev?}', [LauncherController::class, 'getLauncherInfo'])->where('dev', 'dev');
Route::get('launcherImages', [LauncherController::class, 'getLauncherImages']);
Route::get('download/bootstrap', [LauncherController::class, 'downloadBootstrap']);
Route::get('download/launcher', [LauncherController::class, 'downloadLauncher']);

Route::get('shop/bestSallers/{size}', [ShopItemsController::class, 'bestSellersJson']);
Route::get('shop/newest/{size}', [ShopItemsController::class, 'newestJson']);
Route::get('shop/sales/{size}', [ShopItemsController::class, 'salesJson']);

// Get auth token routes:
Route::post('/auth/token/session', [UserController::class, 'getAuthTokenBySession'])->middleware('auth:sanctum');;
Route::post('/auth/token/ms', [UserController::class, 'getAuthTokenByMSAuth']);
Route::post('/auth/token/svc', [UserController::class, 'getAuthTokenBySvcAuth']);


Route::middleware(['auth:sanctum', 'anyRole:admin'])->group(function () {
    Route::post('stages/export', [StagesController::class, 'exportStages']);
    Route::put('milestones/{milestone}', [StagesController::class, 'updateMilestone']);

    Route::post('milestones', [StagesController::class, 'storeMilestone']);
    Route::delete('milestones/{milestone}', [StagesController::class, 'destroyMilestone']);

    Route::post('milestone-closures', [StagesController::class, 'storeLink']);
    Route::delete('milestone-closures', [StagesController::class, 'destroyLink']);
});
