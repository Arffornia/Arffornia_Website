<?php
namespace App\Services;

use App\Repositories\UserRepository;
use Arffornia\MinecraftOauth\MinecraftOauth;
use Arffornia\MinecraftOauth\Exceptions\MinecraftOauthException;

class UserService{
    private UserRepository $repository;

    public function __construct(UserRepository $repository) {
        $this->repository = $repository;
    }

    public function getBestUsersByProgressPoints($size) {
        return $this->repository->getBestUsersByProgressPoints($size);
    }

    /**
     * Get user by name
     *
     * @param  string  $name
     * @return
     */
    public function getUserByName(string $name) {
        return $this->repository->getUserByName($name);
    }

    /**
     * Get user by uuid
     *
     * @param  string  $uuid
     * @return
     */
    public function getUserByUuid(string $uuid) {
        return $this->repository->getUserByUuid($uuid);
    }



    public function getTopVoters(int $size) {
        if ($size < 0) {
            $size *= -1;
        }

        if ($size > 25) {
            $size = 25;
        }

        return $this->repository->getTopVoters($size);
    }

    public function getMsAuthRedirectUrl() {
        $clientId = env('AZURE_OAUTH_CLIENT_ID');
        $redirectUri = urlencode(env('AZURE_OAUTH_REDIRECT_URI'));

        return "https://login.live.com/oauth20_authorize.srf?client_id=$clientId&response_type=code&redirect_uri=$redirectUri&scope=XboxLive.signin%20offline_access&state=NOT_NEEDED";
    }


    public function getUserFromMsAuthCallback() {
        $clientId = env('AZURE_OAUTH_CLIENT_ID');
        $redirectUri = env('AZURE_OAUTH_REDIRECT_URI');
        $clientSecret = env('AZURE_OAUTH_CLIENT_SECRET');

        try {
            $profile = (new MinecraftOauth)->fetchProfile(
                $clientId,
                $clientSecret,
                $_GET['code'],
                $redirectUri,
            );

            // dump('Minecraft UUID: ' . $profile->uuid());
            // dump( 'Minecraft Username: ' . $profile->username());
            // dump( 'Minecraft Skin URL: ' . $profile->skins()[0]->url());
            // dump( 'Minecraft Cape URL: ' . $profile->capes()[0]->url());

            $user = $this->getUserByUuid($profile->uuid());

            if(!$user) {
                // register new player
                $user =  $this->repository->createUser($profile->username(), $profile->uuid());
            }

            // Login
            auth()->login($user);

            return $user;


        } catch (MinecraftOauthException $e) {
            dump($e->getMessage());

            /*
                TODO:

                Add a flash message, with e getmessage
            */
            return null;
        }
    }
}
