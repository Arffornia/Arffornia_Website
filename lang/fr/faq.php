<?php

// FAQ joueurs (/faq). Same shape as lang/en/faq.php.

return [
    'title' => 'FAQ',
    'footer' => 'FAQ',
    'entries' => [
        [
            'id' => 'mac',
            'question' => 'Peut-on jouer sur Mac ?',
            'paragraphs' => [
                'Oui. Le launcher fonctionne sur macOS 12 Monterey ou plus récent, sur Apple Silicon (M1 et suivants) comme sur les Mac Intel. Il installe Java et le modpack pour vous.',
            ],
            'steps' => [
                'Vérifiez votre puce : menu Pomme > À propos de ce Mac. « Puce : Apple M… » = Apple Silicon ; « Processeur : Intel » = Intel.',
                'Téléchargez le fichier correspondant dans les versions du launcher (https://github.com/Arffornia/Arffornia_Launcher_V.5.2/releases) : le .dmg « arm64 » pour Apple Silicon, le .dmg « x64 » pour Intel.',
                'Ouvrez le .dmg et glissez Arffornia dans Applications.',
                'Premier lancement : ouvrez Arffornia. macOS indique qu\'il ne peut pas vérifier l\'app, car Arffornia est gratuit et n\'est pas notarié par Apple. Cliquez sur Terminé.',
                'Ouvrez Réglages Système > Confidentialité et sécurité, descendez jusqu\'à « Arffornia a été bloqué », cliquez sur Ouvrir quand même et confirmez avec votre mot de passe. À faire une seule fois par version du launcher.',
                'Connectez-vous avec Microsoft et cliquez sur Jouer. Le premier démarrage télécharge Java 21 pour votre Mac et environ 330 mods : il prend un moment.',
            ],
            'notes' => [
                'Mémoire : le jeu utilise jusqu\'à 8 Go. Un Mac avec 16 Go de mémoire ou plus est recommandé ; avec 8 Go, fermez les autres applications.',
                'Mises à jour : sur macOS, le launcher ne peut pas encore se mettre à jour tout seul. Quand une nouvelle version est annoncée, retéléchargez-la et remplacez l\'app dans Applications. Vos réglages et mondes restent dans ~/Library/Application Support/Arffornia.',
                'Shaders : Complementary et BSL fonctionnent. Les packs voxel et ray tracing demandent des fonctions graphiques que macOS n\'a pas.',
                'Un problème ? Envoyez le fichier ~/Library/Application Support/Arffornia/.Arffornia_V.5.2/launcher_log.txt dans le salon support du Discord.',
            ],
        ],
    ],
];
