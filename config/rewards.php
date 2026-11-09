<?php

return [
    // Typed contents of the reward created for each vote (see App\Rewards\RewardCatalog).
    'vote' => [
        ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 1],
    ],

    'streak' => [
        // A "day" for the daily login streak; one missed day resets the streak to 1.
        'timezone' => env('REWARDS_STREAK_TIMEZONE', 'Europe/Paris'),

        // The tier with the highest `min_days` not above the current streak applies. The last tier is the cap.
        'tiers' => [
            ['min_days' => 1, 'contents' => [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 1]]],
            ['min_days' => 8, 'contents' => [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 2]]],
            ['min_days' => 15, 'contents' => [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 3]]],
            ['min_days' => 22, 'contents' => [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 4]]],
        ],
    ],

    'pending_page_size' => 50,
];
