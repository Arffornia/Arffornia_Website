<?php

namespace App\Services\Vote;

use App\Models\User;
use Illuminate\Http\Request;

interface VoteVerifierInterface
{
    /**
     * Verify if a vote is valid with the external site's API.
     *
     * @param User $user The user attempting to verify.
     * @param Request $request The incoming HTTP request, containing the user's IP.
     * @return bool True if the vote is valid, false otherwise.
     */
    public function verify(User $user, Request $request): bool;
}
