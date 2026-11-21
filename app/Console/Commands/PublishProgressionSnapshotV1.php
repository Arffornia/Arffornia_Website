<?php

namespace App\Console\Commands;

use App\Models\Milestone;
use App\Models\Progression;
use App\Progression\Finding;
use App\Progression\ModelService;
use App\Progression\SnapshotStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Spec 017 item 7: the hand-made tree becomes snapshot v1, and each progression's completed milestone
 * ids become keys. Rule errors of the old tree (backward links) are published as they are and listed
 * for Brian; schema errors stop the command.
 */
class PublishProgressionSnapshotV1 extends Command
{
    protected $signature = 'progression:snapshot-v1 {--dry-run : Report everything, write nothing}';

    protected $description = 'Publishes the current progression tree as snapshot v1 and moves player progress to milestone keys.';

    public function handle(ModelService $models, SnapshotStore $snapshots): int
    {
        if ($snapshots->latest() !== null) {
            $this->error('A snapshot is already published; v1 is only for the first migration.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        DB::beginTransaction();
        try {
            $keys = $models->ensureKeys();
            Milestone::query()->update(['source' => 'legacy']);
            [$moved, $unknown] = $this->moveProgress();
            $result = $models->publish(null, 'v1: the hand-made tree as it was before spec 017', true);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->report($result['findings']);
        $this->line("Keys assigned: {$keys}. Completed milestones moved to keys: {$moved}. Ids that no longer exist: {$unknown}.");

        if ($result['snapshot'] === null) {
            DB::rollBack();
            $this->error('Schema errors: nothing was published.');

            return self::FAILURE;
        }
        if ($dryRun) {
            DB::rollBack();
            $this->info("Dry run: snapshot v{$result['snapshot']->version} would be published. Nothing was written.");

            return self::SUCCESS;
        }

        DB::commit();
        $this->info("Published snapshot v{$result['snapshot']->version} ({$result['snapshot']->etag}).");

        return self::SUCCESS;
    }

    /** @return array{0: int, 1: int} */
    private function moveProgress(): array
    {
        $keyById = Milestone::pluck('key', 'id')->all();
        $moved = 0;
        $unknown = 0;
        Progression::query()->orderBy('id')->chunkById(200, function ($progressions) use ($keyById, &$moved, &$unknown) {
            foreach ($progressions as $progression) {
                foreach ($progression->completed_milestones ?? [] as $id) {
                    $key = $keyById[(int) $id] ?? null;
                    if ($key === null) {
                        $unknown++;
                        continue;
                    }
                    $moved += DB::table('progression_milestones')->insertOrIgnore([
                        'progression_id' => $progression->id,
                        'milestone_key' => $key,
                        'completed_at' => $progression->updated_at ?? now(),
                        'completed_by' => 'legacy',
                    ]);
                }
            }
        });

        return [$moved, $unknown];
    }

    /** @param list<Finding> $findings */
    private function report(array $findings): void
    {
        $counts = [];
        foreach ($findings as $finding) {
            $counts["{$finding->severity} {$finding->code}"] = ($counts["{$finding->severity} {$finding->code}"] ?? 0) + 1;
        }
        ksort($counts);
        $this->table(['Finding', 'Count'], array_map(fn ($k, $v) => [$k, $v], array_keys($counts), $counts));
        foreach ($findings as $finding) {
            if ($finding->severity !== 'info') {
                $this->line("{$finding->severity} {$finding->code} {$finding->subject}: {$finding->message}");
            }
        }
    }
}
