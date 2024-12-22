<?php
namespace App\Repositories;

use App\Models\LauncherImage;
use App\Models\LauncherVersion;
use App\Models\User;

class LauncherRepository {

    /**
     * Get launcher information
     *
     * @param boolean $devVersion
     * @return Collection<LauncherVersion>
     */
    public function getLauncherInfo(bool $devVersion) {
        if($devVersion){
            $launcherVersion = LauncherVersion::orderBy("created_at","desc")->first();
        } else {
            $launcherVersion = LauncherVersion::where('in_prod', !$devVersion)
                                ->orderBy("created_at","desc")
                                ->first();
        }


        return $launcherVersion;
    }

    /**
     * Get all launcher images
     *
     * @return Collection<LauncherVersion>
     */
    public function getLauncherImages() {
        return LauncherImage::where('in_prod', true)->orderBy("created_at","desc")->get();
    }
}
