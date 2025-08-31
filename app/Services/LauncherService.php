<?php

namespace App\Services;

use App\Models\LauncherImage;
use App\Repositories\LauncherRepository;
use Illuminate\Database\Eloquent\Collection;



class LauncherService
{

    private LauncherRepository $repository;

    public function __construct(LauncherRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all launcher images
     *
     * @return Collection<LauncherImage>
     */
    public function getLauncherImages()
    {
        return $this->repository->getLauncherImages()
            ->map(function ($image) {
                return [
                    'path' => url($image->path),
                    'player_name' => $image->player_name,
                ];
            })
            ->toArray();
    }
}
