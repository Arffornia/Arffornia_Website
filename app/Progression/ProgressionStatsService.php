<?php

namespace App\Progression;

use App\Models\Progression;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Admin statistics over every progression, computed from the state tables and cached for a few minutes. */
class ProgressionStatsService
{
    private const TTL_SECONDS = 300;

    public function __construct(
        private readonly ProgressionStateService $states,
        private readonly SnapshotStore $snapshots,
    ) {
    }

    /** @return array<string, mixed>|null null while no snapshot is published */
    public function stats(): ?array
    {
        $snapshot = $this->states->snapshot();
        if ($snapshot === null) {
            return null;
        }

        return Cache::remember('progression-stats:v' . $snapshot->version, self::TTL_SECONDS, fn () => $this->compute($snapshot));
    }

    private function compute(Snapshot $snapshot): array
    {
        $since = $this->snapshots->firstPublishedAt();
        $since = $since === null ? null : Carbon::parse($since);
        $perStage = [];
        $total = 0;
        Progression::query()->select(['id', 'open_stage', 'max_stage_id'])->orderBy('id')->chunkById(500, function ($progressions) use ($snapshot, &$perStage, &$total) {
            foreach ($progressions as $progression) {
                $stage = $this->states->openStage($progression, $snapshot);
                $perStage[$stage] = ($perStage[$stage] ?? 0) + 1;
                $total++;
            }
        });

        $active = (int) DB::table('progression_milestones')->distinct()->count('progression_id');
        $completions = DB::table('progression_milestones')->select('milestone_key', DB::raw('count(*) as completed'))
            ->groupBy('milestone_key')->pluck('completed', 'milestone_key');

        $milestones = [];
        foreach ($snapshot->milestones as $key => $milestone) {
            $count = (int) ($completions[$key] ?? 0);
            $milestones[] = [
                'key' => $key,
                'stage' => $milestone['stage'],
                'completed' => $count,
                'rate' => $active === 0 ? 0.0 : round($count / $active, 4),
            ];
        }

        return [
            'snapshot_version' => $snapshot->version,
            'generated_at' => Carbon::now()->toIso8601ZuluString(),
            'progressions' => $total,
            'active_progressions' => $active,
            'stages' => array_map(fn (int $number) => [
                'number' => $number,
                'progressions' => $perStage[$number] ?? 0,
                ...$this->stageDuration($number, $since),
            ], array_keys($snapshot->stages)),
            'milestones' => $milestones,
        ];
    }

    /**
     * Median time to open a stage: from the opening of the stage before it, or, with no earlier opening,
     * from the creation of the progression when it began after the first publish. Older progressions
     * would count their whole pre-log history, so they give no sample there.
     *
     * @return array{samples: int, median_seconds: ?int}
     */
    private function stageDuration(int $number, ?Carbon $since): array
    {
        $opened = DB::table('progression_events as e')
            ->join('progressions as p', 'p.id', '=', 'e.progression_id')
            ->leftJoin('progression_events as prev', function ($join) use ($number) {
                $join->on('prev.progression_id', '=', 'e.progression_id')
                    ->where('prev.type', ProgressionStateService::EVENT_STAGE)
                    ->where('prev.stage_number', '<', $number);
            })
            ->where('e.type', ProgressionStateService::EVENT_STAGE)
            ->where('e.stage_number', $number)
            ->groupBy('e.id', 'e.occurred_at', 'p.created_at')
            ->select('e.occurred_at', 'p.created_at', DB::raw('max(prev.occurred_at) as previous_at'))
            ->get();

        $durations = $opened->filter(fn ($row) => $row->previous_at !== null || ($since !== null && Carbon::parse($row->created_at)->gte($since)))
            ->map(function ($row) {
                $from = Carbon::parse($row->previous_at ?? $row->created_at);

                return max(0, (int) $from->diffInSeconds(Carbon::parse($row->occurred_at), true));
            })->sort()->values();

        if ($durations->isEmpty()) {
            return ['samples' => 0, 'median_seconds' => null];
        }
        $middle = intdiv($durations->count(), 2);
        $median = $durations->count() % 2 === 1 ? $durations[$middle] : intdiv($durations[$middle - 1] + $durations[$middle], 2);

        return ['samples' => $durations->count(), 'median_seconds' => $median];
    }
}
