<?php

return [
    // Typed contents of the reward created for each vote (see App\Rewards\RewardCatalog).
    'vote' => [
        ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 1],
    ],

    'streak' => [
        // A "day" for the daily login streak; one missed day resets the streak to 1.
        'timezone' => env('REWARDS_STREAK_TIMEZONE', 'Europe/Paris'),

        // Spec 020: give crate keys (`cycle`) instead of lucky boxes (`tiers`). Turn on once the mod with crates and
        // keys runs on every server that claims rewards; until then a key would wait in the pending list.
        'as_keys' => (bool) env('REWARDS_STREAK_AS_KEYS', false),

        // Weekly cycle (spec 020 D3), used when `as_keys` is on. Every day gives `daily`; a day that is a multiple of
        // a bonus's `every` also gives that bonus. Both the rewards and the calendar API read it.
        'cycle' => [
            'daily' => [['type' => 'key', 'crate' => 'arffornia:streak', 'count' => 1]],
            'bonuses' => [
                ['every' => 7, 'contents' => [['type' => 'key', 'crate' => 'arffornia:vote', 'count' => 1]]],
                ['every' => 28, 'contents' => [['type' => 'key', 'crate' => 'arffornia:legendary', 'count' => 1]]],
            ],
        ],

        // Lucky box rewards, used while `as_keys` is off. The tier with the highest `min_days` not above the current
        // streak applies. The last tier is the cap. Remove once `as_keys` is on everywhere.
        'tiers' => [
            ['min_days' => 1, 'contents' => [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 1]]],
            ['min_days' => 8, 'contents' => [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 2]]],
            ['min_days' => 15, 'contents' => [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 3]]],
            ['min_days' => 22, 'contents' => [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 4]]],
        ],
    ],

    'pending_page_size' => 50,
];
