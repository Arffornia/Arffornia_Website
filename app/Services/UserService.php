<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Collection;

use Arffornia\MinecraftOauth\MinecraftOauth;
use Arffornia\MinecraftOauth\Exceptions\MinecraftOauthException;

class UserService
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }


    /*
        Mojang api
    */

    /**
     * Get player name by uuid from mojang api
     *
     * @param string $uuid
     * @return string|null
     */
    public function getPlayerNameFromUuid(string $uuid)
    {
        $url = 'https://api.mojang.com/user/profiles/' . $uuid . '/names';
        $response = Http::get($url);

        if ($response->successful()) {
            $names = $response->json();
            if (is_array($names) && count($names) > 0) {
                $pseudo = end($names)['name'];
                return $pseudo;
            }
        }

        return null;
    }

    /**
     * Format the player uuid
     *
     * @param string $uuid
     * @return string
     */
    public function getCleanPlayerUuid(string $uuid)
    {
        return str_replace('-', '', $uuid);
    }

    /**
     * Get size best user by progress points
     *
     * @param int $size
     * @return Collection<User>
     */
    public function getBestUsersByProgressPoints($size)
    {
        return $this->repository->getBestUsersByProgressPoints($size);
    }

    /**
     * Get user by name
     *
     * @param  string  $name
     * @return User
     */
    public function getUserByName(string $name)
    {
        return $this->repository->getUserByName($name);
    }

    /**
     * Get user by uuid
     *
     * @param  string  $uuid
     * @return User
     */
    public function getUserByUuid(string $uuid)
    {
        return $this->repository->getUserByUuid($uuid);
    }

    /**
     * Get size top voters
     *
     * @param integer $size
     * @return Collection<User>
     */
    public function getTopVoters(int $size)
    {
        $size = min(max(0, $size), 25);
        return $this->repository->getTopVoters($size);
    }

    /**
     * Get size user by progress points
     *
     * @param integer $size
     * @return Collection<User>
     */
    public function getTopUsersByPoint(int $size)
    {
        $size = min(max(0, $size), 25);
        return $this->repository->getTopUsersByPoint($size);
    }

    /**
     * Create a new user, and return it
     *
     * @param string $name
     * @param string $uuid
     * @return User
     */
    public function createUser(string $name, string $uuid)
    {
        return $this->repository->createUser($name, $uuid);
    }

    /**
     * Generate user loging redirect url by MS auth
     *
     * @return string
     */
    public function getMsAuthRedirectUrl()
    {
        $clientId = config('app.azure.oauth.client.id');
        $redirectUri = urlencode(config('app.azure.oauth.redirect_uri'));

        return "https://login.live.com/oauth20_authorize.srf?client_id=$clientId&response_type=code&redirect_uri=$redirectUri&scope=XboxLive.signin%20offline_access&state=NOT_NEEDED";
    }

    /**
     * Get MS auth callback for user loging
     *
     * @return User
     */
    public function getUserFromMsAuthCallback()
    {
        $clientId = config('app.azure.oauth.client.id');
        $redirectUri = config('app.azure.oauth.redirect_uri');
        $clientSecret = urlencode(config('app.azure.oauth.client.secret'));

        try {
            $profile = (new MinecraftOauth)->fetchProfileWithOAuthUI(
                $clientId,
                $clientSecret,
                $_GET['code'],
                $redirectUri,
            );

            $user = $this->getUserFromMCProfile($profile);


            // Login
            auth()->login($user);

            return $user;
        } catch (MinecraftOauthException $e) {
            dump($e->getMessage());

            /*
                TODO:

                Add a flash message, with e getmessage
            */
            abort(401, 'Authentication failed. Please try again.');
        }
    }

    public function getUserWithAccessToken($access_token)
    {
        try {
            $profile = (new MinecraftOauth)->fetchProfileWithAccessToken(
                $access_token
            );

            $user = $this->getUserFromMCProfile($profile);

            return $user;
        } catch (MinecraftOauthException $e) {
            dump($e->getMessage());

            /*
                TODO:

                Add a flash message, with e getmessage
            */
            abort(401, 'Authentication failed. Please try again.');
        }
    }

    private function getUserFromMCProfile($profile)
    {

        $cleanUuid = $this->getCleanPlayerUuid($profile->uuid());
        $user = $this->getUserByUuid($cleanUuid);

        if (!$user) {
            // register new player
            $user =  $this->repository->createUser($profile->username(), $cleanUuid);
        }

        // Check of the player has change his Minecraft pseudo
        $tempPseudo = $this->getPlayerNameFromUuid($cleanUuid);

        if ($tempPseudo != null && $user->name != $tempPseudo) {
            $user->name = $tempPseudo;
            $user->save();
        }

        return $user;
    }
}
