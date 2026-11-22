<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeDatapackController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\StagesController;
use App\Http\Controllers\AdminPanelManager;
use App\Http\Controllers\LauncherController;
use App\Http\Controllers\MigrationController;
use App\Http\Controllers\ShopItemsController;
use App\Http\Controllers\ProgressionController;
use App\Http\Controllers\ProgressionDraftController;
use App\Http\Controllers\ProgressionModelController;
use App\Http\Controllers\ProgressionStateController;
use App\Http\Controllers\PlaytimeController;

Route::get('best_player_point/{size}', [UserController::class, 'bestPlayerByPointJson']);
Route::get('best_player_vote/{size}', [VoteController::class, 'bestPlayerByVoteJson']);
Route::get('best_player_vote/all-time/{size}', [VoteController::class, 'bestPlayerByVoteAllTimeJson']);

Route::get('profile/{playerName}', [UserController::class, 'playerProfile']);
Route::get('profile/uuid/{playerUuid}/', [UserController::class, 'playerProfileByUuid']);
Route::get('checkNewPlayer/{playerUuid}', [UserController::class, 'checkNewPlayer']);

Route::get('stages', [StagesController::class, 'stagesJson']);
Route::get('stages/player/get/{playerUuid}', [StagesController::class, 'playerStagesJson']);
Route::get('milestone/get/{milestone}', [StagesController::class, 'getMilestoneById']);

Route::get('progression/config', [StagesController::class, 'getProgressionConfigForMod']);
Route::get('progression/datapack', [RecipeDatapackController::class, 'show'])->middleware('throttle:recipe-datapack');
Route::get('progression/model', [ProgressionModelController::class, 'show']);
Route::get('progression/{progression}', [ProgressionController::class, 'getProgressionById'])->whereNumber('progression');

// Progression model and state (spec 017)
Route::get('progressions/{progression}/state', [ProgressionStateController::class, 'show'])->whereNumber('progression');
Route::get('players/{playerUuid}/progression/state', [ProgressionStateController::class, 'showForPlayer']);
Route::put('progressions/{progression}/visibility', [ProgressionStateController::class, 'visibility'])->whereNumber('progression')->middleware('auth:sanctum');

Route::get('launcherImages', [LauncherController::class, 'getLauncherImages']);

// Shop
Route::get('shop/bestSallers/{size}', [ShopItemsController::class, 'bestSellersJson']);
Route::get('shop/newest/{size}', [ShopItemsController::class, 'newestJson']);
Route::get('shop/sales/{size}', [ShopItemsController::class, 'salesJson']);
Route::get('shop/item/{item}', [ShopItemsController::class, 'itemDetailsJson']);

// Get auth token routes:
Route::post('/auth/token/session', [UserController::class, 'getAuthTokenBySession'])->middleware(['auth:sanctum', 'throttle:session-token']);
Route::post('/auth/token/ms', [UserController::class, 'getAuthTokenByMSAuth'])->middleware('throttle:auth-token');
Route::post('/auth/token/svc', [UserController::class, 'getAuthTokenBySvcCredentials'])->middleware('throttle:auth-token');

// Recipes
Route::get('recipes', [RecipeController::class, 'index']);
Route::get('recipes/type/{type}', [RecipeController::class, 'showByType']);

Route::middleware(['auth:sanctum', 'anyRole:admin'])->group(function () {
    Route::post('stages/export', [StagesController::class, 'exportStages']);
    Route::post('stages/import', [StagesController::class, 'importStages']);

    // Graph Management
    Route::post('graphs', [StagesController::class, 'storeGraph']);
    Route::put('graphs/{graph}', [StagesController::class, 'updateGraph']);
    Route::delete('graphs/{graph}', [StagesController::class, 'destroyGraph']);

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

    // Progression draft and publish (spec 017); one working draft for now, addressed as "current"
    Route::get('progression/drafts/current', [ProgressionModelController::class, 'draft']);
    Route::put('progression/drafts/current', [ProgressionModelController::class, 'replaceDraft']);
    Route::post('progression/drafts/current/validate', [ProgressionModelController::class, 'validateDraft']);
    Route::post('progression/drafts/current/publish', [ProgressionModelController::class, 'publish']);
    Route::get('stats/progression', [ProgressionStateController::class, 'stats']);

    // Admin editor drafts (spec 019), separate from the table draft above until the old editor is removed
    Route::post('progression/drafts', [ProgressionDraftController::class, 'open']);
    Route::get('progression/drafts/{draft}', [ProgressionDraftController::class, 'show'])->whereNumber('draft');
    Route::post('progression/drafts/{draft}/actions', [ProgressionDraftController::class, 'act'])->whereNumber('draft');
    Route::put('progression/drafts/{draft}/document', [ProgressionDraftController::class, 'import'])->whereNumber('draft');
    Route::get('progression/drafts/{draft}/document', [ProgressionDraftController::class, 'export'])->whereNumber('draft');
    Route::post('progression/drafts/{draft}/discard', [ProgressionDraftController::class, 'discard'])->whereNumber('draft');

    // Launcher images Management
    Route::put('launcherImages/{image}/toggle-prod', [AdminPanelManager::class, 'toggleProdStatus']);
});

Route::middleware(['auth:sanctum', 'anyRole:nuvotifier_service'])->group(function () {
    Route::post('/vote/nuvotifier', [VoteController::class, 'handleNuvotifierVote']);
});

Route::middleware(['auth:sanctum', 'anyRole:admin,reward_service'])->group(function () {
    Route::get('/rewards/pending', [RewardController::class, 'pending']);
    Route::post('/rewards/{reward}/claim', [RewardController::class, 'claim'])->where('reward', '[0-9]{1,18}');
    Route::post('/rewards/day-streak', [RewardController::class, 'dayStreak']);
});

Route::middleware(['auth:sanctum', 'anyRole:admin,team_editor'])->group(function () {
    Route::put('/teams/player/sync', [TeamController::class, 'playerSync']);
    Route::post('/teams/player/join', [TeamController::class, 'playerJoin']);
    Route::post('/teams/player/leave', [TeamController::class, 'playerLeave']);
    Route::post('/teams/disband', [TeamController::class, 'disband']);
});

Route::middleware(['auth:sanctum', 'anyRole:admin,progression_editor'])->group(function () {
    Route::post('/progression/add', [ProgressionController::class, 'addMilestone']);
    Route::post('/progression/remove', [ProgressionController::class, 'removeMilestone']);
    Route::post('/progression/list', [ProgressionController::class, 'listMilestones']);

    Route::post('/progression/set-target', [ProgressionController::class, 'setTargetMilestone']);

    Route::post('milestones/{milestone}/add-unlock-from-game', [StagesController::class, 'storeUnlockFromGame']);

    Route::get('/migration/items-to-migrate', [MigrationController::class, 'getItemsToMigrate']);
    Route::post('/migration/submit-batch-recipes', [MigrationController::class, 'submitBatchRecipes']);

    Route::post('milestones/{milestone}/set-requirements', [StagesController::class, 'setRequirementsFromGame']);

    Route::post('/milestone-items/move', [StagesController::class, 'moveMilestoneItem']);

    Route::post('progressions/{progression}/events', [ProgressionStateController::class, 'events'])->whereNumber('progression');
    Route::post('players/playtime', [PlaytimeController::class, 'store']);
});


Route::middleware(['auth:sanctum', 'anyRole:admin,user_editor'])->group(function () {
    Route::post('/player/ensure-exists', [UserController::class, 'ensurePlayerExists']);
});
