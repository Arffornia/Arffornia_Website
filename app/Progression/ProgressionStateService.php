<?php

namespace App\Progression;

use App\Models\Milestone;
use App\Models\Progression;
use App\Models\Stage;
use App\Repositories\TeamRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Progression state of spec 017: done milestones and deliveries stored by key, stages opened by the
 * published snapshot's rules (threshold of the stage's milestones plus its Noyau module). Stages never close.
 */
class ProgressionStateService
{
    public const EVENT_MILESTONE = 'milestone_completed';

    public const EVENT_MODULE = 'module_delivered';

    public const EVENT_STAGE = 'stage_opened';

    public function __construct(
        private readonly SnapshotStore $snapshots,
        private readonly TeamRepository $teams,
    ) {
    }

    public function snapshot(): ?Snapshot
    {
        return $this->snapshots->current();
    }

    /** @return array<string, mixed>|null null while no snapshot is published */
    public function state(Progression $progression): ?array
    {
        $snapshot = $this->snapshots->current();
        if ($snapshot === null) {
            return null;
        }
        if (!self::evaluatedWith($progression, $snapshot)) {
            $progression = $this->evaluateLocked($progression->id, $snapshot) ?? $progression;
        }

        $completed = DB::table('progression_milestones')->where('progression_id', $progression->id)
            ->orderBy('completed_at')->orderBy('id')->get(['milestone_key', 'completed_at']);
        $done = array_flip($completed->pluck('milestone_key')->all());
        $delivered = $this->deliveries($progression->id);
        $open = $this->openStage($progression, $snapshot);

        $stages = [];
        foreach ($snapshot->stages as $number => $stage) {
            $keys = $snapshot->milestonesByStage[$number] ?? [];
            $doneCount = count(array_filter($keys, fn (string $key) => isset($done[$key])));
            $stages[] = [
                'number' => $number,
                'open' => $number <= $open,
                'done' => $doneCount,
                'total' => count($keys),
                'ratio' => $keys === [] ? 1.0 : round($doneCount / count($keys), 4),
                'threshold' => (float) $stage['threshold'],
                'module' => array_map(fn (array $item) => [
                    'item' => $item['item'],
                    'count' => $item['count'],
                    'delivered' => min($item['count'], $delivered[$number][$item['item']] ?? 0),
                ], $stage['module']),
                'module_done' => $this->moduleDone($stage['module'], $delivered[$number] ?? []),
            ];
        }

        $next = [];
        foreach ($snapshot->milestones as $key => $milestone) {
            if (!isset($done[$key]) && self::isAvailable($milestone, $done, $open)) {
                $next[] = $key;
            }
        }

        return [
            'progression_id' => $progression->id,
            'snapshot_version' => $snapshot->version,
            'retired' => $this->teams->isProgressionRetired($progression->id),
            'open_stage' => $open,
            'target' => $progression->current_milestone_id ? Milestone::whereKey($progression->current_milestone_id)->value('key') : null,
            'completed' => $completed->filter(fn ($row) => isset($snapshot->milestones[$row->milestone_key]))
                ->map(fn ($row) => ['key' => $row->milestone_key, 'completed_at' => Carbon::parse($row->completed_at)->toIso8601ZuluString()])
                ->values()->all(),
            'stages' => $stages,
            'next' => $next,
        ];
    }

    /**
     * Applies events from the game. Each event id is applied once per progression, a milestone is
     * completed once whatever the event id, so replays and reordering change nothing. A milestone that is
     * not available (rule 3: its stage is open and its prerequisites are done) is rejected with
     * `milestone_unavailable` and nothing is recorded; within a batch it is retried once the rest has
     * been applied, so a prerequisite sent after it still counts. A batch costs a fixed number of
     * queries plus two per delivery.
     *
     * @param  list<array<string, mixed>>  $events
     * @return array{retired: bool, results: list<array<string, string>>}
     */
    public function apply(Progression $progression, array $events, Snapshot $snapshot): array
    {
        return DB::transaction(function () use ($progression, $events, $snapshot) {
            $progression = Progression::whereKey($progression->id)->lockForUpdate()->firstOrFail();
            if ($this->teams->isProgressionRetired($progression->id)) {
                return ['retired' => true, 'results' => []];
            }

            $seen = array_flip(DB::table('progression_events')->where('progression_id', $progression->id)
                ->whereIn('event_id', array_column($events, 'id'))->pluck('event_id')->all());
            $done = array_flip(DB::table('progression_milestones')->where('progression_id', $progression->id)->pluck('milestone_key')->all());
            $open = $this->openStage($progression, $snapshot);
            $now = Carbon::now();
            $results = [];
            $eventRows = [];
            $completions = [];
            $unavailable = [];

            foreach ($events as $index => $event) {
                $id = $event['id'];
                if (isset($seen[$id])) {
                    $results[$index] = ['id' => $id, 'status' => 'duplicate'];
                    continue;
                }
                $seen[$id] = true;
                $at = isset($event['occurred_at']) ? Carbon::createFromTimestampMs($event['occurred_at']) : $now;
                $actor = $event['actor'] ?? null;

                if ($event['type'] === self::EVENT_MILESTONE) {
                    $key = $event['milestone'];
                    if (!isset($snapshot->milestones[$key])) {
                        $results[$index] = ['id' => $id, 'status' => 'rejected', 'reason' => 'unknown_milestone'];
                        continue;
                    }
                    if (!isset($done[$key]) && !self::isAvailable($snapshot->milestones[$key], $done, $open)) {
                        $unavailable[$index] = [$id, $key, $at, $actor];
                        continue;
                    }
                    $results[$index] = $this->completeFromEvent($progression, $snapshot, $id, $key, $at, $actor, $done, $eventRows, $completions);
                    continue;
                }

                $stage = $snapshot->stages[$event['stage']] ?? null;
                if ($stage === null || !in_array($event['item'], array_column($stage['module'], 'item'), true)) {
                    $results[$index] = ['id' => $id, 'status' => 'rejected', 'reason' => 'not_in_module'];
                    continue;
                }
                // Only the module of the highest open stage counts: it is the one that opens the next stage.
                if ((int) $event['stage'] !== $open) {
                    $results[$index] = ['id' => $id, 'status' => 'rejected', 'reason' => 'stage_not_current'];
                    continue;
                }
                $row = ['progression_id' => $progression->id, 'stage_number' => $open, 'item' => $event['item']];
                DB::table('progression_deliveries')->insertOrIgnore([...$row, 'count' => 0]);
                DB::table('progression_deliveries')->where($row)->increment('count', $event['count']);
                $eventRows[] = $this->eventRow($progression->id, self::EVENT_MODULE, $id, null, $open, ['item' => $event['item'], 'count' => $event['count']], $actor, $at, $snapshot->version);
                $results[$index] = ['id' => $id, 'status' => 'applied'];
                // A delivery may open the stage, and the next module is then the one that counts.
                $this->flushCompletions($progression, $completions);
                $open = $this->evaluate($progression, $snapshot);
            }

            // Completions earlier in the batch, or a stage they open, may make a held-back milestone available.
            // Stage opening is projected in memory so that chains cost no query per pass.
            if ($unavailable !== []) {
                $delivered = $this->deliveries($progression->id);
                do {
                    $progress = false;
                    while (($next = $snapshot->nextStage($open)) !== null && $this->passed($snapshot, $open, $done, $delivered[$open] ?? [])) {
                        $open = $next;
                    }
                    foreach ($unavailable as $index => [$id, $key, $at, $actor]) {
                        if (isset($done[$key]) || self::isAvailable($snapshot->milestones[$key], $done, $open)) {
                            $results[$index] = $this->completeFromEvent($progression, $snapshot, $id, $key, $at, $actor, $done, $eventRows, $completions);
                            unset($unavailable[$index]);
                            $progress = true;
                        }
                    }
                } while ($progress && $unavailable !== []);
                foreach ($unavailable as $index => [$id]) {
                    $results[$index] = ['id' => $id, 'status' => 'rejected', 'reason' => 'milestone_unavailable'];
                }
            }

            $this->flushCompletions($progression, $completions, $eventRows);
            $this->evaluate($progression, $snapshot);
            ksort($results);

            return ['retired' => false, 'results' => array_values($results)];
        });
    }

    /** @return array<string, string> the event's result */
    private function completeFromEvent(Progression $progression, Snapshot $snapshot, string $id, string $key, Carbon $at, ?string $actor, array &$done, array &$eventRows, array &$completions): array
    {
        // A new id for a done milestone is recorded as a no-op, so replaying that id stays a duplicate.
        $noop = isset($done[$key]);
        $eventRows[] = $this->eventRow($progression->id, self::EVENT_MILESTONE, $id, $key, null, $noop ? ['noop' => true] : null, $actor, $at, $snapshot->version);
        if ($noop) {
            return ['id' => $id, 'status' => 'duplicate'];
        }
        $done[$key] = true;
        $completions[] = ['progression_id' => $progression->id, 'milestone_key' => $key, 'completed_at' => $at, 'completed_by' => $actor];

        return ['id' => $id, 'status' => 'applied'];
    }

    /** Rule 3 of spec 017: the milestone's stage is open and all its prerequisites are done. */
    public static function isAvailable(array $milestone, array $done, int $open): bool
    {
        return $milestone['stage'] <= $open && array_all($milestone['requires'], fn (string $required) => isset($done[$required]));
    }

    /**
     * @param  list<array<string, mixed>>  $completions  emptied once written
     * @param  list<array<string, mixed>>|null  $eventRows  emptied once written
     */
    private function flushCompletions(Progression $progression, array &$completions, ?array &$eventRows = null): void
    {
        if ($eventRows !== null && $eventRows !== []) {
            DB::table('progression_events')->insert($eventRows);
            $eventRows = [];
        }
        if ($completions === []) {
            return;
        }
        DB::table('progression_milestones')->insertOrIgnore($completions);
        $this->mirrorLegacyList($progression, array_column($completions, 'milestone_key'));
        $completions = [];
    }

    public function removeLegacyCompletion(Progression $progression, int $milestoneId): void
    {
        $key = Milestone::whereKey($milestoneId)->value('key');
        if ($key !== null) {
            DB::table('progression_milestones')->where('progression_id', $progression->id)->where('milestone_key', $key)->delete();
        }
    }

    /**
     * After a publish: a lower threshold or a removed milestone may open stages. Each progression is
     * handled on its own; one that fails is logged and catches up on its next state read or event.
     */
    public function reevaluateAll(): int
    {
        $snapshot = $this->snapshots->current();
        if ($snapshot === null) {
            return 0;
        }
        $failed = 0;
        Progression::query()->where(fn ($q) => $q->whereNull('evaluated_version')->orWhere('evaluated_version', '!=', $snapshot->version))
            ->select('id')->orderBy('id')->chunkById(200, function ($progressions) use ($snapshot, &$failed) {
                foreach ($progressions as $progression) {
                    try {
                        $this->evaluateLocked($progression->id, $snapshot);
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::error('Progression re-evaluation failed; it will catch up lazily.', ['progression_id' => $progression->id, 'snapshot' => $snapshot->version, 'error' => $e->getMessage()]);
                    }
                }
            });

        return $failed;
    }

    public function openStage(Progression $progression, Snapshot $snapshot): int
    {
        if ($progression->open_stage !== null) {
            return (int) $progression->open_stage;
        }
        $legacy = $progression->max_stage_id ? Stage::whereKey($progression->max_stage_id)->value('number') : null;

        return $legacy !== null ? (int) $legacy : $snapshot->firstStage();
    }

    private function evaluateLocked(int $progressionId, Snapshot $snapshot): ?Progression
    {
        return DB::transaction(function () use ($progressionId, $snapshot) {
            $locked = Progression::whereKey($progressionId)->lockForUpdate()->first();
            if ($locked !== null) {
                $this->evaluate($locked, $snapshot);
            }

            return $locked;
        });
    }

    private function complete(int $progressionId, string $key, Carbon $at, ?string $actor): bool
    {
        return DB::table('progression_milestones')->insertOrIgnore([
            'progression_id' => $progressionId,
            'milestone_key' => $key,
            'completed_at' => $at,
            'completed_by' => $actor,
        ]) === 1;
    }

    private function eventRow(int $progressionId, string $type, ?string $eventId, ?string $key, ?int $stage, ?array $payload, ?string $actor, Carbon $at, ?int $version): array
    {
        return [
            'progression_id' => $progressionId,
            'type' => $type,
            'event_id' => $eventId,
            'milestone_key' => $key,
            'stage_number' => $stage,
            'payload' => $payload === null ? null : json_encode($payload),
            'actor' => $actor,
            'snapshot_version' => $version,
            'occurred_at' => $at,
            'created_at' => Carbon::now(),
        ];
    }

    /** Clients still on the old routes read `completed_milestones` (row ids) until they move. */
    private function mirrorLegacyList(Progression $progression, array $keys): void
    {
        $completed = $progression->completed_milestones ?? [];
        $added = array_diff(Milestone::whereIn('key', $keys)->pluck('id')->all(), $completed);
        if ($added !== []) {
            $progression->completed_milestones = array_values([...$completed, ...$added]);
            $progression->save();
        }
    }

    /** @return int the open stage after evaluation */
    private function evaluate(Progression $progression, Snapshot $snapshot): int
    {
        $open = $this->openStage($progression, $snapshot);
        $initial = $progression->open_stage;
        $done = array_flip(DB::table('progression_milestones')->where('progression_id', $progression->id)->pluck('milestone_key')->all());
        $delivered = $this->deliveries($progression->id);

        $opened = [];
        while (($next = $snapshot->nextStage($open)) !== null && $this->passed($snapshot, $open, $done, $delivered[$open] ?? [])) {
            $open = $next;
            $opened[] = $this->eventRow($progression->id, self::EVENT_STAGE, null, null, $open, null, null, Carbon::now(), $snapshot->version);
        }
        if ($opened !== []) {
            DB::table('progression_events')->insert($opened);
        }

        if ($initial === null || (int) $initial !== $open || !self::evaluatedWith($progression, $snapshot)) {
            $progression->open_stage = $open;
            $progression->evaluated_version = $snapshot->version;
            $stageId = Stage::where('number', $open)->value('id');
            if ($stageId !== null) {
                $progression->max_stage_id = $stageId;
            }
            $progression->save();
        }

        return $open;
    }

    private static function evaluatedWith(Progression $progression, Snapshot $snapshot): bool
    {
        return $progression->evaluated_version !== null && (int) $progression->evaluated_version === $snapshot->version;
    }

    private function passed(Snapshot $snapshot, int $number, array $done, array $delivered): bool
    {
        $keys = $snapshot->milestonesByStage[$number] ?? [];
        $ratio = $keys === [] ? 1.0 : count(array_filter($keys, fn (string $key) => isset($done[$key]))) / count($keys);

        return $ratio + 1e-9 >= (float) $snapshot->stages[$number]['threshold']
            && $this->moduleDone($snapshot->stages[$number]['module'], $delivered);
    }

    private function moduleDone(array $module, array $delivered): bool
    {
        return array_all($module, fn (array $item) => ($delivered[$item['item']] ?? 0) >= $item['count']);
    }

    /** @return array<int, array<string, int>> stage number => item => delivered count */
    private function deliveries(int $progressionId): array
    {
        $result = [];
        foreach (DB::table('progression_deliveries')->where('progression_id', $progressionId)->get() as $row) {
            $result[(int) $row->stage_number][$row->item] = (int) $row->count;
        }

        return $result;
    }
}
