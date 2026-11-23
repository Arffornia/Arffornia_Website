<?php

// Progression graph viewer page (/progression). The component's own strings live in packages/progression-graph/src/i18n.ts.

return [
    'title' => 'Progression',
    'player_title' => "Progression of :name",
    'loading' => 'Loading…',
    'error' => "The progression could not be loaded.",
    'preview' => "Preview: the 31 July 2026 tree with demo data. Your real progression arrives with the new progression API.",
    'private' => "This progression is private: its owner hides it.",
    'no_model' => "No progression tree is published yet.",
    'login_cta' => 'Log in',
    'login_hint' => 'Log in to follow your own progress on this tree.',
    'search' => 'Search milestones, items, mods',
    'next_title' => 'Next milestones',
    'next_empty' => 'Nothing available right now: finish the open stage to unlock the next one.',
    'privacy_public' => 'Public progress',
    'privacy_hint' => 'Anyone can see this progress. Turn it off to show it only to your team.',
    'privacy_failed' => 'The setting could not be saved. Try again.',
    'stats_link' => 'Statistics',
    'embed' => [
        'no_session' => 'Open this page from the game to see your own progress.',
        'target_set' => 'Target this milestone',
        'target_clear' => 'Targeted: click to clear',
        'target_done' => 'Already done',
        'target_locked' => 'Not available yet',
        'target_pending' => 'Sending…',
        'target_refused' => 'The server refused the target. Try again.',
    ],
];
