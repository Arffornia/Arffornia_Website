<?php
namespace App\Repositories;

use App\Models\LauncherImage;
use App\Models\LauncherVersioning;
use App\Models\User;

class LauncherRepository {
    public function getLauncherInfo(bool $devVersion) {
        if($devVersion){
            $launcherVersion = LauncherVersioning::orderBy("created_at","desc")->first();
        } else {
            $launcherVersion = LauncherVersioning::where('in_prod', !$devVersion)
                                ->orderBy("created_at","desc")
                                ->first();
        }
        
        
        return $launcherVersion;            
    }

    public function getLauncherImages() {
        return LauncherImage::where('in_prod', true)->orderBy("created_at","desc")->get();
    }
}