<?php

namespace App\Progression;

use App\Models\Progression;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Spec 019 player statistics for one progression: progress, time and playtime per stage, team share, rank. */
class PlayerStatsService
{
    public function __construct(private readonly ProgressionStateService $states)
    {
    }

    /** @return array<string, mixed>|null null while no snapshot is published */
    public function stats(Progression $progression): ?array
    {
        $state = $this->states->state($progression);
        if ($state === null) {
            return null;
        }

        $opened = DB::table('progression_events')->where('progression_id', $progression->id)
            ->where('type', ProgressionStateService::EVENT_STAGE)
            ->select('stage_number', DB::raw('min(occurred_at) as opened_at'))->groupBy('stage_number')
            ->pluck('opened_at', 'stage_number')->map(fn ($at) => Carbon::parse($at));
        $members = User::query()->where('active_progression_id', $progression->id)->get(['id', 'name', 'uuid']);
        $now = Carbon::now();

        $open = array_values(array_filter($state['stages'], fn (array $stage) => $stage['open']));
        $stages = [];
        foreach ($open as $index => $stage) {
            $from = $opened[$stage['number']] ?? ($index === 0 ? Carbon::parse($progression->created_at) : null);
            $next = $open[$index + 1] ?? null;
            $until = $next === null ? null : ($opened[$next['number']] ?? null);
            $stages[] = [
                'number' => $stage['number'],
                'done' => $stage['done'],
                'total' => $stage['total'],
                'opened_at' => $from?->toIso8601ZuluString(),
                'seconds' => $from === null ? null : (int) $from->diffInSeconds($until ?? $now, true),
                'playtime_seconds' => $from === null ? null : $this->playtime($members->pluck('id')->all(), $from, $until),
            ];
        }

        $done = count($state['completed']);
        $total = array_sum(array_column($state['stages'], 'total'));

        return [
            'progression_id' => $progression->id,
            'snapshot_version' => $state['snapshot_version'],
            'open_stage' => $state['open_stage'],
            'done' => $done,
            'total' => $total,
            'ratio' => $total === 0 ? 0.0 : round($done / $total, 4),
            'rank' => $this->rank($done),
            'ranked' => (int) DB::table('progression_milestones')->distinct()->count('progression_id'),
            'stages' => $stages,
            'members' => $this->members($progression, $members),
        ];
    }

    /** Daily rows count from the day the stage opened up to the day before the next one opened. */
    private function playtime(array $userIds, Carbon $from, ?Carbon $until): int
    {
        if ($userIds === []) {
            return 0;
        }
        $query = DB::table('player_playtime')->whereIn('user_id', $userIds)->where('day', '>=', $from->toDateString());
        if ($until !== null) {
            $query->where('day', '<', $until->toDateString());
        }

        return (int) $query->sum('seconds');
    }

    /** 1 plus the progressions that completed more milestones. */
    private function rank(int $done): int
    {
        return 1 + DB::table('progression_milestones')->select('progression_id')->groupBy('progression_id')
            ->havingRaw('count(*) > ?', [$done])->get()->count();
    }

    /** @return list<array{name: ?string, completed: int}> milestones done per member, most first */
    private function members(Progression $progression, $members): array
    {
        $byUuid = $members->keyBy('uuid');
        $counts = DB::table('progression_milestones')->where('progression_id', $progression->id)
            ->select('completed_by', DB::raw('count(*) as completed'))->groupBy('completed_by')
            ->pluck('completed', 'completed_by');

        $rows = $members->map(fn (User $user) => ['name' => $user->name, 'completed' => (int) ($counts[$user->uuid] ?? 0)])->all();
        // Former members and completions without an actor (legacy routes) share one row.
        $others = $counts->filter(fn ($count, $uuid) => $uuid === '' || !$byUuid->has($uuid))->sum();
        if ($others > 0) {
            $rows[] = ['name' => null, 'completed' => (int) $others];
        }
        usort($rows, fn (array $a, array $b) => $b['completed'] <=> $a['completed']);

        return $rows;
    }
}
