<?php
namespace App\Repositories;

use App\Models\User;
use App\Models\LauncherVersion;

class AdminPanelRepository {
    public function createNewLauncherVersion(string $version, string $hash, bool $in_prod, string $filePath) {
        LauncherVersion::create([
            'version' => $version,
            'hash' => $hash,
            'in_prod' => $in_prod,
            'path' => $filePath,
        ]);
    }

    public function getLauncherVersions() {
        return LauncherVersion::orderBy('created_at', 'desc')->get();
    }
}
