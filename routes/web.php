<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\StagesController;
use App\Http\Controllers\AdminPanelManager;
use App\Http\Controllers\ShopItemsController;

// Language switcher (EN/FR), stores the choice in a cookie
Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

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

// Legal pages (text in lang/{en,fr}/legal.php). Bump the date when the text changes.
Route::view('/legal', 'pages.legal', ['page' => 'notice', 'updated' => '2026-10-31'])->name('legal.notice');
Route::view('/privacy', 'pages.legal', ['page' => 'privacy', 'updated' => '2026-10-31'])->name('legal.privacy');
Route::view('/terms', 'pages.legal', ['page' => 'terms', 'updated' => '2026-10-31'])->name('legal.terms');

// Player FAQ (text in lang/{en,fr}/faq.php)
Route::view('/faq', 'pages.faq')->name('faq');

// Stages
Route::get('stages', [StagesController::class, 'loadStagesView']);
Route::get('stages/{playerUuid}', [StagesController::class, 'loadPlayerStageView']);

// News
Route::get('news', [NewsController::class, 'allNewsView']);
Route::get('news/{newsId}', [NewsController::class, 'newsView']);

// Shop
Route::get('shop', [ShopController::class, 'shopView']);
Route::get('shop/{item}', [ShopController::class, 'shopItemView'])->name('shop.item');
Route::post('shop/buy/{item}', [ShopItemsController::class, 'buyItemWeb'])->middleware(['auth', 'throttle:shop-buy'])->name('shop.buy');

// Vote
Route::get('/vote', [VoteController::class, 'showVotePage'])->name('vote.page');
Route::post('/vote/verify', [VoteController::class, 'verifyVote'])->middleware(['auth', 'throttle:vote-verify'])->name('vote.verify');

// Authentification
Route::get('/login/MS-Auth', [UserController::class, 'msAuth']);
Route::get('/login/connect', [UserController::class, 'msAuthCallback']);

Route::get('/login', [UserController::class, 'msAuth'])->name('login');

Route::middleware(['auth', "anyRole:user"])->group(callback: function () {
    Route::post('/profile/logout', [UserController::class, 'logoutUser']);
});

// Admin
Route::middleware(['auth', "anyRole:admin"])->group(callback: function () {
    Route::get('/admin', [AdminPanelManager::class, 'adminPanelView'])->name("adminPanel");

    Route::get('/admin/launcherImages', [AdminPanelManager::class, 'launcherImagesView'])->name("launcherImages");
    Route::post('/admin/launcherImages', [AdminPanelManager::class, 'uploadNewLauncherImage'])->name("launcherImages.upload");
});
