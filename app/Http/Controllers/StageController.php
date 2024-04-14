<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use Illuminate\Http\Request;

class StageController extends Controller
{
    public function getStartStage() {
        return Stage::where('number', 1)->first();
    }
}
