<?php

use App\Http\Controllers\LauncherController;
use App\Http\Controllers\StagesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/arffornia_v5/modlist', function () {
    return response()->file(public_path('files/ArfforniaV.5ModList.json'));
});

Route::get('best_player_vote/{size}', [VoteController::class, 'bestPlayerByVoteJson']);
Route::get('profile/{playerName}', [UserController::class, 'playerProfile']);

Route::get('stages', [StagesController::class, 'stagesJson']);
Route::get('stages/{playerName}', [StagesController::class, 'playerStagesJson']);

Route::get('launcherVersionInfo/{dev?}', [LauncherController::class,'getLauncherInfo'])->where('dev', 'dev');
Route::get('launcherImages', [LauncherController::class,'getLauncherImages']);
