<?php

namespace App\Progression;

use App\Models\Milestone;
use App\Models\ProgressionGraph;
use Illuminate\Support\Str;

/** Stable keys of spec 017. Same slug rules as docs/scripts/progression.py, so both tools give the same keys. */
final class Keys
{
    public const GRAPH_PATTERN = '/^[a-z0-9_]+$/';

    public const MILESTONE_PATTERN = '/^[a-z0-9_]+(\/[a-z0-9_]+)?$/';

    public const ITEM_PATTERN = '/^#?[a-z0-9_.-]+:[a-z0-9_.\/-]+$/';

    public const FALLBACK_GRAPH = 'arffornia';

    public static function slug(?string $text): string
    {
        $ascii = '';
        foreach (mb_str_split((string) $text) as $char) {
            if (strlen($char) === 1) {
                $ascii .= $char;
                continue;
            }
            // Python's NFKD + ascii-ignore keeps the base letter of an accented one and drops the rest (’, œ, ß).
            $base = Str::ascii($char);
            $ascii .= strlen($base) === 1 && ctype_alnum($base) ? $base : '';
        }
        $slug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($ascii)), '_');

        return substr($slug === '' ? 'unnamed' : $slug, 0, 100);
    }

    public static function forNewGraph(string $name): string
    {
        $key = self::slug($name);
        while (ProgressionGraph::where('key', $key)->exists()) {
            $key .= '_2';
        }

        return $key;
    }

    public static function forNewMilestone(Milestone $milestone): string
    {
        $graphKey = $milestone->graph_id ? ProgressionGraph::whereKey($milestone->graph_id)->value('key') : null;

        return self::uniqueMilestoneKey(($graphKey ?? self::FALLBACK_GRAPH) . '/' . self::slug($milestone->name), fn (string $key) => Milestone::where('key', $key)->exists());
    }

    /** @param callable(string): bool $taken */
    public static function uniqueMilestoneKey(string $base, callable $taken): string
    {
        $key = $base;
        for ($n = 2; $taken($key); $n++) {
            $key = "{$base}_{$n}";
        }

        return $key;
    }
}
