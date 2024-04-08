<?php

use Illuminate\Support\Facades\Route;

Route::get('/arffornia_v5/modlist', function () {
    return response()->file(public_path('files/ArfforniaV.5ModList.json'));
});
