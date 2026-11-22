<?php

namespace App\Rewards;

use RuntimeException;

/**
 * Crate definitions for the shop preview (spec 020). The mod owns the real ones, `data/<ns>/arffornia/crate/<id>.json`
 * and their loot tables, which the website cannot read. Until the mod publishes them, resources/crates/crates.json
 * mirrors each crate file and adds `loot`: the entries of the crate's single-roll loot table, so the odds are
 * weight / total weight. Keep it in step with the mod's files.
 */
final class CrateCatalog
{
    public function __construct(private readonly string $path)
    {
    }

    /**
     * @return list<array{id: string, translate: string, loot: list<array{item: string, min: int, max: int, rare: bool, odds: float}>}>
     */
    public function all(): array
    {
        $document = json_decode((string) file_get_contents($this->path), true, flags: JSON_THROW_ON_ERROR);
        if (($document['format'] ?? null) !== 1 || ! is_array($document['crates'] ?? null)) {
            throw new RuntimeException("{$this->path} is not a format 1 crate file.");
        }

        $crates = [];
        foreach ($document['crates'] as $id => $crate) {
            $crates[] = [
                'id' => $id,
                'translate' => $crate['name']['translate'],
                'loot' => self::odds($crate['loot'] ?? []),
            ];
        }

        return $crates;
    }

    /**
     * Chance of each entry in percent, rounded to two decimals.
     *
     * @param list<array{item: string, weight: int, min?: int, max?: int, rare?: bool}> $entries
     * @return list<array{item: string, min: int, max: int, rare: bool, odds: float}>
     */
    public static function odds(array $entries): array
    {
        $total = array_sum(array_column($entries, 'weight'));

        return array_map(fn (array $entry) => [
            'item' => $entry['item'],
            'min' => $entry['min'] ?? 1,
            'max' => $entry['max'] ?? $entry['min'] ?? 1,
            'rare' => $entry['rare'] ?? false,
            'odds' => round($entry['weight'] * 100 / $total, 2),
        ], $entries);
    }
}
