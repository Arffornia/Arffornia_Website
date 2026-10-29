<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
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
     * Session key holding the OAuth `state` value of the pending Microsoft login.
     */
    public const OAUTH_STATE_SESSION_KEY = 'ms_oauth_state';

    /**
     * Generate the Microsoft login redirect URL.
     *
     * A random `state` value is stored in the session and must come back unchanged
     * on the callback (see isValidOAuthState).
     *
     * @return string
     */
    public function getMsAuthRedirectUrl()
    {
        $state = Str::random(40);
        session()->put(self::OAUTH_STATE_SESSION_KEY, $state);

        $query = http_build_query([
            'client_id' => config('app.azure.oauth.client.id'),
            'response_type' => 'code',
            'redirect_uri' => config('app.azure.oauth.redirect_uri'),
            'scope' => 'XboxLive.signin offline_access',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return "https://login.live.com/oauth20_authorize.srf?$query";
    }

    /**
     * Check the `state` returned by Microsoft against the one stored in the session.
     * The stored value is consumed, so a state can only be used once.
     *
     * @param mixed $state
     * @return bool
     */
    public function isValidOAuthState($state): bool
    {
        $expected = session()->pull(self::OAUTH_STATE_SESSION_KEY);

        return is_string($expected) && is_string($state) && hash_equals($expected, $state);
    }

    /**
     * Get an User with MS OAuth UI Flow
     *
     * @param string $code Authorization code returned by Microsoft
     * @return User
     *
     * @throws MinecraftOauthException
     */
    public function getUserFromMsAuthCallback(string $code)
    {
        $clientId = config('app.azure.oauth.client.id');
        $redirectUri = config('app.azure.oauth.redirect_uri');
        $clientSecret = urlencode(config('app.azure.oauth.client.secret'));

        $profile = app(MinecraftOauth::class)->fetchProfileWithOAuthUI(
            $clientId,
            $clientSecret,
            $code,
            $redirectUri,
        );

        $user = $this->getUserFromMCProfile($profile);

        // Login
        auth()->login($user);

        return $user;
    }


    /**
     * Get an User with a MS access token
     *
     * @param mixed $access_token
     * @return User
     *
     * @throws MinecraftOauthException
     */
    public function getUserWithAccessToken($access_token)
    {
        $profile = app(MinecraftOauth::class)->fetchProfileWithAccessToken(
            $access_token
        );

        return $this->getUserFromMCProfile($profile);
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
