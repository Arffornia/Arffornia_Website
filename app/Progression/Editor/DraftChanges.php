<?php

namespace App\Progression\Editor;

/**
 * Entity-level changes between two documents: one entry per stage, graph or milestone that was added,
 * removed or changed, with its value before and after and its position. Applying them forward or in
 * reverse is what undo and redo do, whatever the action was.
 *
 * A change is `['type' => 'stage'|'graph'|'milestone', 'key' => string, 'index' => int, 'before' => ?array, 'after' => ?array]`;
 * `index` is the position in the list the entity is missing from on the other side.
 */
final class DraftChanges
{
    private const LISTS = ['stage' => 'stages', 'graph' => 'graphs', 'milestone' => 'milestones'];

    /** @return list<array<string, mixed>> */
    public static function between(array $before, array $after): array
    {
        $changes = [];
        foreach (self::LISTS as $type => $list) {
            $old = self::keyed($type, $before[$list] ?? []);
            $new = self::keyed($type, $after[$list] ?? []);
            foreach ($old as $key => [$index, $entity]) {
                if (!isset($new[$key])) {
                    $changes[] = ['type' => $type, 'key' => (string) $key, 'index' => $index, 'before' => $entity, 'after' => null];
                } elseif ($new[$key][1] !== $entity) {
                    $changes[] = ['type' => $type, 'key' => (string) $key, 'index' => $index, 'before' => $entity, 'after' => $new[$key][1]];
                }
            }
            foreach ($new as $key => [$index, $entity]) {
                if (!isset($old[$key])) {
                    $changes[] = ['type' => $type, 'key' => (string) $key, 'index' => $index, 'before' => null, 'after' => $entity];
                }
            }
        }

        return $changes;
    }

    /** @param list<array<string, mixed>> $changes */
    public static function apply(array $document, array $changes, bool $reverse = false): array
    {
        $inserts = [];
        foreach ($changes as $change) {
            $list = self::LISTS[$change['type']];
            [$from, $to] = $reverse ? [$change['after'], $change['before']] : [$change['before'], $change['after']];
            $position = self::find($change['type'], $document[$list], $change['key']);
            if ($to === null) {
                if ($position !== null) {
                    array_splice($document[$list], $position, 1);
                }
            } elseif ($from === null || $position === null) {
                $inserts[] = [$list, $change['index'], $to];
            } else {
                $document[$list][$position] = $to;
            }
        }
        usort($inserts, fn ($a, $b) => $a[1] <=> $b[1]);
        foreach ($inserts as [$list, $index, $entity]) {
            array_splice($document[$list], min($index, count($document[$list])), 0, [$entity]);
        }

        return $document;
    }

    /** @return array<string, array{0: int, 1: array}> */
    private static function keyed(string $type, array $entities): array
    {
        $keyed = [];
        foreach (array_values($entities) as $index => $entity) {
            $keyed[(string) self::keyOf($type, $entity)] = [$index, $entity];
        }

        return $keyed;
    }

    private static function find(string $type, array $entities, string $key): ?int
    {
        foreach ($entities as $index => $entity) {
            if ((string) self::keyOf($type, $entity) === $key) {
                return $index;
            }
        }

        return null;
    }

    private static function keyOf(string $type, array $entity): string|int
    {
        return $type === 'stage' ? $entity['number'] : $entity['key'];
    }
}
