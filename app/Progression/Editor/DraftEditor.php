<?php

namespace App\Progression\Editor;

use App\Progression\Keys;

/**
 * The actions of the admin editor (spec 019), each a pure function from a draft document to the next
 * one. They cover what the old `/stages` editor did on the live tables: graphs, stages, milestones,
 * links, unlocks with their recipe, and delivered requirements. There is no shop price (decision Q3).
 */
final class DraftEditor
{
    public const ACTIONS = [
        'graph.create', 'graph.update', 'graph.delete',
        'stage.create', 'stage.update', 'stage.delete',
        'milestone.create', 'milestone.update', 'milestone.move', 'milestone.delete',
        'link.create', 'link.delete',
        'unlock.upsert', 'unlock.delete',
        'requirement.upsert', 'requirement.delete',
    ];

    public const ICON_TYPES = ['tech', 'pipe', 'magic', 'default'];

    /** Laravel validation rules of each action's payload. */
    public static function rules(string $action): array
    {
        $item = ['string', 'max:191', 'regex:' . Keys::ITEM_PATTERN];
        $key = ['required', 'string', 'max:128'];
        $text = fn (string $field, bool $required) => [
            $field => $required ? ['required', 'array'] : ['sometimes', 'nullable', 'array'],
            "{$field}.en" => $required ? ['required', 'string', 'max:2000'] : ['nullable', 'string', 'max:2000'],
            "{$field}.fr" => ['nullable', 'string', 'max:2000'],
        ];
        $graph = [
            'mod_id' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_.-]+$/'],
            'branch' => ['sometimes', 'in:tech,magic,neutral'],
            'icon_item' => ['nullable', ...$item],
        ];
        $stage = [
            'threshold' => ['sometimes', 'numeric', 'gt:0', 'max:1'],
            'module' => ['sometimes', 'array'],
            'module.*.item' => ['required', ...$item],
            'module.*.count' => ['required', 'integer', 'min:1'],
        ];
        $milestone = [
            'stage' => ['sometimes', 'integer', 'min:1'],
            'graph' => ['sometimes', 'string', 'max:64'],
            'icon_type' => ['sometimes', 'in:' . implode(',', self::ICON_TYPES)],
        ];

        return match ($action) {
            'graph.create' => [...$text('name', true), 'key' => ['nullable', 'string', 'max:64', 'regex:' . Keys::GRAPH_PATTERN], ...$graph],
            'graph.update' => ['key' => $key, ...$text('name', false), ...$graph],
            'graph.delete', 'milestone.delete' => ['key' => $key],
            'stage.create' => [...$text('name', true), ...$text('description', false), ...$stage],
            'stage.update' => ['number' => ['required', 'integer'], ...$text('name', false), ...$text('description', false), ...$stage],
            'stage.delete' => ['number' => ['required', 'integer']],
            'milestone.create' => [...$text('title', true), ...$text('description', false), ...$milestone, 'stage' => ['required', 'integer', 'min:1'], 'graph' => ['required', 'string', 'max:64']],
            'milestone.update' => [
                'key' => $key, ...$text('title', false), ...$text('description', false), ...$milestone,
            ],
            'milestone.move' => ['key' => $key, 'stage' => ['required', 'integer', 'min:1'], 'graph' => ['sometimes', 'string', 'max:64']],
            'link.create', 'link.delete' => ['from' => $key, 'to' => $key],
            'unlock.upsert' => [
                'key' => $key,
                'item' => ['required', ...$item],
                'original_item' => ['nullable', 'string', 'max:191'],
                'gate_mode' => ['required', 'in:machine,ingredient,crafting'],
                'recipes_to_ban' => ['sometimes', 'array'],
                'recipes_to_ban.*' => ['string', 'max:191'],
                'recipe' => ['sometimes', 'nullable', 'array'],
                'recipe.ingredients' => ['required_with:recipe', 'array'],
                'recipe.ingredients.*.item' => ['required_without:recipe.ingredients.*.tag', 'string', 'max:191'],
                'recipe.ingredients.*.tag' => ['required_without:recipe.ingredients.*.item', 'string', 'max:191'],
                'recipe.ingredients.*.count' => ['sometimes', 'integer', 'min:1'],
                'recipe.result' => ['required_with:recipe', 'array', 'min:1'],
                'recipe.result.*.item' => ['required', 'string', 'max:191'],
                'recipe.result.*.count' => ['sometimes', 'integer', 'min:1'],
                'recipe.energy' => ['nullable', 'integer', 'min:0'],
                'recipe.time' => ['nullable', 'integer', 'min:0'],
            ],
            'unlock.delete', 'requirement.delete' => ['key' => $key, 'item' => ['required', 'string', 'max:191']],
            'requirement.upsert' => ['key' => $key, 'item' => ['required', ...$item], 'original_item' => ['nullable', 'string', 'max:191'], 'count' => ['required', 'integer', 'min:1']],
        };
    }

    /**
     * @return array{0: array, 1: ?string} the next document, and the key of the entity the action
     *                                     created or changed (shown in the history and selected by the editor)
     */
    public function apply(array $document, string $action, array $p): array
    {
        return match ($action) {
            'graph.create' => $this->createGraph($document, $p),
            'graph.update' => [$this->updateGraph($document, $p), $p['key']],
            'graph.delete' => [$this->deleteGraph($document, $p['key']), $p['key']],
            'stage.create' => $this->createStage($document, $p),
            'stage.update' => [$this->updateStage($document, $p), "stage {$p['number']}"],
            'stage.delete' => [$this->deleteStage($document, (int) $p['number']), "stage {$p['number']}"],
            'milestone.create' => $this->createMilestone($document, $p),
            'milestone.update', 'milestone.move' => [$this->updateMilestone($document, $p), $p['key']],
            'milestone.delete' => [$this->deleteMilestone($document, $p['key']), $p['key']],
            'link.create' => [$this->link($document, $p['from'], $p['to']), $p['to']],
            'link.delete' => [$this->unlink($document, $p['from'], $p['to']), $p['to']],
            'unlock.upsert' => [$this->upsertUnlock($document, $p), $p['key']],
            'unlock.delete' => [$this->deleteUnlock($document, $p['key'], $p['item']), $p['key']],
            'requirement.upsert' => [$this->upsertRequirement($document, $p), $p['key']],
            'requirement.delete' => [$this->deleteRequirement($document, $p['key'], $p['item']), $p['key']],
            default => throw new DraftActionRefused('unknown_action'),
        };
    }

    private function createGraph(array $doc, array $p): array
    {
        $taken = array_flip(array_column($doc['graphs'], 'key'));
        if (!empty($p['key']) && isset($taken[$p['key']])) {
            throw new DraftActionRefused('graph_exists', ['key' => $p['key']]);
        }
        $key = $p['key'] ?? null;
        if (empty($key)) {
            $key = Keys::slug($p['name']['en']);
            while (isset($taken[$key])) {
                $key .= '_2';
            }
        }
        $doc['graphs'][] = $this->graphFields(['key' => $key, 'name' => ['en' => ''], 'branch' => 'neutral', 'mod_id' => null], $p);

        return [$doc, $key];
    }

    private function updateGraph(array $doc, array $p): array
    {
        $i = $this->graphIndex($doc, $p['key']);
        $doc['graphs'][$i] = $this->graphFields($doc['graphs'][$i], $p);

        return $doc;
    }

    private function graphFields(array $graph, array $p): array
    {
        if (!empty($p['name']['en'])) {
            $graph['name'] = self::text($p['name']);
        }
        if (array_key_exists('mod_id', $p)) {
            $graph['mod_id'] = $p['mod_id'];
        }
        if (isset($p['branch'])) {
            $graph['branch'] = $p['branch'];
        }
        if (array_key_exists('icon_item', $p)) {
            unset($graph['icon_item']);
            if ($p['icon_item'] !== null) {
                $graph['icon_item'] = $p['icon_item'];
            }
        }

        return $graph;
    }

    private function deleteGraph(array $doc, string $key): array
    {
        $i = $this->graphIndex($doc, $key);
        if (array_any($doc['milestones'], fn ($m) => $m['graph'] === $key)) {
            throw new DraftActionRefused('graph_not_empty', ['key' => $key]);
        }
        array_splice($doc['graphs'], $i, 1);

        return $doc;
    }

    private function createStage(array $doc, array $p): array
    {
        $number = max([0, ...array_column($doc['stages'], 'number')]) + 1;
        $stage = ['number' => $number, 'name' => self::text($p['name'])];
        if (!empty($p['description']['en'])) {
            $stage['description'] = self::text($p['description']);
        }
        $stage['threshold'] = (float) ($p['threshold'] ?? 0.8);
        $stage['module'] = self::itemCounts($p['module'] ?? []);
        $doc['stages'][] = $stage;

        return [$doc, "stage {$number}"];
    }

    private function updateStage(array $doc, array $p): array
    {
        $i = $this->stageIndex($doc, (int) $p['number']);
        $stage = $doc['stages'][$i];
        if (!empty($p['name']['en'])) {
            $stage['name'] = self::text($p['name']);
        }
        if (array_key_exists('description', $p)) {
            unset($stage['description']);
            if (!empty($p['description']['en'])) {
                $stage['description'] = self::text($p['description']);
            }
        }
        if (isset($p['threshold'])) {
            $stage['threshold'] = (float) $p['threshold'];
        }
        if (isset($p['module'])) {
            $stage['module'] = self::itemCounts($p['module']);
        }
        $doc['stages'][$i] = $stage;

        return $doc;
    }

    private function deleteStage(array $doc, int $number): array
    {
        $i = $this->stageIndex($doc, $number);
        if (array_any($doc['milestones'], fn ($m) => $m['stage'] === $number)) {
            throw new DraftActionRefused('stage_not_empty', ['number' => $number]);
        }
        array_splice($doc['stages'], $i, 1);

        return $doc;
    }

    private function createMilestone(array $doc, array $p): array
    {
        $this->graphIndex($doc, $p['graph']);
        $this->stageIndex($doc, (int) $p['stage']);
        $taken = array_flip(array_column($doc['milestones'], 'key'));
        $key = Keys::uniqueMilestoneKey($p['graph'] . '/' . Keys::slug($p['title']['en']), fn (string $k) => isset($taken[$k]));
        $milestone = [
            'key' => $key,
            'graph' => $p['graph'],
            'stage' => (int) $p['stage'],
            'title' => self::text($p['title']),
            'requires' => [],
            'validation' => null,
            'unlocks' => [],
            'effort' => null,
            'power' => null,
            'source' => 'manual',
            'icon_type' => $p['icon_type'] ?? 'tech',
        ];
        if (!empty($p['description']['en'])) {
            $milestone['description'] = self::text($p['description']);
        }
        $doc['milestones'][] = $milestone;

        return [$doc, $key];
    }

    private function updateMilestone(array $doc, array $p): array
    {
        $i = $this->milestoneIndex($doc, $p['key']);
        $m = $doc['milestones'][$i];
        if (!empty($p['title']['en'])) {
            $m['title'] = self::text($p['title']);
        }
        if (array_key_exists('description', $p)) {
            unset($m['description']);
            if (!empty($p['description']['en'])) {
                $m['description'] = self::text($p['description']);
            }
        }
        if (isset($p['stage'])) {
            $this->stageIndex($doc, (int) $p['stage']);
            $m['stage'] = (int) $p['stage'];
        }
        if (isset($p['graph'])) {
            $this->graphIndex($doc, $p['graph']);
            $m['graph'] = $p['graph'];
        }
        if (isset($p['icon_type'])) {
            $m['icon_type'] = $p['icon_type'];
        }
        $doc['milestones'][$i] = $m;

        return $doc;
    }

    private function deleteMilestone(array $doc, string $key): array
    {
        $i = $this->milestoneIndex($doc, $key);
        array_splice($doc['milestones'], $i, 1);
        foreach ($doc['milestones'] as &$m) {
            if (in_array($key, $m['requires'], true)) {
                $m['requires'] = array_values(array_diff($m['requires'], [$key]));
            }
        }

        return $doc;
    }

    private function link(array $doc, string $from, string $to): array
    {
        $this->milestoneIndex($doc, $from);
        $i = $this->milestoneIndex($doc, $to);
        if ($from === $to) {
            throw new DraftActionRefused('link_self');
        }
        if (in_array($from, $doc['milestones'][$i]['requires'], true)) {
            throw new DraftActionRefused('link_exists');
        }
        $doc['milestones'][$i]['requires'][] = $from;

        return $doc;
    }

    private function unlink(array $doc, string $from, string $to): array
    {
        $i = $this->milestoneIndex($doc, $to);
        if (!in_array($from, $doc['milestones'][$i]['requires'], true)) {
            throw new DraftActionRefused('link_missing');
        }
        $doc['milestones'][$i]['requires'] = array_values(array_diff($doc['milestones'][$i]['requires'], [$from]));

        return $doc;
    }

    private function upsertUnlock(array $doc, array $p): array
    {
        $i = $this->milestoneIndex($doc, $p['key']);
        $unlocks = $doc['milestones'][$i]['unlocks'];
        $position = $this->itemIndex($unlocks, $p['original_item'] ?? $p['item']);
        $clash = $this->itemIndex($unlocks, $p['item']);
        if ($clash !== null && $clash !== $position) {
            throw new DraftActionRefused('item_exists', ['item' => $p['item']]);
        }
        $unlock = ['item' => $p['item'], 'gate_mode' => $p['gate_mode'], 'recipes_to_ban' => array_values(array_filter(array_map('trim', $p['recipes_to_ban'] ?? []), 'strlen'))];
        $recipe = $p['recipe'] ?? null;
        $unlock['recipe'] = $recipe === null ? null : array_filter([
            'type' => 'arffornia:' . str_replace(':', '_', $p['item']) . '_recipe',
            'ingredients' => array_values($recipe['ingredients']),
            'result' => array_values($recipe['result']),
            'energy' => $recipe['energy'] ?? null,
            'time' => $recipe['time'] ?? null,
        ], fn ($value) => $value !== null);
        if ($position === null) {
            $unlocks[] = $unlock;
        } else {
            $unlocks[$position] = $unlock;
        }
        $doc['milestones'][$i]['unlocks'] = $unlocks;

        return $doc;
    }

    private function deleteUnlock(array $doc, string $key, string $item): array
    {
        $i = $this->milestoneIndex($doc, $key);
        $position = $this->itemIndex($doc['milestones'][$i]['unlocks'], $item) ?? throw new DraftActionRefused('item_missing', ['item' => $item]);
        array_splice($doc['milestones'][$i]['unlocks'], $position, 1);

        return $doc;
    }

    /** Requirements are the items to deliver in the Space Elevator, the milestone's only validation (docs ADR 0017). */
    private function upsertRequirement(array $doc, array $p): array
    {
        $i = $this->milestoneIndex($doc, $p['key']);
        $validation = $doc['milestones'][$i]['validation'];
        $items = ($validation['type'] ?? null) === 'deliver' ? $validation['items'] : [];
        $position = $this->itemIndex($items, $p['original_item'] ?? $p['item']);
        $clash = $this->itemIndex($items, $p['item']);
        if ($clash !== null && $clash !== $position) {
            throw new DraftActionRefused('item_exists', ['item' => $p['item']]);
        }
        $entry = ['item' => $p['item'], 'count' => (int) $p['count']];
        if ($position === null) {
            $items[] = $entry;
        } else {
            $items[$position] = $entry;
        }
        $doc['milestones'][$i]['validation'] = ['type' => 'deliver', 'items' => $items];

        return $doc;
    }

    private function deleteRequirement(array $doc, string $key, string $item): array
    {
        $i = $this->milestoneIndex($doc, $key);
        $validation = $doc['milestones'][$i]['validation'];
        $items = ($validation['type'] ?? null) === 'deliver' ? $validation['items'] : [];
        $position = $this->itemIndex($items, $item) ?? throw new DraftActionRefused('item_missing', ['item' => $item]);
        array_splice($items, $position, 1);
        $doc['milestones'][$i]['validation'] = $items === [] ? null : ['type' => 'deliver', 'items' => $items];

        return $doc;
    }

    private function graphIndex(array $doc, string $key): int
    {
        return $this->indexOf($doc['graphs'], 'key', $key) ?? throw new DraftActionRefused('unknown_graph', ['key' => $key]);
    }

    private function stageIndex(array $doc, int $number): int
    {
        return $this->indexOf($doc['stages'], 'number', $number) ?? throw new DraftActionRefused('unknown_stage', ['number' => $number]);
    }

    private function milestoneIndex(array $doc, string $key): int
    {
        return $this->indexOf($doc['milestones'], 'key', $key) ?? throw new DraftActionRefused('unknown_milestone', ['key' => $key]);
    }

    private function itemIndex(array $entries, string $item): ?int
    {
        return $this->indexOf($entries, 'item', $item);
    }

    private function indexOf(array $entities, string $field, string|int $value): ?int
    {
        foreach ($entities as $index => $entity) {
            if (($entity[$field] ?? null) === $value) {
                return $index;
            }
        }

        return null;
    }

    private static function text(array $text): array
    {
        $result = ['en' => (string) $text['en']];
        if (isset($text['fr']) && $text['fr'] !== '') {
            $result['fr'] = $text['fr'];
        }

        return $result;
    }

    private static function itemCounts(array $entries): array
    {
        return array_values(array_map(fn ($e) => ['item' => $e['item'], 'count' => (int) $e['count']], $entries));
    }
}
