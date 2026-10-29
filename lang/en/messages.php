<?php

// Flash and JSON messages shown to players. API routes run in `en` (config app.locale); the shop and vote
// English strings are kept identical to the previous hardcoded ones so API responses do not change.

return [
    'auth' => [
        'logged_out' => 'You have been logged out!',
        'welcome' => 'Welcome :name!',
        'could_not_authenticate' => 'Could not authenticate you. Please try again.',
        'failed' => 'Authentication failed. Please try again.',
    ],
    'shop' => [
        'already_owned' => 'You already own this item.',
        'not_enough_money' => 'You do not have enough money.',
        'not_deliverable' => 'This item is not configured for delivery.',
        'transaction_error' => 'An error occurred during the transaction. Please try again.',
        'purchased' => 'You have successfully purchased :item! Your items will be delivered in-game shortly.',
    ],
    'vote' => [
        'unknown_site' => 'Unknown voting site.',
        'cooldown' => 'You can vote on this site again in :time.',
        'server_error' => 'Server configuration error.',
        'not_confirmed' => 'We could not confirm your vote. Please try again.',
        'thanks' => 'Thank you for your vote! Your reward is on its way.',
    ],
    'admin' => [
        'uploaded' => 'Launcher image uploaded.',
        'updated' => 'Changes saved.',
    ],
];
