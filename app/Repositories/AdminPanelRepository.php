<?php
namespace App\Repositories;

use App\Models\LauncherImage;
use App\Models\User;
use App\Models\LauncherVersion;
use Illuminate\Database\Eloquent\Collection;


class AdminPanelRepository {
    /**
     * [Obselete] Create a new launcer version
     *
     * @param string $version
     * @param string $hash
     * @param boolean $in_prod
     * @param string $filePath
     * @return LauncherVersion
     */
    public function createNewLauncherVersion(string $version, string $hash, bool $in_prod, string $filePath) {
        LauncherVersion::create([
            'version' => $version,
            'hash' => $hash,
            'in_prod' => $in_prod,
            'path' => $filePath,
        ]);
    }

    /**
     * Create a new launcher image
     *
     * @param boolean $in_prod
     * @param string $filePath
     * @return LauncherImage
     */
    public function createNewLauncherImage(bool $in_prod, string $filePath) {
        LauncherImage::create([
            'path' => $filePath,
            'in_prod' => $in_prod,
        ]);
    }

    /**
     * Get all launcher versions
     *
     * @return Collection<LauncherVersion>
     */
    public function getLauncherVersions() {
        return LauncherVersion::orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all launcher images
     *
     * @return Collection<LauncherImages>
     */
    public function getLauncherImages() {
        return LauncherImage::orderBy('created_at', 'desc')->get();
    }
}
