<?php

// Flash and JSON messages shown to players. Keep the keys in sync with en/messages.php.

return [
    'auth' => [
        'logged_out' => 'Tu as été déconnecté !',
        'welcome' => 'Bienvenue :name !',
        'could_not_authenticate' => 'Impossible de te connecter. Réessaie.',
        'failed' => 'La connexion a échoué. Réessaie.',
    ],
    'shop' => [
        'already_owned' => 'Tu possèdes déjà cet article.',
        'not_enough_money' => 'Tu n\'as pas assez d\'argent.',
        'not_deliverable' => 'Cet article n\'est pas encore configuré pour la livraison.',
        'transaction_error' => 'Une erreur est survenue pendant la transaction. Réessaie.',
        'purchased' => 'Tu as acheté :item ! Tes objets seront livrés en jeu d\'ici peu.',
    ],
    'vote' => [
        'unknown_site' => 'Site de vote inconnu.',
        'cooldown' => 'Tu pourras revoter sur ce site dans :time.',
        'server_error' => 'Erreur de configuration du serveur.',
        'not_confirmed' => 'Impossible de confirmer ton vote. Réessaie.',
        'thanks' => 'Merci pour ton vote ! Ta récompense arrive.',
    ],
    'admin' => [
        'uploaded' => 'Fichier envoyé : :file !',
        'updated' => 'Modifications enregistrées.',
    ],
];
