<?php

return [
    /*
     * Data source of the progression graph viewer (/progression), spec 018 item 3:
     * - off: the pages answer 404 (default until the spec 017 snapshot and state endpoints exist)
     * - fixture: the 31 Jul 2026 tree with ELK layout and a demo player state, from public/progression-fixture
     */
    'viewer' => env('PROGRESSION_VIEWER', 'off'),

    // Base URL of the item renders written by the Item Icon Exporter (public/images/item_textures, not in git).
    'icon_base' => env('PROGRESSION_ICON_BASE', '/images/item_textures/'),
];
