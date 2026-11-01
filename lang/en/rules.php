<?php

// Server rules. Each section has a title and a list of rules; a rule is either a string or
// ['text' => ..., 'sub' => [...]] for a rule with sub-points. Keep fr/rules.php in the same shape
// (the French version is the reference text).

return [
    'title' => 'Server rules:',
    'sections' => [
        [
            'title' => 'Preamble:',
            'items' => [
                'By connecting to any part of the Arffornia infrastructure, every player is considered to accept these rules.',
                'These rules may change in the future; any changes will be announced on Discord or in the "News" tab.',
            ],
        ],
        [
            'title' => 'I - General:',
            'items' => [
                'Arffornia is first and foremost a Minecraft building server where everyone plays their own survival in a friendly environment. Players are expected to respect this community spirit.',
                'Every player is responsible for the account they play on, whether or not they own it.',
                'Using an alt account or lending your Minecraft account to someone else is not allowed.',
                'Selling services, gear, money, etc. through anything other than the official channels (website, in-game money) is not allowed.',
                'Scams of any kind are forbidden.',
                'Giving away gear to players outside your team is not allowed.',
                'If you have a dispute with another player or a staff member, please report it in the Discord support channel with proof (videos or screenshots).',
                'These rules are not exhaustive: staff can still sanction inappropriate behaviour that is not listed here.',
            ],
        ],
        [
            'title' => 'II - In game:',
            'items' => [
                'Griefing builds, or taking or moving another player\'s belongings without their consent, is strictly forbidden.',
                'Placing claims or wrecking the chunks around someone\'s base to cause them harm is not allowed.',
                'Bases of players who have been inactive for over 6 months and made little progress may be removed by staff if they get in the way of another player\'s base.',
                'Any machine or bug abuse meant to slow down or crash the server will be severely punished.',
                'Cheating of any kind is forbidden.',
                'Every player is responsible for reporting cheating; helping a cheater can get you sanctioned too.',
                'Using any third-party tool is forbidden: macros, VMs, proxies, VPNs, etc.',
                'Modifying the launcher, or connecting to the server with anything other than the official Arffornia launcher, is not allowed.',
                'Anti-AFK tricks and auto-clickers of any kind are forbidden (this includes using an object to hold a button down).',
                'Inappropriate behaviour towards other players can lead to sanctions; help keep the atmosphere friendly and respectful.',
            ],
        ],
        [
            'title' => 'III - Performance:',
            'items' => [
                [
                    'text' => 'Arffornia runs a lot of mods, and not all of them are well optimized, so please take care to keep your bases, farms, etc. lag-friendly. Here is a non-exhaustive list of things to do:',
                    'sub' => [
                        'Make your machines shut off on their own (so they do not run for nothing).',
                        'Handle waste properly (void it in a trash can, lava, etc.). Simply dropping items on the ground is not allowed.',
                    ],
                ],
                'A staff member may warn you that a machine or base is causing too much lag, then sanction you (e.g. by removing the machine) if no serious optimization is made.',
                'Adding an on/off switch to your machines and base is strongly recommended.',
                'You are allowed a maximum of 20 entities per chunk. Go over that limit and you may be sanctioned.',
            ],
        ],
        [
            'title' => 'IV - Chat:',
            'items' => [
                'Spam, flooding and excessive caps are not allowed.',
                'Hate speech, insults and discrimination of any kind will be harshly sanctioned.',
                'Messages complaining about the server are not welcome in chat. You can share your feedback in the Discord support channel.',
                'Advertising anything not related to the server is not allowed.',
                'Begging for donations is not allowed.',
                'Please mind your spelling in chat.',
            ],
        ],
        [
            'title' => 'V - Shop:',
            'items' => [
                'Shop purchases are non-refundable.',
                'If something goes wrong with a purchase (e.g. you did not receive it), please contact support.',
                'Reselling shop promo codes is not allowed.',
            ],
        ],
        [
            'title' => 'VI - Progression system:',
            'items' => [
                'Suggestions to change some links or required items for a milestone are welcome if they improve the system or its balance.',
                'Players may use naturally generated items even if they have not unlocked them yet.',
                'When selling or renting an item to a player who has not unlocked it, adjust the price to keep things fair between the player\'s stage and the stage that unlocks the item.',
            ],
        ],
        [
            'title' => 'VII - Bugs:',
            'items' => [
                'If you find a bug, report it quickly and privately to an Administrator or a Developer on the staff.',
                [
                    'text' => 'Only the bugs listed below are allowed; all others are forbidden:',
                    'sub' => [
                        'Breaking bedrock.',
                    ],
                ],
            ],
        ],
    ],
];
