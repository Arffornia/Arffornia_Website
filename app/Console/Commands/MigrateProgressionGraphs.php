<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Milestone;
use Illuminate\Support\Facades\DB;

class MigrateProgressionGraphs extends Command
{
    protected $signature = 'progression:migrate-graphs';
    protected $description = 'Splits existing milestones into distinct graphs based on visual Y-coordinate proximity and normalizes heights.';

    public function handle()
    {
        DB::beginTransaction();
        try {
            // Idempotency: Reset graph_id and clear generated graphs so you can safely re-run this command
            DB::table('milestones')->update(['graph_id' => null]);
            DB::table('progression_graphs')->delete();

            // Find all root nodes (Stage 1)
            $rootMilestones = Milestone::whereHas('stage', function ($q) {
                $q->where('number', 1);
            })->get();

            if ($rootMilestones->isEmpty()) {
                $this->warn("No root milestones (Stage 1) found!");
                return;
            }

            $this->info("Found {$rootMilestones->count()} root milestones. Creating graphs...");

            $graphMap = []; // root_id => [graph_id, root_y, name]

            foreach ($rootMilestones as $root) {
                $graphId = DB::table('progression_graphs')->insertGetId([
                    'name' => $root->name,
                    'icon_item_id' => 'minecraft:grass_block',
                    'categories' => json_encode(['General']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $graphMap[$root->id] = [
                    'graph_id' => $graphId,
                    'root_y' => $root->y,
                    'name' => $root->name
                ];
            }

            $allMilestones = Milestone::all();
            $stats = [];

            foreach ($allMilestones as $milestone) {
                // Find the closest root based on Y coordinate
                $closestRootId = null;
                $minDistance = PHP_INT_MAX;

                foreach ($graphMap as $rootId => $data) {
                    $distance = abs($milestone->y - $data['root_y']);
                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $closestRootId = $rootId;
                    }
                }

                if ($closestRootId !== null) {
                    $rootData = $graphMap[$closestRootId];

                    // Assign to graph
                    $milestone->graph_id = $rootData['graph_id'];

                    // Normalize Y: (Current Y) + (0 - Root Y)
                    $deltaY = 0 - $rootData['root_y'];
                    $milestone->y = $milestone->y + $deltaY;

                    $milestone->save();

                    if (!isset($stats[$closestRootId])) {
                        $stats[$closestRootId] = 0;
                    }
                    $stats[$closestRootId]++;
                }
            }

            foreach ($graphMap as $rootId => $data) {
                $count = $stats[$rootId] ?? 0;
                $this->info("Migrated graph: '{$data['name']}' with {$count} nodes. Applied DeltaY: " . (0 - $data['root_y']));
            }

            DB::commit();
            $this->info("Migration completed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Migration failed: " . $e->getMessage());
        }
    }
}
