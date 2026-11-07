<?php

// Legal pages: /legal (legal notice), /privacy (privacy policy), /terms (terms of use).
// Each page has a title, a last-updated date and sections; a section has a title, optional
// paragraphs and optional list items. Keep lang/fr/legal.php in the same shape.
// Source: docs spec 012 (legal and compliance), data inventory included. Not legal advice.

return [
    'footer' => [
        'notice' => 'Legal notice',
        'privacy' => 'Privacy',
        'terms' => 'Terms of use',
    ],
    'contact_email' => 'arffornia@gmail.com',
    'updated' => 'Last updated: :date',

    'notice' => [
        'title' => 'Legal notice',
        'sections' => [
            [
                'title' => 'Publisher',
                'paragraphs' => [
                    'This website is published by TheGostsniperfr, a non-professional individual publishing under a pseudonym, for the Arffornia community Minecraft server.',
                    'Publication director: TheGostsniperfr.',
                    'Contact: arffornia@gmail.com',
                ],
            ],
            [
                'title' => 'Hosting',
                'paragraphs' => [
                    'The website and the game servers are self-hosted by the publisher on his own servers in France.',
                    'DNS and network protection: Cloudflare, Inc., 101 Townsend Street, San Francisco, CA 94107, United States, https://www.cloudflare.com.',
                ],
            ],
            [
                'title' => 'Minecraft',
                'paragraphs' => [
                    'NOT AN OFFICIAL MINECRAFT SERVICE. NOT APPROVED BY OR ASSOCIATED WITH MOJANG OR MICROSOFT.',
                    'Minecraft is a trademark of Mojang AB. Arffornia is a fan-made community server that follows the Minecraft Usage Guidelines and EULA.',
                ],
            ],
            [
                'title' => 'Intellectual property',
                'paragraphs' => [
                    'The Arffornia name, texts and artwork belong to their authors. The source code of the Arffornia projects is published on GitHub (https://github.com/Arffornia), each repository under the licence stated in it.',
                    'Mods used on the server belong to their respective authors and are downloaded from their official sources.',
                ],
            ],
        ],
    ],

    'privacy' => [
        'title' => 'Privacy policy',
        'sections' => [
            [
                'title' => 'Who is responsible',
                'paragraphs' => [
                    'The data controller is the publisher of this website, TheGostsniperfr (see the legal notice). For any question about your data, write to arffornia@gmail.com.',
                ],
            ],
            [
                'title' => 'What we collect, why, and for how long',
                'items' => [
                    'Minecraft UUID, username and the result of your Microsoft sign-in: to create your account and link it to the game (performance of the terms of use). Kept until your account is deleted; an account inactive for 3 years gets a deletion notice.',
                    'In-game money, purchases, rewards, vote count, progression and team: to run the game features (performance of the terms of use). Kept for the life of your account.',
                    'IP address when you vote: to detect vote fraud (legitimate interest). Kept 30 days.',
                    'IP address and browser user agent of your website session: for security and sign-in (legitimate interest). Kept for the life of the session.',
                    'IP address in website and game server logs: for security and to fight abuse (legitimate interest). Kept 30 days.',
                    'Chat messages in game logs and in the Discord bridge: for moderation (legitimate interest). Kept 30 days in the logs.',
                    'Your position on the public map: only shown live, never stored by the map.',
                    'Launcher sign-in tokens: stored encrypted on your own computer only, under your control.',
                ],
            ],
            [
                'title' => 'Who receives it',
                'paragraphs' => [
                    'Your data stays on Arffornia\'s own servers in France. It is never sold or shared for advertising.',
                    'Microsoft processes your sign-in under its own privacy policy. Discord processes the messages you send on Discord under its own policy.',
                    'Some pages still load resources from third parties, which then receive your IP address: Google Fonts (font), minotar.net and mineskin.eu (player heads and skins), Discord (server widget), and mod icon hosts (CurseForge, Modrinth). We plan to serve these from our own servers.',
                ],
            ],
            [
                'title' => 'Cookies',
                'paragraphs' => [
                    'The website only uses cookies that it needs to work, so it does not ask for consent:',
                ],
                'items' => [
                    'arffornia_website_session: keeps you signed in (session).',
                    'XSRF-TOKEN: protects forms against cross-site request forgery (session).',
                    'locale: remembers the language you chose (one year).',
                ],
            ],
            [
                'title' => 'Your rights',
                'paragraphs' => [
                    'Under the GDPR you can access, correct or delete your data, and object to its processing. To use these rights, or to delete your account, write to arffornia@gmail.com from an address we can link to your account, or contact a staff member on Discord.',
                    'If you think your rights are not respected, you can complain to the CNIL (https://www.cnil.fr).',
                ],
            ],
            [
                'title' => 'Changes',
                'paragraphs' => [
                    'We announce changes to this policy in the News section and on Discord.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Terms of use',
        'rules_link' => 'Read the server rules',
        'sections' => [
            [
                'title' => 'Purpose',
                'paragraphs' => [
                    'These terms apply to the Arffornia website, launcher and game servers. By creating an account or joining the server, you accept them and the server rules.',
                ],
            ],
            [
                'title' => 'Accounts',
                'items' => [
                    'You need a genuine Minecraft Java Edition account and sign in with Microsoft.',
                    'You are responsible for everything done with your account. One account per player; lending or sharing accounts is not allowed.',
                    'Arffornia is free to play and suitable for all ages. Players under 15 should have the agreement of a parent or guardian.',
                ],
            ],
            [
                'title' => 'Server rules',
                'paragraphs' => [
                    'Behaviour in game, on the website and on Discord follows the server rules, which are part of these terms.',
                ],
            ],
            [
                'title' => 'Virtual currency and rewards',
                'items' => [
                    'In-game money, shop items, ranks and vote rewards are virtual. They have no real-world value.',
                    'They cannot be bought with real money, sold, exchanged for money, refunded or transferred outside the official in-game and website features.',
                    'We may adjust balances, prices and rewards, for example to fix a bug or an exploit, or when a new season starts.',
                ],
            ],
            [
                'title' => 'Moderation',
                'paragraphs' => [
                    'Staff may warn, mute, temporarily or permanently ban a player, or remove items and builds, when the rules or these terms are broken. You can contest a sanction in the Discord support channel.',
                ],
            ],
            [
                'title' => 'Availability and liability',
                'paragraphs' => [
                    'Arffornia is a community project run on personal hardware. The servers are not available around the clock and may be stopped, reset or changed at any time, without compensation. We do our best to keep backups, but we cannot guarantee that no progress is ever lost.',
                ],
            ],
            [
                'title' => 'Changes and applicable law',
                'paragraphs' => [
                    'We announce changes to these terms in the News section and on Discord; continuing to play means you accept them. These terms are governed by French law.',
                ],
            ],
            [
                'title' => 'Contact',
                'paragraphs' => [
                    'Questions about these terms: arffornia@gmail.com, or the Discord support channel.',
                ],
            ],
        ],
    ],
];
