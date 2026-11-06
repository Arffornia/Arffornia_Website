<?php

// Messages renvoyés dans les réponses JSON de l'API (voir lang/en/api.php).

return [
    'forbidden' => 'Accès refusé',
    'internal_error' => 'Une erreur interne est survenue.',
    'auth' => [
        'not_authenticated' => 'Non authentifié',
        'unauthenticated' => 'Non authentifié.',
        'failed' => "L'authentification a échoué. Veuillez réessayer.",
        'invalid_service_credentials' => 'Identifiants du compte de service invalides',
    ],
    'player' => [
        'not_found' => 'Joueur introuvable.',
        'name_not_found' => 'nom de joueur introuvable.',
    ],
    'user' => [
        'not_found' => 'Utilisateur introuvable',
        'create_failed' => "Impossible de créer l'utilisateur.",
    ],
    'team' => [
        'joined' => "Le joueur a rejoint l'équipe.",
        'join_failed' => "Impossible de traiter l'arrivée du joueur.",
        'left' => "Le joueur a quitté l'équipe.",
        'leave_failed' => 'Impossible de traiter le départ du joueur.',
        'disbanded' => 'Équipe dissoute.',
        'disband_failed' => "Impossible de dissoudre l'équipe.",
    ],
    'progression' => [
        'invalid_milestone_id' => 'milestone_id invalide.',
        'milestone_added' => 'Jalon ajouté.',
        'milestone_removed' => 'Jalon retiré.',
        'target_updated' => 'Jalon ciblé mis à jour.',
        'target_update_failed' => 'Impossible de mettre à jour le jalon ciblé.',
    ],
    'stages' => [
        'import_invalid' => 'Format de fichier invalide.',
        'import_failed' => "Une erreur est survenue pendant l'import.",
        'imported' => 'Données de progression importées. Veuillez rafraîchir la page.',
        'graph_not_empty' => 'Impossible de supprimer le graphe : il contient encore des jalons.',
        'stage_not_empty' => 'Impossible de supprimer le palier : il contient encore des jalons.',
        'stage_deleted' => 'Palier supprimé.',
        'link_not_found' => 'Lien introuvable.',
        'link_milestones_not_found' => "Un des jalons, ou les deux, est introuvable.",
        'link_exists' => 'Ce lien existe déjà.',
        'link_created' => 'Lien créé.',
        'unlock_exists' => 'Ce jalon débloque déjà cet objet.',
        'unlock_create_failed' => 'Une erreur est survenue pendant la création du déblocage et de sa recette.',
        'requirements_updated' => 'Les objets requis du jalon ont été mis à jour.',
        'item_moved' => ":type « :item » déplacé du jalon :from vers le jalon :to.",
        'item_move_failed' => "Impossible de déplacer l'objet. :error",
        'item_type' => [
            'unlock' => 'Déblocage',
            'requirement' => 'Objet requis',
        ],
    ],
    'recipe' => [
        'not_found' => 'Recette introuvable.',
        'batch_failed' => "Une erreur est survenue pendant l'envoi du lot de recettes.",
        'batch_processed' => ':count recettes traitées.',
    ],
    'vote' => [
        'user_not_found' => 'Utilisateur introuvable, vote ignoré.',
        'recorded' => 'Vote enregistré.',
    ],
];
