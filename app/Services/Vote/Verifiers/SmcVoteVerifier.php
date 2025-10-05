<?php

namespace App\Services\Vote\Verifiers;

use App\Models\User;
use App\Services\Vote\VoteVerifierInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmcVoteVerifier implements VoteVerifierInterface
{
    /**
     * {@inheritdoc}
     */
    public function verify(User $user, Request $request): bool
    {
        $serverId = config('services.smc.server_id');
        if (!$serverId) {
            Log::error('Vote Service Error: VOTE_SMC_SERVER_ID is not set.');
            return false;
        }

        $response = Http::get("https://www.serveurs-minecraft.org/api/is_valid_vote.php", [
            'id' => $serverId,
            'ip' => $request->ip(),
            'format' => 'json',
            'duration' => 10,
        ]);

        if ($response->failed()) {
            Log::error('SMC Vote API request failed.', ['status' => $response->status(), 'user_id' => $user->id]);
            return false;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            Log::info('SMC Vote API returned an error.', ['error' => $data['error'], 'user_id' => $user->id]);
            return false;
        }

        return isset($data['votes']) && $data['votes'] >= 1;
    }
}
