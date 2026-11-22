<?php

namespace App\Console\Commands;

use App\Models\Milestone;
use App\Models\Progression;
use App\Progression\Finding;
use App\Progression\ModelSerializer;
use App\Progression\ModelService;
use App\Progression\ModelValidator;
use App\Progression\ProgressionStateService;
use App\Progression\SnapshotStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Spec 017 item 7: the hand-made tree becomes snapshot v1, and each progression's completed milestone
 * ids become keys. Rule errors of the old tree (backward links) are published as they are and listed
 * for Brian; schema errors stop the command.
 *
 * The move runs again after the commit (it is idempotent), so a write that landed on the id list meanwhile is not lost.
 */
class PublishProgressionSnapshotV1 extends Command
{
    protected $signature = 'progression:snapshot-v1 {--dry-run : Report everything; read only, no lock}';

    protected $description = 'Publishes the current progression tree as snapshot v1 and moves player progress to milestone keys.';

    public function handle(ModelService $models, SnapshotStore $snapshots, ModelSerializer $serializer, ModelValidator $validator, ProgressionStateService $states): int
    {
        if ($snapshots->latest() !== null) {
            $this->error('A snapshot is already published; v1 is only for the first migration.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            [$document, $findings] = $serializer->draft();
            $findings = [...$findings, ...$validator->validate($document)];
            [$moved, $unknown] = $this->countProgress($serializer->keysById);
            $this->report($findings);
            $this->line("Completed milestones to move to keys: {$moved}. Ids that no longer exist: {$unknown}.");
            $schemaErrors = array_filter($findings, fn (Finding $f) => $f->code === 'schema');
            if ($schemaErrors !== []) {
                $this->error('Schema errors: the real run would publish nothing.');

                return self::FAILURE;
            }
            $this->info('Dry run: v1 would be published. Nothing was written.');

            return self::SUCCESS;
        }

        try {
            $result = DB::transaction(function () use ($models) {
                $models->ensureKeys();
                Milestone::query()->update(['source' => 'legacy']);
                $this->moveProgress();
                $result = $models->publish(null, 'v1: the hand-made tree as it was before spec 017', true, reevaluate: false);
                if ($result['snapshot'] === null) {
                    throw new SchemaErrors($result['findings']);
                }

                return $result;
            });
        } catch (SchemaErrors $e) {
            $this->report($e->findings);
            $this->error('Schema errors: nothing was published.');

            return self::FAILURE;
        }

        [$moved, $unknown] = $this->moveProgress();
        $failed = $states->reevaluateAll();
        $this->report($result['findings']);
        $this->line("Completed milestones on keys: {$moved} added after the commit, {$unknown} ids that no longer exist. Re-evaluation failures: {$failed}.");
        $this->info("Published snapshot v{$result['snapshot']->version} ({$result['snapshot']->etag}).");

        return self::SUCCESS;
    }

    /** @return array{0: int, 1: int} rows added, ids without a milestone */
    private function moveProgress(): array
    {
        $keyById = Milestone::whereNotNull('key')->pluck('key', 'id')->all();
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

    /** @return array{0: int, 1: int} */
    private function countProgress(array $keyById): array
    {
        $moved = 0;
        $unknown = 0;
        Progression::query()->select(['id', 'completed_milestones'])->orderBy('id')->chunkById(200, function ($progressions) use ($keyById, &$moved, &$unknown) {
            foreach ($progressions as $progression) {
                foreach (array_unique(array_map('intval', $progression->completed_milestones ?? [])) as $id) {
                    isset($keyById[$id]) ? $moved++ : $unknown++;
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

/** @internal carries the findings out of the rolled-back transaction */
final class SchemaErrors extends RuntimeException
{
    /** @param list<Finding> $findings */
    public function __construct(public readonly array $findings)
    {
        parent::__construct('schema errors');
    }
}
