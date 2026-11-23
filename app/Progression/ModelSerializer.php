<?php

namespace App\Progression;

use App\Models\Milestone;
use App\Models\MilestoneClosure;
use App\Models\ProgressionGraph;
use App\Models\Stage;

/**
 * Turns the editable tables (the working draft) into a snapshot document that follows
 * progression-model.schema.json. Rows the schema cannot hold are left out and reported.
 */
class ModelSerializer
{
    /** @var list<Finding> */
    private array $findings = [];

    /** @var array<int, string> milestone row id => key used in the last document, provisional ones included */
    public array $keysById = [];

    /** @return array{0: array<string, mixed>, 1: list<Finding>} */
    public function draft(): array
    {
        $this->findings = [];

        $stages = Stage::orderBy('number')->get();
        $stageNumbers = $stages->pluck('number', 'id');
        $graphs = ProgressionGraph::orderBy('id')->get();
        $milestones = Milestone::with(['requirements', 'unlocks.recipe'])->orderBy('id')->get();

        $this->provisionalKeys($graphs, $milestones);
        $graphKeys = $graphs->pluck('key', 'id')->all();
        $this->keysById = $milestones->pluck('key', 'id')->all();
        $documentGraphs = $graphs->map(fn (ProgressionGraph $graph) => $this->graph($graph, $milestones->where('graph_id', $graph->id)))->values()->all();
        if ($milestones->contains(fn (Milestone $m) => $m->graph_id === null || !isset($graphKeys[$m->graph_id]))) {
            $documentGraphs[] = ['key' => Keys::FALLBACK_GRAPH, 'name' => ['en' => 'Arffornia'], 'mod_id' => null, 'branch' => 'neutral'];
        }

        $milestoneKeys = $milestones->pluck('key', 'id')->all();
        $requires = [];
        foreach (MilestoneClosure::orderBy('id')->get() as $link) {
            $parent = $milestoneKeys[$link->milestone_id] ?? null;
            $child = $milestoneKeys[$link->descendant_id] ?? null;
            if ($parent === null || $child === null) {
                $this->finding('warning', 'dangling_link', "link {$link->id}", 'points to a milestone that no longer exists; left out');
                continue;
            }
            if (!in_array($parent, $requires[$link->descendant_id] ?? [], true)) {
                $requires[$link->descendant_id][] = $parent;
            }
        }

        $documentMilestones = [];
        foreach ($milestones as $milestone) {
            if (!isset($stageNumbers[$milestone->stage_id])) {
                $this->finding('error', 'unknown_stage', (string) $milestone->key, "stage row {$milestone->stage_id} does not exist; left out");
                continue;
            }
            $documentMilestones[] = $this->milestone(
                $milestone,
                $graphKeys[$milestone->graph_id] ?? Keys::FALLBACK_GRAPH,
                $stageNumbers[$milestone->stage_id],
                $requires[$milestone->id] ?? [],
            );
        }

        $document = [
            'schema_version' => 1,
            'version' => 0,
            'status' => 'draft',
            'source' => 'website',
            'stages' => $stages->map(fn (Stage $stage) => array_filter([
                'number' => $stage->number,
                'name' => self::text($stage->name, $stage->name_fr),
                'description' => $stage->description ? self::text($stage->description, $stage->description_fr) : null,
                'threshold' => (float) $stage->threshold,
                'module' => array_values($stage->module ?? []),
            ], fn ($value) => $value !== null))->values()->all(),
            'graphs' => $documentGraphs,
            'milestones' => $documentMilestones,
        ];

        return [$document, $this->findings];
    }

    /**
     * Rows created before keys existed get, in memory only, the key ModelService::ensureKeys would give
     * them, so reading or validating the draft never writes.
     */
    private function provisionalKeys($graphs, $milestones): void
    {
        $usedGraphs = array_flip(array_filter($graphs->pluck('key')->all()));
        foreach ($graphs as $graph) {
            if ($graph->key === null) {
                $key = Keys::slug($graph->name);
                while (isset($usedGraphs[$key])) {
                    $key .= '_2';
                }
                $usedGraphs[$key] = true;
                $graph->key = $key;
            }
        }
        $graphKeys = $graphs->pluck('key', 'id')->all();
        $used = array_flip(array_filter($milestones->pluck('key')->all()));
        foreach ($milestones as $milestone) {
            if ($milestone->key === null) {
                $base = ($graphKeys[$milestone->graph_id] ?? Keys::FALLBACK_GRAPH) . '/' . Keys::slug($milestone->name);
                $milestone->key = Keys::uniqueMilestoneKey($base, fn (string $key) => isset($used[$key]));
                $used[$milestone->key] = true;
            }
        }
    }

    private function graph(ProgressionGraph $graph, $milestones): array
    {
        $result = [
            'key' => $graph->key,
            'name' => self::text($graph->name, $graph->name_fr),
            'mod_id' => $graph->mod_id ?? self::dominantNamespace($milestones),
            'branch' => $graph->branch ?? 'neutral',
        ];
        if ($graph->icon_item_id && preg_match(Keys::ITEM_PATTERN, $graph->icon_item_id)) {
            $result['icon_item'] = $graph->icon_item_id;
        }

        return $result;
    }

    private function milestone(Milestone $milestone, string $graphKey, int $stage, array $requires): array
    {
        $unlocks = [];
        foreach ($milestone->unlocks->sortBy('id') as $unlock) {
            if (!preg_match(Keys::ITEM_PATTERN, (string) $unlock->item_id)) {
                $this->finding('warning', 'bad_item_id', $milestone->key, "unlock item '{$unlock->item_id}' is not a valid id; left out");
                continue;
            }
            $recipe = $unlock->recipe;
            $unlocks[] = [
                'item' => $unlock->item_id,
                'gate_mode' => $unlock->gate_mode ?? ($recipe ? 'machine' : 'crafting'),
                'recipes_to_ban' => array_values(array_map('strval', $unlock->recipes_to_ban ?? [])),
                'recipe' => $recipe ? array_filter([
                    'type' => $recipe->type,
                    'ingredients' => $recipe->ingredients,
                    'result' => $recipe->result,
                    'energy' => $recipe->energy,
                    'time' => $recipe->time,
                ], fn ($value) => $value !== null) : null,
            ];
        }

        $entry = [
            'key' => $milestone->key,
            'graph' => $graphKey,
            'stage' => $stage,
            'title' => self::text($milestone->name, $milestone->name_fr),
            'requires' => $requires,
            'validation' => $this->validation($milestone),
            'unlocks' => $unlocks,
            'effort' => $milestone->effort,
            'power' => $milestone->power ?: null,
            'source' => $milestone->source ?? 'manual',
            'layout' => ['x' => (int) $milestone->x, 'y' => (int) $milestone->y],
        ];
        if ($milestone->description) {
            $entry['description'] = self::text($milestone->description, $milestone->description_fr);
        }
        if ($milestone->icon_type) {
            $entry['icon_type'] = $milestone->icon_type;
        }

        return $entry;
    }

    /** Delivering the requirements in the Space Elevator is the only way to complete a milestone (docs ADR 0017). */
    private function validation(Milestone $milestone): ?array
    {
        $requirements = $milestone->requirements->sortBy('id')->filter(fn ($r) => preg_match(Keys::ITEM_PATTERN, (string) $r->item_id));
        if ($requirements->isNotEmpty()) {
            return ['type' => 'deliver', 'items' => $requirements->map(fn ($r) => ['item' => $r->item_id, 'count' => max(1, (int) $r->amount)])->values()->all()];
        }

        return null;
    }

    private static function dominantNamespace($milestones): ?string
    {
        $counts = [];
        foreach ($milestones as $milestone) {
            foreach ($milestone->unlocks as $unlock) {
                if (str_contains((string) $unlock->item_id, ':')) {
                    $namespace = explode(':', $unlock->item_id, 2)[0];
                    if ($namespace !== 'minecraft') {
                        $counts[$namespace] = ($counts[$namespace] ?? 0) + 1;
                    }
                }
            }
        }
        if ($counts === []) {
            return null;
        }
        arsort($counts);

        return (string) array_key_first($counts);
    }

    public static function text(?string $en, ?string $fr = null): array
    {
        $text = ['en' => (string) $en];
        if ($fr !== null && $fr !== '') {
            $text['fr'] = $fr;
        }

        return $text;
    }

    private function finding(string $severity, string $code, string $subject, string $message): void
    {
        $this->findings[] = new Finding($severity, $code, $subject, $message);
    }
}
