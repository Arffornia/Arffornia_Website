<?php

use App\Services\Vote\Verifiers\SmcVoteVerifier;


return [
    'sites' => [
        'smc' => [
            'name' => 'Serveurs-Minecraft.org',
            'url' => 'https://www.serveurs-minecraft.org/vote.php?id=' . env('VOTE_SMC_SERVER_ID'),
            'cooldown_hours' => 24,
            'verifier_class' => SmcVoteVerifier::class,
        ],
    ],
];
