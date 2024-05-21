<?php
namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Http;
use Arffornia\MinecraftOauth\MinecraftOauth;
use Arffornia\MinecraftOauth\Exceptions\MinecraftOauthException;

class UserService{
    private UserRepository $repository;

    public function __construct(UserRepository $repository) {
        $this->repository = $repository;
    }


    /*
        Mojang api
    */

    public function getPlayerNameFromUuid(string $uuid) {
        $rep = Http::get('https://minecraft-api.com/api/pseudo/' . $uuid);
        if($rep->successful()) {
            $pseudo = $rep->body();
            if($pseudo != "Player not found !") {
                return $pseudo;
            }
        }

        return null;
    }

    public function getCleanPlayerUuid(string $uuid) {
        return str_replace('-','', $uuid);
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

    public function createUser(string $name, string $uuid) {
        return $this->repository->createUser($name, $uuid);
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

            $cleanUuid = $this->getCleanPlayerUuid($profile->uuid());
            $user = $this->getUserByUuid($cleanUuid);

            if(!$user) {
                // register new player
                $user =  $this->repository->createUser($profile->username(), $cleanUuid);
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
