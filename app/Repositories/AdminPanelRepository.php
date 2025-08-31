<?php

namespace App\Repositories;

use App\Models\LauncherImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;


class AdminPanelRepository
{
    /**
     * Create a new launcher image
     *
     * @param boolean $in_prod
     * @param string $filePath
     * @return ?LauncherImage
     */
    public function createNewLauncherImage(bool $in_prod, string $filePath, ?string $playerName)
    {
        LauncherImage::create([
            'path' => $filePath,
            'in_prod' => $in_prod,
            'player_name' => $playerName,
        ]);
    }

    /**
     * Get all launcher images
     *
     * @return Collection<LauncherImages>
     */
    public function getLauncherImages()
    {
        return LauncherImage::orderBy('created_at', 'desc')->get();
    }
}
