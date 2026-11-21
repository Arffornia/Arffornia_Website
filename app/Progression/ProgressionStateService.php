<?php

namespace App\Progression;

use App\Models\Milestone;
use App\Models\Progression;
use App\Models\Stage;
use App\Repositories\TeamRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        $completed = DB::table('progression_milestones')->where('progression_id', $progression->id)
            ->orderBy('completed_at')->orderBy('id')->get(['milestone_key', 'completed_at']);
        $done = array_flip($completed->pluck('milestone_key')->all());
        $delivered = $this->deliveries($progression->id);
        $open = $this->openStage($progression, $snapshot);

        $stages = [];
        foreach ($snapshot->stages as $number => $stage) {
            $keys = $snapshot->milestonesByStage[$number] ?? [];
            $doneCount = count(array_filter($keys, fn (string $key) => isset($done[$key])));
            $module = array_map(fn (array $item) => [
                'item' => $item['item'],
                'count' => $item['count'],
                'delivered' => min($item['count'], $delivered[$number][$item['item']] ?? 0),
            ], $stage['module']);
            $stages[] = [
                'number' => $number,
                'open' => $number <= $open,
                'done' => $doneCount,
                'total' => count($keys),
                'ratio' => $keys === [] ? 1.0 : round($doneCount / count($keys), 4),
                'threshold' => (float) $stage['threshold'],
                'module' => $module,
                'module_done' => $this->moduleDone($stage['module'], $delivered[$number] ?? []),
            ];
        }

        $next = [];
        foreach ($snapshot->milestones as $key => $milestone) {
            if (!isset($done[$key]) && $milestone['stage'] <= $open
                && array_all($milestone['requires'], fn (string $required) => isset($done[$required]))) {
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
     * completed once whatever the event id, so replays and reordering change nothing.
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

            $results = [];
            foreach ($events as $event) {
                $results[] = ['id' => $event['id'], ...$this->applyOne($progression, $event, $snapshot)];
            }
            $this->evaluate($progression, $snapshot);

            return ['retired' => false, 'results' => $results];
        });
    }

    /** Old `/progression/add` path: same storage, so the new state and the old list never disagree. */
    public function recordLegacyCompletion(Progression $progression, int $milestoneId, ?string $actor): void
    {
        $key = Milestone::whereKey($milestoneId)->value('key');
        if ($key !== null && $this->complete($progression->id, $key, Carbon::now(), $actor)) {
            $this->event($progression->id, self::EVENT_MILESTONE, null, $key, null, null, $actor, Carbon::now());
        }
        $snapshot = $this->snapshots->current();
        if ($snapshot !== null) {
            $this->evaluate($progression, $snapshot);
        }
    }

    public function removeLegacyCompletion(Progression $progression, int $milestoneId): void
    {
        $key = Milestone::whereKey($milestoneId)->value('key');
        if ($key !== null) {
            DB::table('progression_milestones')->where('progression_id', $progression->id)->where('milestone_key', $key)->delete();
        }
    }

    /** After a publish: a lower threshold or a removed milestone may open stages. */
    public function reevaluateAll(): void
    {
        $snapshot = $this->snapshots->current();
        if ($snapshot === null) {
            return;
        }
        Progression::query()->orderBy('id')->chunkById(200, function ($progressions) use ($snapshot) {
            foreach ($progressions as $progression) {
                DB::transaction(function () use ($progression, $snapshot) {
                    $locked = Progression::whereKey($progression->id)->lockForUpdate()->first();
                    if ($locked !== null) {
                        $this->evaluate($locked, $snapshot);
                    }
                });
            }
        });
    }

    public function openStage(Progression $progression, Snapshot $snapshot): int
    {
        if ($progression->open_stage !== null) {
            return (int) $progression->open_stage;
        }
        $legacy = $progression->max_stage_id ? Stage::whereKey($progression->max_stage_id)->value('number') : null;

        return $legacy !== null ? (int) $legacy : $snapshot->firstStage();
    }

    /** @return array<string, string> status and, when rejected, a reason */
    private function applyOne(Progression $progression, array $event, Snapshot $snapshot): array
    {
        if (DB::table('progression_events')->where('progression_id', $progression->id)->where('event_id', $event['id'])->exists()) {
            return ['status' => 'duplicate'];
        }
        $at = isset($event['occurred_at']) ? Carbon::createFromTimestampMs($event['occurred_at']) : Carbon::now();
        $actor = $event['actor'] ?? null;

        if ($event['type'] === self::EVENT_MILESTONE) {
            $key = $event['milestone'];
            if (!isset($snapshot->milestones[$key])) {
                return ['status' => 'rejected', 'reason' => 'unknown_milestone'];
            }
            if (!$this->complete($progression->id, $key, $at, $actor)) {
                return ['status' => 'duplicate'];
            }
            $this->event($progression->id, self::EVENT_MILESTONE, $event['id'], $key, null, null, $actor, $at);
            $this->mirrorLegacyList($progression, $key);

            return ['status' => 'applied'];
        }

        $stage = $snapshot->stages[$event['stage']] ?? null;
        $wanted = $stage === null ? [] : array_column($stage['module'], 'item');
        if (!in_array($event['item'], $wanted, true)) {
            return ['status' => 'rejected', 'reason' => 'not_in_module'];
        }
        $row = ['progression_id' => $progression->id, 'stage_number' => $event['stage'], 'item' => $event['item']];
        DB::table('progression_deliveries')->insertOrIgnore([...$row, 'count' => 0]);
        DB::table('progression_deliveries')->where($row)->increment('count', $event['count']);
        $this->event($progression->id, self::EVENT_MODULE, $event['id'], null, $event['stage'], ['item' => $event['item'], 'count' => $event['count']], $actor, $at);

        return ['status' => 'applied'];
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

    private function event(int $progressionId, string $type, ?string $eventId, ?string $key, ?int $stage, ?array $payload, ?string $actor, Carbon $at): void
    {
        DB::table('progression_events')->insert([
            'progression_id' => $progressionId,
            'type' => $type,
            'event_id' => $eventId,
            'milestone_key' => $key,
            'stage_number' => $stage,
            'payload' => $payload === null ? null : json_encode($payload),
            'actor' => $actor,
            'snapshot_version' => $this->snapshots->current()?->version,
            'occurred_at' => $at,
            'created_at' => Carbon::now(),
        ]);
    }

    /** Clients still on the old routes read `completed_milestones` (row ids) until they move. */
    private function mirrorLegacyList(Progression $progression, string $key): void
    {
        $id = Milestone::where('key', $key)->value('id');
        $completed = $progression->completed_milestones ?? [];
        if ($id !== null && !in_array($id, $completed)) {
            $completed[] = $id;
            $progression->completed_milestones = $completed;
            $progression->save();
        }
    }

    private function evaluate(Progression $progression, Snapshot $snapshot): void
    {
        $open = $this->openStage($progression, $snapshot);
        $initial = $progression->open_stage;
        $done = array_flip(DB::table('progression_milestones')->where('progression_id', $progression->id)->pluck('milestone_key')->all());
        $delivered = $this->deliveries($progression->id);

        while (($next = $snapshot->nextStage($open)) !== null && $this->passed($snapshot, $open, $done, $delivered[$open] ?? [])) {
            $open = $next;
            $this->event($progression->id, self::EVENT_STAGE, null, null, $open, null, null, Carbon::now());
        }

        if ($initial === null || (int) $initial !== $open) {
            $progression->open_stage = $open;
            $stageId = Stage::where('number', $open)->value('id');
            if ($stageId !== null) {
                $progression->max_stage_id = $stageId;
            }
            $progression->save();
        }
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
