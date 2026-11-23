<?php

namespace App\Progression;

use App\Models\Milestone;
use App\Models\MilestoneClosure;
use App\Models\MilestoneRequirement;
use App\Models\MilestoneUnlock;
use App\Models\Progression;
use App\Models\ProgressionGraph;
use App\Models\Recipe;
use App\Models\Stage;
use Illuminate\Support\Facades\DB;

/**
 * Writes a schema-valid snapshot document into the editable tables, matching rows by key so row
 * ids, item pictures and player targets survive. Used by PUT /api/progression/drafts/current.
 *
 * Until the mod and the launcher leave the old routes, these tables are also what they read: a draft
 * is live for them as soon as it is written, not when it is published.
 */
class DraftWriter
{
    /** @return list<Finding> what could not be applied as asked */
    public function write(array $document): array
    {
        return DB::transaction(function () use ($document) {
            $findings = [];
            $keyByOldId = Milestone::whereNotNull('key')->pluck('key', 'id')->all();
            $stageIds = $this->stages($document['stages']);
            $graphIds = $this->graphs($document['graphs']);
            $milestoneIds = $this->milestones($document['milestones'], $stageIds, $graphIds);

            MilestoneClosure::query()->delete();
            foreach ($document['milestones'] as $milestone) {
                foreach ($milestone['requires'] as $required) {
                    if (isset($milestoneIds[$required])) {
                        MilestoneClosure::create(['milestone_id' => $milestoneIds[$required], 'descendant_id' => $milestoneIds[$milestone['key']]]);
                    }
                }
            }

            $this->remapLegacyLists($keyByOldId, $milestoneIds);
            ProgressionGraph::whereNotIn('id', array_values($graphIds))->delete();
            $this->dropStaleStages($stageIds, $findings);

            return $findings;
        });
    }

    /** @return array<int, int> stage number => row id */
    private function stages(array $stages): array
    {
        $ids = [];
        foreach ($stages as $stage) {
            $row = Stage::firstOrNew(['number' => $stage['number']]);
            $row->fill([
                'name' => $stage['name']['en'],
                'name_fr' => $stage['name']['fr'] ?? null,
                'description' => $stage['description']['en'] ?? '',
                'description_fr' => $stage['description']['fr'] ?? null,
                'threshold' => $stage['threshold'],
                'module' => $stage['module'],
            ]);
            $row->reward_progress_points ??= 0;
            $row->save();
            $ids[$stage['number']] = $row->id;
        }

        return $ids;
    }

    private function dropStaleStages(array $ids, array &$findings): void
    {
        $referenced = Progression::distinct()->pluck('max_stage_id')->all();
        foreach (Stage::whereNotIn('id', array_values($ids))->get() as $stale) {
            if (in_array($stale->id, $referenced, true)) {
                $findings[] = new Finding('warning', 'stage_kept', "stage {$stale->number}", 'not in the document but progressions still point to it; kept');
                continue;
            }
            $stale->delete();
        }
    }

    /** @return array<string, int> graph key => row id */
    private function graphs(array $graphs): array
    {
        $ids = [];
        foreach ($graphs as $graph) {
            $row = ProgressionGraph::firstOrNew(['key' => $graph['key']]);
            $row->fill([
                'name' => $graph['name']['en'],
                'name_fr' => $graph['name']['fr'] ?? null,
                'mod_id' => $graph['mod_id'] ?? null,
                'branch' => $graph['branch'],
                'icon_item_id' => $graph['icon_item'] ?? 'minecraft:book',
            ]);
            $row->save();
            $ids[$graph['key']] = $row->id;
        }

        return $ids;
    }

    /** @return array<string, int> milestone key => row id */
    private function milestones(array $milestones, array $stageIds, array $graphIds): array
    {
        $ids = [];
        foreach ($milestones as $milestone) {
            $row = Milestone::firstOrNew(['key' => $milestone['key']]);
            $row->fill([
                'name' => $milestone['title']['en'],
                'name_fr' => $milestone['title']['fr'] ?? null,
                'description' => $milestone['description']['en'] ?? '',
                'description_fr' => $milestone['description']['fr'] ?? null,
                'stage_id' => $stageIds[$milestone['stage']],
                'graph_id' => $graphIds[$milestone['graph']] ?? null,
                'icon_type' => $milestone['icon_type'] ?? ($row->icon_type ?? 'tech'),
                'x' => $milestone['layout']['x'] ?? ($row->x ?? 0),
                'y' => $milestone['layout']['y'] ?? ($row->y ?? 0),
                'effort' => $milestone['effort'] ?? null,
                'power' => $milestone['power'] ?? null,
                'source' => $milestone['source'],
            ]);
            $row->reward_progress_points ??= 0;
            $row->save();
            $ids[$milestone['key']] = $row->id;

            $this->requirements($row, $milestone['validation']['items'] ?? []);
            $this->unlocks($row, $milestone['unlocks']);
        }

        $stale = Milestone::whereNotIn('id', array_values($ids))->pluck('id');
        if ($stale->isNotEmpty()) {
            Progression::whereIn('current_milestone_id', $stale)->update(['current_milestone_id' => null]);
            Milestone::whereIn('id', $stale)->delete();
        }

        return $ids;
    }

    /**
     * `completed_milestones` holds row ids: follow each id to its key and back to today's row, and drop
     * the ids of milestones that left the draft, so the old list and the keyed state stay in step.
     */
    private function remapLegacyLists(array $keyByOldId, array $idByKey): void
    {
        Progression::query()->whereNotNull('completed_milestones')->orderBy('id')->chunkById(200, function ($progressions) use ($keyByOldId, $idByKey) {
            foreach ($progressions as $progression) {
                $before = $progression->completed_milestones ?? [];
                $after = [];
                foreach ($before as $id) {
                    $key = $keyByOldId[(int) $id] ?? null;
                    $mapped = $key === null ? (int) $id : ($idByKey[$key] ?? null);
                    if ($mapped !== null && !in_array($mapped, $after, true)) {
                        $after[] = $mapped;
                    }
                }
                if ($after !== array_map('intval', $before)) {
                    $progression->completed_milestones = $after;
                    $progression->save();
                }
            }
        });
    }

    private function requirements(Milestone $milestone, array $items): void
    {
        $kept = [];
        foreach ($items as $item) {
            $row = MilestoneRequirement::firstOrNew(['milestone_id' => $milestone->id, 'item_id' => $item['item']]);
            $row->amount = $item['count'];
            $row->save();
            $kept[] = $row->id;
        }
        MilestoneRequirement::where('milestone_id', $milestone->id)->whereNotIn('id', $kept)->delete();
    }

    private function unlocks(Milestone $milestone, array $unlocks): void
    {
        $kept = [];
        foreach ($unlocks as $unlock) {
            $row = MilestoneUnlock::firstOrNew(['milestone_id' => $milestone->id, 'item_id' => $unlock['item']]);
            $row->fill([
                'gate_mode' => $unlock['gate_mode'],
                'recipes_to_ban' => $unlock['recipes_to_ban'] ?? [],
            ]);
            $row->save();
            $kept[] = $row->id;

            $recipe = $unlock['recipe'] ?? null;
            if ($recipe === null || !isset($recipe['type'], $recipe['ingredients'], $recipe['result'])) {
                Recipe::where('milestone_unlock_id', $row->id)->delete();
                continue;
            }
            Recipe::where('type', $recipe['type'])->where('milestone_unlock_id', '!=', $row->id)->delete();
            Recipe::updateOrCreate(['milestone_unlock_id' => $row->id], [
                'type' => $recipe['type'],
                'ingredients' => $recipe['ingredients'],
                'result' => $recipe['result'],
                'energy' => $recipe['energy'] ?? null,
                'time' => $recipe['time'] ?? null,
            ]);
        }
        MilestoneUnlock::where('milestone_id', $milestone->id)->whereNotIn('id', $kept)->delete();
    }
}
