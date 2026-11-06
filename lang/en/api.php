<?php

// Messages returned in API JSON bodies. The API locale comes from App\Http\Middleware\SetApiLocale
// (`?locale=`, `locale` cookie, Accept-Language, default `en`). The English lines are byte-identical to the
// former hardcoded strings: clients that send no locale (mod, proxy) see no change.
// Keys are stable: treat them as message codes (a future `code` field will carry them).

return [
    'forbidden' => 'Forbidden',
    'internal_error' => 'An internal error occurred.',
    'auth' => [
        'not_authenticated' => 'Not authenticated',
        'unauthenticated' => 'Unauthenticated.',
        'failed' => 'Authentication failed. Please try again.',
        'invalid_service_credentials' => 'Invalid service account credentials',
    ],
    'player' => [
        'not_found' => 'Player not found.',
        'name_not_found' => 'player name not found.',
    ],
    'user' => [
        'not_found' => 'User not found',
        'create_failed' => 'Failed to create user.',
    ],
    'team' => [
        'joined' => 'Player joined team successfully.',
        'join_failed' => 'Failed to process player join.',
        'left' => 'Player left team successfully.',
        'leave_failed' => 'Failed to process player leave.',
        'disbanded' => 'Team disbanded successfully.',
        'disband_failed' => 'Failed to disband team.',
    ],
    'progression' => [
        'invalid_milestone_id' => 'Invalid milestone_id.',
        'milestone_added' => 'Milestone added successfully.',
        'milestone_removed' => 'Milestone removed successfully.',
        'target_updated' => 'Target milestone updated successfully.',
        'target_update_failed' => 'Failed to update target milestone.',
    ],
    'stages' => [
        'import_invalid' => 'Invalid file format.',
        'import_failed' => 'An error occurred during import.',
        'imported' => 'Progression data imported successfully. Please refresh the page.',
        'graph_not_empty' => 'Cannot delete graph: It still contains milestones.',
        'stage_not_empty' => 'Cannot delete stage: It still contains milestones.',
        'stage_deleted' => 'Stage deleted successfully.',
        'link_not_found' => 'Link not found.',
        'link_milestones_not_found' => 'One or both milestones not found.',
        'link_exists' => 'Link already exists.',
        'link_created' => 'Link created successfully.',
        'unlock_exists' => 'An unlock for this item already exists for this milestone.',
        'unlock_create_failed' => 'An error occurred while creating the unlock and its recipe.',
        'requirements_updated' => 'Milestone requirements have been successfully updated.',
        'item_moved' => ":type ':item' moved successfully from milestone :from to :to.",
        'item_move_failed' => 'Failed to move item. :error',
        'item_type' => [
            'unlock' => 'Unlock',
            'requirement' => 'Requirement',
        ],
    ],
    'recipe' => [
        'not_found' => 'Recipe not found.',
        'batch_failed' => 'An error occurred during the batch recipe submission.',
        'batch_processed' => 'Successfully processed :count recipes.',
    ],
    'vote' => [
        'user_not_found' => 'User not found, vote ignored.',
        'recorded' => 'Vote recorded successfully.',
    ],
];
