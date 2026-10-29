<?php

// Server rules. Each section has a title and a list of rules; a rule is either a string or
// ['text' => ..., 'sub' => [...]] for a rule with sub-points. Keep en/rules.php in the same shape.

return [
    'title' => 'Règlement :',
    'sections' => [
        [
            'title' => 'Préambule :',
            'items' => [
                'Tout joueur se connectant à l\'infrastructure d\'Arffornia est considéré comme approuvant le règlement.',
                'Le règlement peut être modifié dans le futur, les modifications vous seront transmises via Discord ou l\'onglet "news".',
            ],
        ],
        [
            'title' => 'I - Généralités :',
            'items' => [
                'Arffornia est avant tout un serveur Minecraft de build où l’on fait sa propre survie dans un environnement convivial. Les joueurs sont tenus de respecter cet esprit communautaire.',
                'Chaque joueur est responsable du compte qu\'il utilise, que ce soit le titulaire ou non.',
                'L\'usage d\'un double compte ou le prêt d\'un compte Minecraft est interdit.',
                'La vente de services, stuff, argent ... par d\'autres moyens que ceux mis en place (site web, argent du jeu) est interdite.',
                'Toutes formes d\'arnaques sont prohibées.',
                'Le don de stuff (en dehors de votre team) est interdit.',
                'En cas de litige avec un autre joueur ou un membre du personnel, merci d\'en informer le salon de support Discord en fournissant des preuves (vidéos ou captures d\'écran).',
                'Ce règlement n\'est pas exhaustif, des sanctions peuvent être appliquées en cas de comportement inapproprié non répertorié.',
            ],
        ],
        [
            'title' => 'II - In Game :',
            'items' => [
                'La dégradation de build, l\'appropriation ou le déplacement d’un bien d’un autre joueur sans son consentement y est strictement interdit.',
                'La pose de claim, la dégradation des chunks autour d\'une base dans le but de nuire est interdite.',
                'Les bases des joueurs inactifs et peu avancées depuis plus de 6 mois peuvent être détruites par le staff si celles-ci gênent celles d\'un autre.',
                'Toutes formes de machines, use-bugs ayant pour but de ralentir / crasher le serveur sont sévèrement sanctionnées.',
                'Toutes formes de triche sont interdites.',
                'Il est de la responsabilité de chaque joueur de signaler toute activité de triche; toute complicité avec un tricheur est passible de sanction.',
                'L’utilisation d’un système tiers quelconque est interdite : macro, vm, proxy, vpn, etc...',
                'La modification du launcher ou d’un autre que celui officiel d’Arffornia pour se connecter au serveur est interdite.',
                'Toutes formes d\'anti AFK, d\'auto click sont interdites (cela comprend l\'objet bloquant un bouton).',
                'Un comportement inapproprié envers les autres joueurs peut entraîner des sanctions; veillez à maintenir une atmosphère respectueuse et conviviale.',
            ],
        ],
        [
            'title' => 'III - Optimisation :',
            'items' => [
                [
                    'text' => 'Arffornia étant un serveur avec de nombreux mods n\'étant pas forcément très optimisés, nous vous demanderons de bien faire attention à optimiser vos bases, farm, etc.. Voici une liste non exhaustive des points à respecter.',
                    'sub' => [
                        'Auto-coupure d\'une machine (pour ne pas qu\'elle tourne pour rien).',
                        'Gestion efficace des déchets (destruction dans une poubelle, lave, ...). Il est interdit de simplement dropper les items par terre.',
                    ],
                ],
                'Un membre du staff peut vous avertir qu\'une machine/base cause trop de lags puis vous sanctionner (ex : destruction de la machine) si aucune optimisation sérieuse n\'est demandée.',
                'Il est fortement recommandé d\'installer un switch on/off sur vos machines / base.',
                'Vous avez le droit à un maximum de 20 entités par chunk. Dépasser ce nombre, vous pouvez être sanctionné.',
            ],
        ],
        [
            'title' => 'IV - Chat :',
            'items' => [
                'Le spam, flood, majuscules abusives sont interdits.',
                'Toutes formes de haine, insulte, discrimination sont fortement sanctionnées.',
                'Les messages se plaignant du serveur ne sont pas les bienvenus. Vous pouvez donner un retour sur le salon support du Discord.',
                'La pub pour des services autres que liés au serveur est interdite.',
                'La demande de don est interdite.',
                'Merci de faire attention à votre orthographe dans le chat.',
            ],
        ],
        [
            'title' => 'V - Boutique :',
            'items' => [
                'Tout achat dans la boutique n\'est pas remboursable.',
                'En cas de problème via un achat (ex : si vous ne le recevez pas), merci de contacter le support.',
                'La vente de code promo pour la boutique est interdite.',
            ],
        ],
        [
            'title' => 'VI - Système de progression :',
            'items' => [
                'La demande de modification de certaines interconnexions ou items requis pour un jalon est la bienvenue si cela permet d\'améliorer le système / l\'équilibrage.',
                'Les joueurs ont le droit d\'utiliser les items générés naturellement même si ceux-ci ne sont pas débloqués par le joueur.',
                'Les prix de vente / location d\'item à un joueur ne l\'ayant pas débloqué doivent être ajustés pour garder un équilibre entre le palier du joueur et le palier de déblocage de l\'item.',
            ],
        ],
        [
            'title' => 'VII - Bugs :',
            'items' => [
                'Si un joueur découvre un bug, il doit en informer rapidement un Administrateur ou un Développeur du staff en privé.',
                [
                    'text' => 'Seuls les bugs listés ci-dessous sont autorisés, les autres sont interdits :',
                    'sub' => [
                        'Destruction de la bedrock.',
                    ],
                ],
            ],
        ],
    ],
];
