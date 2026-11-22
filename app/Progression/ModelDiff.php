<?php

namespace App\Progression;

/**
 * What changed between two model documents (spec 019): stages, graphs and milestones added, removed or
 * changed, milestones moved to another stage or graph, links and unlocks added, removed or changed.
 * Version, status and layout are not content and are ignored.
 */
final class ModelDiff
{
    private const MILESTONE_FIELDS = ['title', 'description', 'icon_type', 'validation', 'effort', 'power', 'source'];

    /** @return array<string, mixed> */
    public function between(array $from, array $to): array
    {
        $stages = $this->entities(self::byKey($from['stages'] ?? [], 'number'), self::byKey($to['stages'] ?? [], 'number'));
        $graphs = $this->entities(self::byKey($from['graphs'] ?? [], 'key'), self::byKey($to['graphs'] ?? [], 'key'));

        $old = self::byKey($from['milestones'] ?? [], 'key');
        $new = self::byKey($to['milestones'] ?? [], 'key');
        $milestones = ['added' => array_values(array_diff(array_keys($new), array_keys($old))), 'removed' => array_values(array_diff(array_keys($old), array_keys($new))), 'moved' => [], 'changed' => []];
        $links = ['added' => [], 'removed' => []];
        $unlocks = ['added' => [], 'removed' => [], 'changed' => []];

        foreach ($new as $key => $milestone) {
            $before = $old[$key] ?? null;
            $requiresBefore = $before['requires'] ?? [];
            foreach (array_diff($milestone['requires'], $requiresBefore) as $required) {
                $links['added'][] = ['from' => $required, 'to' => $key];
            }
            foreach (array_diff($requiresBefore, $milestone['requires']) as $required) {
                $links['removed'][] = ['from' => $required, 'to' => $key];
            }
            $this->unlocks($key, $before['unlocks'] ?? [], $milestone['unlocks'], $unlocks);
            if ($before === null) {
                continue;
            }
            if ($before['stage'] !== $milestone['stage'] || $before['graph'] !== $milestone['graph']) {
                $milestones['moved'][] = ['key' => $key, 'from' => ['stage' => $before['stage'], 'graph' => $before['graph']], 'to' => ['stage' => $milestone['stage'], 'graph' => $milestone['graph']]];
            }
            $fields = array_values(array_filter(self::MILESTONE_FIELDS, fn ($f) => ($before[$f] ?? null) !== ($milestone[$f] ?? null)));
            if ($fields !== []) {
                $milestones['changed'][] = ['key' => $key, 'fields' => $fields];
            }
        }
        foreach ($old as $key => $milestone) {
            if (!isset($new[$key])) {
                foreach ($milestone['requires'] as $required) {
                    $links['removed'][] = ['from' => $required, 'to' => $key];
                }
                $this->unlocks($key, $milestone['unlocks'], [], $unlocks);
            }
        }

        $diff = compact('stages', 'graphs', 'milestones', 'links', 'unlocks');
        $diff['empty'] = $this->isEmpty($diff);

        return $diff;
    }

    /** @return array<string, string> milestone key => added, moved or changed, for highlights on the graph */
    public static function marks(array $diff): array
    {
        $marks = [];
        foreach ($diff['milestones']['changed'] as $c) {
            $marks[$c['key']] = 'changed';
        }
        foreach ([...$diff['links']['added'], ...$diff['links']['removed']] as $l) {
            $marks[$l['to']] = 'changed';
        }
        foreach ([...$diff['unlocks']['added'], ...$diff['unlocks']['removed'], ...$diff['unlocks']['changed']] as $u) {
            $marks[$u['milestone']] = 'changed';
        }
        foreach ($diff['milestones']['moved'] as $m) {
            $marks[$m['key']] = 'moved';
        }
        foreach ($diff['milestones']['added'] as $key) {
            $marks[$key] = 'added';
        }

        return $marks;
    }

    private function unlocks(string $milestone, array $before, array $after, array &$result): void
    {
        $old = self::byKey($before, 'item');
        $new = self::byKey($after, 'item');
        foreach ($new as $item => $unlock) {
            if (!isset($old[$item])) {
                $result['added'][] = ['milestone' => $milestone, 'item' => $item];
                continue;
            }
            $fields = array_values(array_filter(['gate_mode', 'recipes_to_ban', 'recipe'], fn ($f) => ($old[$item][$f] ?? null) !== ($unlock[$f] ?? null)));
            if ($fields !== []) {
                $result['changed'][] = ['milestone' => $milestone, 'item' => $item, 'fields' => $fields];
            }
        }
        foreach (array_diff(array_keys($old), array_keys($new)) as $item) {
            $result['removed'][] = ['milestone' => $milestone, 'item' => $item];
        }
    }

    private function entities(array $old, array $new): array
    {
        $changed = [];
        foreach ($new as $key => $entity) {
            if (!isset($old[$key])) {
                continue;
            }
            $fields = array_values(array_filter(array_unique([...array_keys($entity), ...array_keys($old[$key])]), fn ($f) => ($old[$key][$f] ?? null) !== ($entity[$f] ?? null)));
            if ($fields !== []) {
                $changed[] = ['key' => $key, 'fields' => $fields];
            }
        }

        return [
            'added' => array_values(array_diff(array_keys($new), array_keys($old))),
            'removed' => array_values(array_diff(array_keys($old), array_keys($new))),
            'changed' => $changed,
        ];
    }

    private function isEmpty(array $diff): bool
    {
        foreach ($diff as $section) {
            foreach ($section as $list) {
                if ($list !== []) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function byKey(array $entities, string $field): array
    {
        $keyed = [];
        foreach ($entities as $entity) {
            $keyed[$entity[$field]] = $entity;
        }

        return $keyed;
    }
}
