@extends('admin.layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/progression-stats.css') }}">
@endsection

@section('content')
    <main class="standart-page progression-stats">
        <header class="ps-page-head">
            <p class="ps-eyebrow">{{ __('progression_stats.admin_eyebrow') }}</p>
            <h1>{{ __('progression_stats.admin_title') }}</h1>
            @if ($stats !== null)
                <p class="ps-meta">{{ __('progression_stats.generated', [
                    'version' => $stats['snapshot_version'],
                    'time' => \Illuminate\Support\Carbon::parse($stats['generated_at'])->setTimezone(config('app.timezone'))->locale(app()->getLocale())->isoFormat('lll'),
                ]) }}</p>
            @endif
        </header>

        @if ($stats === null)
            <div class="ps-empty">
                <p>{{ __('progression_stats.no_model') }}</p>
            </div>
        @else
            @php
                $locale = app()->getLocale();
                $formatNumber = fn (float $value, int $decimals = 0) => number_format($value, $decimals, __('common.number.decimal'), __('common.number.thousands'));
                $generated = \Illuminate\Support\Carbon::parse($stats['generated_at']);
                $activityByDay = collect($stats['activity'])->pluck('completed', 'day');
                $activityDays = [];
                for ($i = 29; $i >= 0; $i--) {
                    $day = $generated->copy()->subDays($i)->toDateString();
                    $activityDays[] = ['day' => $day, 'completed' => (int) ($activityByDay[$day] ?? 0)];
                }
                $activityTotal = array_sum(array_column($activityDays, 'completed'));
                $activityPeak = max(array_column($activityDays, 'completed'));
                $activityMax = max(2, (int) ceil($activityPeak / 2) * 2);
                $hereMax = max(1, max(array_column($stats['stages'], 'progressions') ?: [0]));
                $waitingMax = max(1, max(array_column($stats['stalls'], 'waiting') ?: [0]));
                $activeRatio = $stats['progressions'] === 0 ? 0 : $stats['active_progressions'] / $stats['progressions'];
            @endphp

            <dl class="ps-kpis">
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.progressions') }}</dt>
                    <dd class="ps-kpi__value" data-stat="progressions"><x-number :value="$stats['progressions']" /></dd>
                    <dd class="ps-kpi__hint">{{ __('progression_stats.progressions_hint') }}</dd>
                </div>
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.active_progressions') }}</dt>
                    <dd class="ps-kpi__value" data-stat="active"><x-number :value="$stats['active_progressions']" /></dd>
                    <dd class="ps-kpi__hint">{{ __('progression_stats.active_hint', ['percent' => __('common.number.percent', ['value' => $formatNumber($activeRatio * 100)])]) }}</dd>
                </div>
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.milestones_30d') }}</dt>
                    <dd class="ps-kpi__value" data-stat="activity-total"><x-number :value="$activityTotal" /></dd>
                    <dd class="ps-kpi__hint">{{ __('progression_stats.per_day', ['count' => $formatNumber($activityTotal / 30, 1)]) }}</dd>
                </div>
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.playtime_players') }}</dt>
                    <dd class="ps-kpi__value" data-stat="playtime-players"><x-number :value="$stats['playtime']['players']" /></dd>
                    <dd class="ps-kpi__hint">{{ __('progression_stats.playtime_players_hint') }}</dd>
                </div>
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.playtime_total') }}</dt>
                    <dd class="ps-kpi__value" data-stat="playtime-total"><x-duration :seconds="$stats['playtime']['seconds']" /></dd>
                    <dd class="ps-kpi__hint">{{ __('progression_stats.playtime_total_hint') }}</dd>
                </div>
            </dl>

            <div class="ps-grid">
                <section class="ps-card ps-card--wide">
                    <h2>{{ __('progression_stats.stages_title') }}</h2>
                    <p class="ps-meta">{{ __('progression_stats.stages_hint') }}</p>
                    <ol class="ps-stages">
                        @foreach ($stats['stages'] as $stage)
                            <li class="ps-stage" data-stage="{{ $stage['number'] }}">
                                <span class="ps-stage__badge" aria-hidden="true">{{ $stage['number'] }}</span>
                                <div class="ps-stage__body">
                                    <div class="ps-stage__head">
                                        <h3>{{ __('progression_stats.stage_badge', ['number' => $stage['number']]) }}@if ($stage['name'] !== __('progression_stats.stage_badge', ['number' => $stage['number']]) && $stage['name'] !== (string) $stage['number']) <span>{{ $stage['name'] }}</span>@endif</h3>
                                    </div>
                                    <div class="ps-dual">
                                        <span class="ps-dual__label">{{ __('progression_stats.at_stage') }}</span>
                                        <span class="ps-bar ps-bar--second" aria-hidden="true"><span style="--ps-value: {{ round($stage['progressions'] / $hereMax, 4) }}"></span></span>
                                        <span class="ps-dual__value" data-col="at"><x-number :value="$stage['progressions']" /></span>

                                        <span class="ps-dual__label">{{ __('progression_stats.reached') }}</span>
                                        <span class="ps-bar" aria-hidden="true"><span style="--ps-value: {{ $stage['reached_rate'] }}"></span></span>
                                        <span class="ps-dual__value"><span data-col="reached"><x-number :value="$stage['reached']" /></span> <small data-col="reached-rate"><x-percent :ratio="$stage['reached_rate']" /></small></span>
                                    </div>
                                    <dl class="ps-stage__meta">
                                        <div><dt>{{ __('progression_stats.median_time') }}</dt><dd data-col="median"><x-duration :seconds="$stage['median_seconds']" /></dd></div>
                                        <div><dt>{{ __('progression_stats.samples') }}</dt><dd data-col="samples"><x-number :value="$stage['samples']" /></dd></div>
                                    </dl>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section class="ps-card">
                    <h2>{{ __('progression_stats.stalls_title') }}</h2>
                    <p class="ps-meta">{{ __('progression_stats.stalls_hint') }}</p>
                    @if ($stats['stalls'] === [])
                        <div class="ps-empty">
                            <p class="ps-empty__title">{{ __('progression_stats.stalls_empty_title') }}</p>
                            <p>{{ __('progression_stats.stalls_empty') }}</p>
                        </div>
                    @else
                        <ol class="ps-stalls">
                            @foreach ($stats['stalls'] as $milestone)
                                <li data-milestone="{{ $milestone['key'] }}">
                                    <span class="ps-stalls__rank" aria-hidden="true">{{ $loop->iteration }}</span>
                                    <div class="ps-stalls__body">
                                        <div class="ps-stalls__head">
                                            <span class="ps-stalls__title">{{ $milestone['title'] }}</span>
                                            <span class="ps-chip">{{ __('progression_stats.stage_badge', ['number' => $milestone['stage']]) }}</span>
                                        </div>
                                        <span class="ps-bar ps-bar--warning" aria-hidden="true"><span style="--ps-value: {{ round($milestone['waiting'] / $waitingMax, 4) }}"></span></span>
                                        <dl class="ps-stalls__meta">
                                            <div><dt>{{ __('progression_stats.waiting') }}</dt><dd data-col="waiting"><x-number :value="$milestone['waiting']" /></dd></div>
                                            <div><dt>{{ __('progression_stats.completed') }}</dt><dd data-col="completed"><x-number :value="$milestone['completed']" /></dd></div>
                                            <div><dt>{{ __('progression_stats.rate') }}</dt><dd data-col="rate"><x-percent :ratio="$milestone['rate']" /></dd></div>
                                        </dl>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                <section class="ps-card">
                    <h2>{{ __('progression_stats.activity_title') }}</h2>
                    @if ($activityTotal === 0)
                        <div class="ps-empty">
                            <p class="ps-empty__title">{{ __('progression_stats.activity_empty_title') }}</p>
                            <p>{{ __('progression_stats.none') }}</p>
                        </div>
                    @else
                        <p class="ps-meta">{{ __('progression_stats.activity_peak', ['count' => $activityPeak]) }}</p>
                        <div class="ps-chart">
                        <ul class="ps-chart__axis" aria-hidden="true">
                            <li><x-number :value="$activityMax" /></li>
                            <li><x-number :value="intdiv($activityMax, 2)" /></li>
                            <li>0</li>
                        </ul>
                        <ol class="ps-columns ps-columns--dense" aria-label="{{ __('progression_stats.activity_title') }}">
                            @foreach ($activityDays as $i => $day)
                                @php($date = \Illuminate\Support\Carbon::parse($day['day'])->locale($locale))
                                <li data-day="{{ $day['day'] }}" @class(['is-labeled' => $i % 7 === 0 || $loop->last, 'is-zero' => $day['completed'] === 0])>
                                    <span class="ps-columns__bar" style="--ps-value: {{ round($day['completed'] / $activityMax, 4) }}"></span>
                                    <span class="ps-columns__tip">{{ $date->isoFormat('D MMM') }}: <span data-col="completed">{{ $day['completed'] }}</span></span>
                                    <span class="ps-columns__label" aria-hidden="true">{{ $date->isoFormat('D/M') }}</span>
                                </li>
                            @endforeach
                        </ol>
                        </div>
                    @endif
                </section>
            </div>
        @endif
    </main>
@endsection
