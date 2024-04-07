<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function () {
    return response()->file(public_path('files/ArfforniaV.5ModList.json'));
});
