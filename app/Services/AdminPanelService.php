<?php
namespace App\Services;

use App\Repositories\AdminPanelRepository;

class AdminPanelService {

    private AdminPanelRepository $repository;

    public function __construct(AdminPanelRepository $repository) {
        $this->repository = $repository;
    }

    public function uploadNewLauncherVersion(string $version, bool $in_prod, $file) {
        // Store the file in storage\app\public folder
        $fileName = $file->getClientOriginalName();
        $fileName = str_replace(' ', '_', $fileName);
        $filePath = $file->storeAs('uploads/launcherVersions', $fileName, 'public');

        // Get sha1
        $hash = sha1_file($file->getRealPath());

        // Save version file in db
        $this->createNewLauncherVersion($version, $hash, $in_prod, $filePath);
    }

    public function createNewLauncherVersion(string $version, string $hash, bool $in_prod, string $filePath) {
        return $this->repository->createNewLauncherVersion($version, $hash, $in_prod, $filePath);
    }

}
