<?php

// Player FAQ (/faq). Each entry has an id (the URL anchor), a question and an answer made of
// paragraphs, optional numbered steps and optional notes. Keep lang/fr/faq.php in the same shape.

return [
    'title' => 'FAQ',
    'footer' => 'FAQ',
    'entries' => [
        [
            'id' => 'mac',
            'question' => 'Can I play on a Mac?',
            'paragraphs' => [
                'Yes. The launcher runs on macOS 12 Monterey or later, on Apple Silicon (M1 and later) and on Intel Macs. It installs Java and the modpack for you.',
            ],
            'steps' => [
                'Check your chip: Apple menu > About This Mac. "Chip: Apple M…" means Apple Silicon; "Processor: Intel" means Intel.',
                'Download the matching file from the launcher releases (https://github.com/Arffornia/Arffornia_Launcher_V.5.2/releases): the "arm64" .dmg for Apple Silicon, the "x64" .dmg for Intel.',
                'Open the .dmg and drag Arffornia into Applications.',
                'First launch: open Arffornia. macOS says it cannot check the app, because Arffornia is free and not notarized by Apple. Click Done.',
                'Open System Settings > Privacy & Security, scroll down to "Arffornia was blocked", click Open Anyway and confirm with your password. You only do this once per launcher version.',
                'Sign in with Microsoft and press Play. The first start downloads Java 21 for your Mac and about 330 mods, so it takes a while.',
            ],
            'notes' => [
                'Memory: the game uses up to 8 GB. A Mac with 16 GB of memory or more is recommended; with 8 GB, close other apps.',
                'Updates: on macOS the launcher cannot update itself yet. When a new version is announced, download it again and replace the app in Applications. Your settings and worlds are kept in ~/Library/Application Support/Arffornia.',
                'Shaders: Complementary and BSL work. Voxel and ray-tracing packs need graphics features that macOS does not have.',
                'Problem? Send the file ~/Library/Application Support/Arffornia/.Arffornia_V.5.2/launcher_log.txt in the Discord support channel.',
            ],
        ],
    ],
];
