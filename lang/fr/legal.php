<?php

// Pages légales : /legal (mentions légales), /privacy (politique de confidentialité),
// /terms (conditions générales d'utilisation). Same shape as lang/en/legal.php.

return [
    'footer' => [
        'notice' => 'Mentions légales',
        'privacy' => 'Confidentialité',
        'terms' => 'CGU',
    ],
    'contact_email' => 'arffornia@gmail.com',
    'updated' => 'Dernière mise à jour : :date',

    'notice' => [
        'title' => 'Mentions légales',
        'sections' => [
            [
                'title' => 'Éditeur',
                'paragraphs' => [
                    'Ce site est édité par TheGostsniperfr, particulier non professionnel publiant sous pseudonyme, pour le serveur Minecraft communautaire Arffornia.',
                    'Directeur de la publication : TheGostsniperfr.',
                    'Contact : arffornia@gmail.com',
                ],
            ],
            [
                'title' => 'Hébergement',
                'paragraphs' => [
                    'Le site et les serveurs de jeu sont auto-hébergés par l\'éditeur, sur ses propres serveurs en France.',
                    'DNS et protection réseau : Cloudflare, Inc., 101 Townsend Street, San Francisco, CA 94107, États-Unis, https://www.cloudflare.com.',
                ],
            ],
            [
                'title' => 'Minecraft',
                'paragraphs' => [
                    'SERVICE MINECRAFT NON OFFICIEL. NON APPROUVÉ PAR MOJANG OU MICROSOFT ET SANS LIEN AVEC EUX.',
                    'Minecraft est une marque de Mojang AB. Arffornia est un serveur communautaire créé par des fans, qui respecte les Minecraft Usage Guidelines et le CLUF de Minecraft.',
                ],
            ],
            [
                'title' => 'Propriété intellectuelle',
                'paragraphs' => [
                    'Le nom Arffornia, les textes et les illustrations appartiennent à leurs auteurs. Le code source des projets Arffornia est publié sur GitHub (https://github.com/Arffornia), chaque dépôt sous la licence qu\'il indique.',
                    'Les mods utilisés sur le serveur appartiennent à leurs auteurs et sont téléchargés depuis leurs sources officielles.',
                ],
            ],
        ],
    ],

    'privacy' => [
        'title' => 'Politique de confidentialité',
        'sections' => [
            [
                'title' => 'Responsable du traitement',
                'paragraphs' => [
                    'Le responsable du traitement est l\'éditeur de ce site, TheGostsniperfr (voir les mentions légales). Pour toute question sur vos données, écrivez à arffornia@gmail.com.',
                ],
            ],
            [
                'title' => 'Ce que nous collectons, pourquoi et combien de temps',
                'items' => [
                    'UUID et pseudo Minecraft, résultat de la connexion Microsoft : pour créer votre compte et le lier au jeu (exécution des CGU). Conservés jusqu\'à la suppression du compte ; un compte inactif depuis 3 ans reçoit un avis de suppression.',
                    'Argent en jeu, achats, récompenses, nombre de votes, progression et équipe : pour faire fonctionner le jeu (exécution des CGU). Conservés pendant la durée de vie du compte.',
                    'Adresse IP lors d\'un vote : pour détecter la fraude aux votes (intérêt légitime). Conservée 30 jours.',
                    'Adresse IP et navigateur de votre session sur le site : pour la sécurité et la connexion (intérêt légitime). Conservés pendant la durée de la session.',
                    'Adresse IP dans les journaux du site et des serveurs de jeu : pour la sécurité et la lutte contre les abus (intérêt légitime). Conservée 30 jours.',
                    'Messages du chat dans les journaux du jeu et le pont Discord : pour la modération (intérêt légitime). Conservés 30 jours dans les journaux.',
                    'Votre position sur la carte publique : affichée en direct uniquement, jamais enregistrée par la carte.',
                    'Jetons de connexion du launcher : stockés chiffrés sur votre ordinateur uniquement, sous votre contrôle.',
                ],
            ],
            [
                'title' => 'Destinataires',
                'paragraphs' => [
                    'Vos données restent sur les serveurs d\'Arffornia, en France. Elles ne sont jamais vendues ni partagées à des fins publicitaires.',
                    'Microsoft traite votre connexion selon sa propre politique de confidentialité. Discord traite les messages que vous envoyez sur Discord selon sa propre politique.',
                    'Certaines pages chargent encore des ressources de tiers, qui reçoivent alors votre adresse IP : Google Fonts (police), minotar.net et mineskin.eu (têtes et skins des joueurs), Discord (widget du serveur) et les hébergeurs d\'icônes de mods (CurseForge, Modrinth). Nous prévoyons de les servir depuis nos propres serveurs.',
                ],
            ],
            [
                'title' => 'Cookies',
                'paragraphs' => [
                    'Le site n\'utilise que des cookies nécessaires à son fonctionnement ; il ne demande donc pas de consentement :',
                ],
                'items' => [
                    'arffornia_website_session : vous garde connecté (session).',
                    'XSRF-TOKEN : protège les formulaires contre la falsification de requêtes (session).',
                    'locale : retient la langue choisie (un an).',
                ],
            ],
            [
                'title' => 'Vos droits',
                'paragraphs' => [
                    'Conformément au RGPD, vous pouvez accéder à vos données, les rectifier, les supprimer et vous opposer à leur traitement. Pour exercer ces droits, ou supprimer votre compte, écrivez à arffornia@gmail.com depuis une adresse que nous pouvons relier à votre compte, ou contactez un membre du staff sur Discord.',
                    'Si vous estimez que vos droits ne sont pas respectés, vous pouvez adresser une réclamation à la CNIL (https://www.cnil.fr).',
                ],
            ],
            [
                'title' => 'Modifications',
                'paragraphs' => [
                    'Les modifications de cette politique sont annoncées dans les actualités et sur Discord.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Conditions générales d\'utilisation',
        'rules_link' => 'Lire le règlement du serveur',
        'sections' => [
            [
                'title' => 'Objet',
                'paragraphs' => [
                    'Ces conditions s\'appliquent au site, au launcher et aux serveurs de jeu Arffornia. En créant un compte ou en rejoignant le serveur, vous les acceptez, ainsi que le règlement du serveur.',
                ],
            ],
            [
                'title' => 'Comptes',
                'items' => [
                    'Il faut un vrai compte Minecraft Java Edition et se connecter avec Microsoft.',
                    'Vous êtes responsable de tout ce qui est fait avec votre compte. Un compte par joueur ; prêter ou partager un compte est interdit.',
                    'Arffornia est gratuit et adapté à tous les âges. Les joueurs de moins de 15 ans doivent avoir l\'accord d\'un parent ou tuteur.',
                ],
            ],
            [
                'title' => 'Règlement du serveur',
                'paragraphs' => [
                    'Le comportement en jeu, sur le site et sur Discord suit le règlement du serveur, qui fait partie de ces conditions.',
                ],
            ],
            [
                'title' => 'Monnaie virtuelle et récompenses',
                'items' => [
                    'L\'argent en jeu, les objets de la boutique, les grades et les récompenses de vote sont virtuels. Ils n\'ont aucune valeur réelle.',
                    'Ils ne peuvent pas être achetés avec de l\'argent réel, ni vendus, échangés contre de l\'argent, remboursés ou transférés en dehors des fonctions officielles du jeu et du site.',
                    'Nous pouvons ajuster les soldes, les prix et les récompenses, par exemple pour corriger un bug ou un abus, ou au lancement d\'une nouvelle saison.',
                ],
            ],
            [
                'title' => 'Modération',
                'paragraphs' => [
                    'Le staff peut avertir, rendre muet, bannir temporairement ou définitivement un joueur, ou retirer des objets et constructions, en cas de non-respect du règlement ou de ces conditions. Vous pouvez contester une sanction dans le salon support du Discord.',
                ],
            ],
            [
                'title' => 'Disponibilité et responsabilité',
                'paragraphs' => [
                    'Arffornia est un projet communautaire qui tourne sur du matériel personnel. Les serveurs ne sont pas disponibles en permanence et peuvent être arrêtés, réinitialisés ou modifiés à tout moment, sans indemnisation. Nous faisons de notre mieux pour garder des sauvegardes, sans pouvoir garantir qu\'aucune progression ne sera jamais perdue.',
                ],
            ],
            [
                'title' => 'Modifications et droit applicable',
                'paragraphs' => [
                    'Les modifications de ces conditions sont annoncées dans les actualités et sur Discord ; continuer à jouer vaut acceptation. Ces conditions sont soumises au droit français.',
                ],
            ],
            [
                'title' => 'Contact',
                'paragraphs' => [
                    'Questions sur ces conditions : arffornia@gmail.com, ou le salon support du Discord.',
                ],
            ],
        ],
    ],
];
