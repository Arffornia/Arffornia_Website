<?php

namespace Tests\Feature\Progression;

use App\Models\ModelSnapshot;
use App\Models\Progression;
use App\Progression\ModelValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Spec 017 item 7, on Brian's tree of 31 Jul 2026 (copied from Arffornia/docs). */
class SnapshotV1CommandTest extends TestCase
{
    use RefreshDatabase;

    private function loadTree(): array
    {
        $tree = json_decode(gzdecode(file_get_contents(base_path('tests/Fixtures/progression/current-tree-2026-07-31.json.gz'))), true);
        $now = now();
        foreach ($tree['graphs'] as $graph) {
            DB::table('progression_graphs')->insert(['id' => $graph['id'], 'name' => $graph['name'], 'icon_item_id' => $graph['icon_item_id'], 'categories' => json_encode($graph['categories']), 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ($tree['stages'] as $stage) {
            DB::table('stages')->insert(['id' => $stage['id'], 'number' => $stage['number'], 'name' => $stage['name'], 'description' => $stage['description'], 'reward_progress_points' => $stage['reward_progress_points']]);
        }
        foreach ($tree['milestones'] as $m) {
            DB::table('milestones')->insert(['id' => $m['id'], 'name' => $m['name'], 'description' => $m['description'], 'stage_id' => $m['stage_id'], 'reward_progress_points' => $m['reward_progress_points'], 'icon_type' => $m['icon_type'], 'x' => $m['x'], 'y' => $m['y'], 'graph_id' => $m['graph_id']]);
            foreach ($m['requirements'] as $r) {
                DB::table('milestone_requirements')->insert(['milestone_id' => $m['id'], 'item_id' => $r['item_id'], 'display_name' => $r['display_name'] ?? null, 'amount' => $r['amount']]);
            }
            foreach ($m['unlocks'] as $u) {
                $unlockId = DB::table('milestone_unlocks')->insertGetId(['milestone_id' => $m['id'], 'item_id' => $u['item_id'], 'display_name' => $u['display_name'] ?? null, 'recipes_to_ban' => json_encode($u['recipes_to_ban'] ?? []), 'shop_price' => $u['shop_price'] ?? null]);
                if (!empty($u['recipe'])) {
                    $recipe = $u['recipe'];
                    DB::table('recipes')->insert(['type' => $recipe['type'], 'ingredients' => json_encode($recipe['ingredients']), 'result' => json_encode($recipe['result']), 'energy' => $recipe['energy'] ?? null, 'time' => $recipe['time'] ?? null, 'milestone_unlock_id' => $unlockId]);
                }
            }
        }
        foreach ($tree['milestone_closure'] as $link) {
            DB::table('milestone_closure')->insert(['milestone_id' => $link['milestone_id'], 'descendant_id' => $link['descendant_id']]);
        }

        return $tree;
    }

    public function test_the_hand_made_tree_becomes_a_schema_valid_v1_and_progress_moves_to_keys(): void
    {
        $tree = $this->loadTree();
        $done = array_slice(array_column($tree['milestones'], 'id'), 0, 3);
        $progression = Progression::create(['max_stage_id' => $tree['stages'][0]['id'], 'completed_milestones' => [...$done, 999999]]);

        $this->artisan('progression:snapshot-v1')->assertSuccessful();

        $document = json_decode(ModelSnapshot::where('version', 1)->value('document'), true);
        $this->assertSame([], app(ModelValidator::class)->schemaErrors($document));
        $this->assertCount(194, $document['milestones']);
        $this->assertCount(11, $document['stages']);
        $this->assertSame(3, DB::table('progression_milestones')->where('progression_id', $progression->id)->count());
        $backward = array_filter(app(ModelValidator::class)->ruleFindings($document), fn ($f) => $f->code === 'requires_later_stage');
        $this->assertNotEmpty($backward);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->loadTree();

        $this->artisan('progression:snapshot-v1', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, ModelSnapshot::count());
        $this->assertSame(0, DB::table('milestones')->whereNotNull('key')->count());
    }
}
