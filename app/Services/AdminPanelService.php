<?php
namespace App\Services;

use App\Repositories\AdminPanelRepository;

class AdminPanelService {

    private AdminPanelRepository $repository;

    public function __construct(AdminPanelRepository $repository) {
        $this->repository = $repository;
    }

}
