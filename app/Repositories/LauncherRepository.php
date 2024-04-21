<?php
namespace App\Repositories;

use App\Models\LauncherImage;
use App\Models\LauncherVersioning;
use App\Models\User;

class LauncherRepository {
    public function getLauncherInfo(bool $devVersion) {
        if($devVersion){
            if($devVersion){
                $launhcerVersion = LauncherVersioning::orderBy("created_at","desc")->first();
            } else {
                $launhcerVersion = LauncherVersioning::where('in_prod', !$devVersion)
                                    ->orderBy("created_at","desc")
                                    ->first();
            }
        }
        
        return $launhcerVersion;            
    }

    public function getLauncherImages() {
        return LauncherImage::where('in_prod', true)->orderBy("created_at","desc")->get();
    }
}