<?php

namespace App\Progression;

use App\Models\Progression;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Spec 019 player statistics for one progression: progress, time and playtime per stage, team share, rank. */
class PlayerStatsService
{
    private const RECENT_COMPLETIONS = 6;
    private const PLAYTIME_DAYS = 14;

    public function __construct(private readonly ProgressionStateService $states)
    {
    }

    /** @return array<string, mixed>|null null while no snapshot is published */
    public function stats(Progression $progression): ?array
    {
        $state = $this->states->state($progression);
        $snapshot = $this->states->snapshot();
        if ($state === null || $snapshot === null) {
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
                'name' => self::localized($snapshot->stages[$stage['number']]['name'] ?? null) ?? (string) $stage['number'],
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
            'recent' => $this->recent($state['completed'], $snapshot),
            'playtime_days' => $this->playtimeDays($members->pluck('id')->all(), $now),
        ];
    }

    /**
     * @param list<array{key: string, completed_at: string}> $completed oldest first
     * @return list<array{key: string, title: string, stage: int, completed_at: string, item: ?string}>
     */
    private function recent(array $completed, Snapshot $snapshot): array
    {
        return array_map(
            fn (array $row) => self::milestoneRow($snapshot, $row['key'], $row['completed_at']),
            array_reverse(array_slice($completed, -self::RECENT_COMPLETIONS)),
        );
    }

    /** @return array{key: string, title: string, stage: int, completed_at: string, item: ?string} */
    private static function milestoneRow(Snapshot $snapshot, string $key, string $completedAt): array
    {
        $milestone = $snapshot->milestones[$key];

        return [
            'key' => $key,
            'title' => self::localized($milestone['title'] ?? null) ?? $key,
            'stage' => (int) $milestone['stage'],
            'completed_at' => $completedAt,
            'item' => self::iconItem($milestone),
        ];
    }

    /**
     * Spec 019 team view, who did what: every milestone of the progression under the member who completed it.
     *
     * @return array{team: ?string, members: list<array{name: ?string, milestones: list<array<string, mixed>>}>}|null
     */
    public function team(Progression $progression): ?array
    {
        $snapshot = $this->states->snapshot();
        if ($snapshot === null || $this->states->state($progression) === null) {
            return null;
        }

        $byUuid = User::query()->where('active_progression_id', $progression->id)->orderBy('name')->get(['name', 'uuid'])
            ->mapWithKeys(fn (User $user) => [$user->uuid => ['name' => $user->name, 'milestones' => []]])->all();
        // Former members and completions without an actor (legacy routes) share one group, as in members().
        $others = [];
        $rows = DB::table('progression_milestones')->where('progression_id', $progression->id)
            ->orderByDesc('completed_at')->orderByDesc('id')->get(['milestone_key', 'completed_at', 'completed_by']);
        foreach ($rows as $row) {
            if (!isset($snapshot->milestones[$row->milestone_key])) {
                continue;
            }
            $milestone = self::milestoneRow($snapshot, $row->milestone_key, Carbon::parse($row->completed_at)->toIso8601ZuluString());
            if ($row->completed_by !== null && isset($byUuid[$row->completed_by])) {
                $byUuid[$row->completed_by]['milestones'][] = $milestone;
            } else {
                $others[] = $milestone;
            }
        }

        $result = array_values($byUuid);
        usort($result, fn (array $a, array $b) => count($b['milestones']) <=> count($a['milestones']));
        if ($others !== []) {
            $result[] = ['name' => null, 'milestones' => $others];
        }

        return [
            'team' => Team::query()->where('progression_id', $progression->id)->value('name'),
            'members' => $result,
        ];
    }

    /** Same pick as the graph viewer: the first unlocked item, else the first item to validate. Tags have no icon. */
    private static function iconItem(array $milestone): ?string
    {
        $validation = $milestone['validation']['items'][0] ?? null;
        $item = $milestone['unlocks'][0]['item'] ?? (is_array($validation) ? ($validation['item'] ?? null) : $validation);

        return is_string($item) && !str_starts_with($item, '#') && preg_match(Keys::ITEM_PATTERN, $item) ? $item : null;
    }

    /** @return list<array{day: string, seconds: int}> one entry per day, oldest first, today included */
    private function playtimeDays(array $userIds, Carbon $now): array
    {
        $from = $now->copy()->subDays(self::PLAYTIME_DAYS - 1)->startOfDay();
        $seconds = $userIds === [] ? collect() : DB::table('player_playtime')->whereIn('user_id', $userIds)
            ->where('day', '>=', $from->toDateString())->where('day', '<=', $now->toDateString())
            ->select('day', DB::raw('sum(seconds) as seconds'))->groupBy('day')->pluck('seconds', 'day')
            ->mapWithKeys(fn ($total, $day) => [substr((string) $day, 0, 10) => (int) $total]);

        $days = [];
        for ($day = $from->copy(); $day->lte($now); $day->addDay()) {
            $days[] = ['day' => $day->toDateString(), 'seconds' => $seconds[$day->toDateString()] ?? 0];
        }

        return $days;
    }

    /** Snapshot texts are per locale with English required (ADR-0009). */
    private static function localized(mixed $text): ?string
    {
        if (!is_array($text)) {
            return is_string($text) ? $text : null;
        }

        return $text[app()->getLocale()] ?? $text['en'] ?? null;
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
