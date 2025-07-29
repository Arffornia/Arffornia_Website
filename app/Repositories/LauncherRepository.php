<?php

namespace App\Repositories;

use App\Models\LauncherImage;
use App\Models\LauncherVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;


class LauncherRepository
{
    /**
     * Get all launcher images
     *
     * @return Collection<LauncherVersion>
     */
    public function getLauncherImages()
    {
        return LauncherImage::where('in_prod', true)->orderBy("created_at", "desc")->get();
    }
}
