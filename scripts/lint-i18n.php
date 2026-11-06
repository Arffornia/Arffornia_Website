<?php

/**
 * Flags user-visible text hardcoded in Blade views instead of going through __().
 *
 * Checks, in resources/views/**.blade.php:
 *   - text nodes:          >Some words<
 *   - text-bearing attributes: title/alt/placeholder/aria-label/value="Some words"
 * Blade echoes ({{ }}, {!! !!}), directives and comments are stripped first.
 * And, in app/Http/Controllers, app/Http/Middleware and app/Services (API JSON bodies, see ADR 0009
 * "Player-visible text in data and APIs"):
 *   - literal values of 'message' / 'error' keys:   ['message' => 'Some words']
 *   - literal abort() messages:                     abort(404, 'Some words')
 *   Log:: lines are skipped (logs are for developers, not players).
 * Known exceptions (brand names, OS names, symbols) go in .i18n-lint-ignore, one per line:
 *   - "text:<exact text>"   ignores that text everywhere
 *   - "file:<path>"         ignores a whole file (relative to the repo root)
 *
 * Usage: php scripts/lint-i18n.php   (exit code 1 when something is found)
 */

$root = dirname(__DIR__);
$ignoreFile = $root . '/.i18n-lint-ignore';
$ignoredTexts = [];
$ignoredFiles = [];
if (is_file($ignoreFile)) {
    foreach (file($ignoreFile, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_starts_with($line, 'file:')) {
            $ignoredFiles[] = trim(substr($line, 5));
        } elseif (str_starts_with($line, 'text:')) {
            $ignoredTexts[] = trim(substr($line, 5));
        }
    }
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/resources/views'));
$problems = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }
    $relative = substr($file->getPathname(), strlen($root) + 1);
    if (in_array($relative, $ignoredFiles, true)) {
        continue;
    }

    $source = file_get_contents($file->getPathname());
    // Blank out what is not literal text, keeping newlines so line numbers stay right.
    $blank = fn (array $m) => preg_replace('/[^\n]/', ' ', $m[0]);
    $clean = preg_replace_callback('/\{\{--.*?--\}\}/s', $blank, $source);
    $clean = preg_replace_callback('/<script\b.*?<\/script>|<style\b.*?<\/style>|<svg\b.*?<\/svg>/si', $blank, $clean);
    $clean = preg_replace_callback('/@php\b.*?@endphp/s', $blank, $clean);
    $clean = preg_replace_callback('/\{!!.*?!!\}|\{\{.*?\}\}/s', $blank, $clean);
    $clean = preg_replace_callback('/@[a-zA-Z]+(?:\s*(\((?:[^()]|(?1))*\)))?/', $blank, $clean);
    // Bound component attributes (:title="__('...')") are PHP expressions, not text.
    $clean = preg_replace_callback('/(?<=\s):[a-zA-Z-]+\s*=\s*"[^"]*"/', $blank, $clean);

    $patterns = [
        // Text between tags containing at least one letter
        '/>([^<>]*\p{L}[^<>]*)</u',
        // Text-bearing attributes
        '/(?<![:\w-])(?:title|alt|placeholder|aria-label|value|label)\s*=\s*"([^"]*\p{L}[^"]*)"/u',
    ];

    foreach ($patterns as $pattern) {
        if (!preg_match_all($pattern, $clean, $matches, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        foreach ($matches[1] as [$text, $offset]) {
            $text = trim(preg_replace('/\s+/', ' ', $text));
            if ($text === '' || in_array($text, $ignoredTexts, true)) {
                continue;
            }
            $line = substr_count($clean, "\n", 0, $offset) + 1;
            $problems[] = "$relative:$line: hardcoded text \"$text\" (use __() and lang/{en,fr})";
        }
    }
}

// API JSON messages: message/error values and abort() texts must go through __() (lang/{en,fr}/api.php).
$phpDirs = ['app/Http/Controllers', 'app/Http/Middleware', 'app/Services'];
$phpPatterns = [
    '/[\'"](?:message|error)[\'"]\s*=>\s*([\'"])((?:(?!\1).)*\p{L}(?:(?!\1).)*)\1/u',
    '/\babort\(\s*\d+\s*,\s*([\'"])((?:(?!\1).)*\p{L}(?:(?!\1).)*)\1/u',
];
foreach ($phpDirs as $dir) {
    if (!is_dir($root . '/' . $dir)) {
        continue;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir)) as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
            continue;
        }
        $relative = substr($file->getPathname(), strlen($root) + 1);
        if (in_array($relative, $ignoredFiles, true)) {
            continue;
        }
        foreach (file($file->getPathname()) as $index => $line) {
            if (str_contains($line, 'Log::')) {
                continue;
            }
            foreach ($phpPatterns as $pattern) {
                if (!preg_match_all($pattern, $line, $matches)) {
                    continue;
                }
                foreach ($matches[2] as $text) {
                    if (in_array($text, $ignoredTexts, true)) {
                        continue;
                    }
                    $problems[] = "$relative:" . ($index + 1) . ": hardcoded API message \"$text\" (use __('api.*') and lang/{en,fr}/api.php)";
                }
            }
        }
    }
}

if ($problems) {
    fwrite(STDERR, implode("\n", $problems) . "\n");
    fwrite(STDERR, count($problems) . " hardcoded string(s). See AGENTS.md (i18n) or add a justified entry to .i18n-lint-ignore.\n");
    exit(1);
}

echo "i18n lint: no hardcoded text in Blade views or API messages.\n";
