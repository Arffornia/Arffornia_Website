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
    private const ACTIVITY_DAYS = 30;

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
        $doneBy = DB::table('progression_milestones')->get(['progression_id', 'milestone_key'])
            ->groupBy('progression_id')->map(fn ($rows) => array_flip($rows->pluck('milestone_key')->all()));
        $waiting = [];
        Progression::query()->select(['id', 'open_stage', 'max_stage_id'])->orderBy('id')->chunkById(500, function ($progressions) use ($snapshot, $doneBy, &$perStage, &$total, &$waiting) {
            foreach ($progressions as $progression) {
                $stage = $this->states->openStage($progression, $snapshot);
                $perStage[$stage] = ($perStage[$stage] ?? 0) + 1;
                $total++;
                $done = $doneBy[$progression->id] ?? null;
                if ($done === null) {
                    continue;
                }
                foreach ($snapshot->milestones as $key => $milestone) {
                    if (!isset($done[$key]) && ProgressionStateService::isAvailable($milestone, $done, $stage)) {
                        $waiting[$key] = ($waiting[$key] ?? 0) + 1;
                    }
                }
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
                'waiting' => $waiting[$key] ?? 0,
            ];
        }

        // Stalls: available to many active progressions, done by few of them.
        $stalls = collect($milestones)->filter(fn (array $m) => $m['waiting'] > 0)
            ->sortBy([['waiting', 'desc'], ['completed', 'asc'], ['key', 'asc']])->take(10)
            ->map(fn (array $m) => [...$m, 'title' => $snapshot->milestones[$m['key']]['title']['en'] ?? $m['key']])
            ->values()->all();

        $reached = 0;
        $funnel = [];
        foreach (array_reverse(array_keys($snapshot->stages)) as $number) {
            $reached += $perStage[$number] ?? 0;
            $funnel[$number] = $reached;
        }

        $activitySince = Carbon::now()->subDays(self::ACTIVITY_DAYS - 1)->startOfDay();
        $activity = DB::table('progression_milestones')->where('completed_at', '>=', $activitySince)
            ->select(DB::raw('date(completed_at) as day'), DB::raw('count(*) as completed'))
            ->groupBy(DB::raw('date(completed_at)'))->orderBy('day')->get()
            ->map(fn ($row) => ['day' => (string) $row->day, 'completed' => (int) $row->completed])->all();

        return [
            'snapshot_version' => $snapshot->version,
            'generated_at' => Carbon::now()->toIso8601ZuluString(),
            'progressions' => $total,
            'active_progressions' => $active,
            'stages' => array_map(fn (int $number) => [
                'number' => $number,
                'name' => $snapshot->stages[$number]['name']['en'] ?? (string) $number,
                'progressions' => $perStage[$number] ?? 0,
                'reached' => $funnel[$number],
                'reached_rate' => $total === 0 ? 0.0 : round($funnel[$number] / $total, 4),
                ...$this->stageDuration($number, $since),
            ], array_keys($snapshot->stages)),
            'milestones' => $milestones,
            'stalls' => $stalls,
            'activity' => $activity,
            'playtime' => [
                'players' => (int) DB::table('player_playtime')->distinct()->count('user_id'),
                'seconds' => (int) DB::table('player_playtime')->sum('seconds'),
            ],
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
