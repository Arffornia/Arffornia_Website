@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/progression-stats.css') }}">
@endsection

@section('content')
    <main class="standart-page progression-stats">
        @if ($stats === null)
            <section class="ps-hero">
                <div class="ps-hero__identity">
                    <img class="ps-hero__avatar" src="https://minotar.net/helm/{{ rawurlencode($playerName) }}/128" alt="" width="96" height="96">
                    <div>
                        <p class="ps-eyebrow">{{ __('progression_stats.player_eyebrow') }}</p>
                        <h1 class="ps-hero__name">{{ $playerName }}</h1>
                    </div>
                </div>
            </section>
            <div class="ps-empty">
                <p>{{ __('progression_stats.no_model') }}</p>
            </div>
        @else
            @php
                $percent = (int) round($stats['ratio'] * 100);
                $current = collect($stats['stages'])->firstWhere('number', $stats['open_stage']);
                $playtimeTotal = collect($stats['stages'])->sum(fn ($stage) => $stage['playtime_seconds'] ?? 0);
                $adventureSeconds = collect($stats['stages'])->sum(fn ($stage) => $stage['seconds'] ?? 0);
                $maxDay = max(1, max(array_column($stats['playtime_days'], 'seconds')));
                $playtimeTracked = collect($stats['playtime_days'])->sum('seconds') > 0 || $playtimeTotal > 0;
                $teamTotal = max(1, collect($stats['members'])->sum('completed'));
                $ranked = max($stats['ranked'], $stats['rank']);
                $locale = app()->getLocale();
            @endphp

            <section class="ps-hero" aria-labelledby="ps-player-name">
                <div class="ps-hero__identity">
                    <img class="ps-hero__avatar" src="https://minotar.net/helm/{{ rawurlencode($playerName) }}/128" alt="" width="96" height="96">
                    <div>
                        <p class="ps-eyebrow">{{ __('progression_stats.player_eyebrow') }}</p>
                        <h1 class="ps-hero__name" id="ps-player-name">{{ $playerName }}</h1>
                        <p class="ps-hero__stage">
                            <span class="ps-chip ps-chip--accent">{{ __('progression_stats.stage_badge', ['number' => $stats['open_stage']]) }}</span>
                            @if ($current !== null && $current['name'] !== __('progression_stats.stage_badge', ['number' => $current['number']]))
                                <span>{{ $current['name'] }}</span>
                            @endif
                        </p>
                        <a class="ps-button" href="{{ route('progression.player', ['playerName' => $playerName]) }}">{{ __('progression_stats.back_to_tree') }}</a>
                    </div>
                </div>

                <div class="ps-ring" role="img" aria-label="{{ __('progression_stats.ring_label', ['done' => $stats['done'], 'total' => $stats['total'], 'percent' => $percent]) }}">
                    <svg viewBox="0 0 120 120" aria-hidden="true">
                        <defs>
                            <linearGradient id="ps-ring-gradient" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#ff7300" />
                                <stop offset="1" stop-color="#f2c230" />
                            </linearGradient>
                        </defs>
                        <circle class="ps-ring__track" cx="60" cy="60" r="52" />
                        <circle class="ps-ring__value" cx="60" cy="60" r="52" pathLength="100" stroke-dasharray="{{ $percent }} 100" />
                    </svg>
                    <div class="ps-ring__text" aria-hidden="true">
                        <span class="ps-ring__percent"><x-percent :ratio="$stats['ratio']" /></span>
                        <span class="ps-ring__caption">{{ __('progression_stats.ring_caption') }}</span>
                    </div>
                </div>
            </section>

            <dl class="ps-kpis">
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.overall') }}</dt>
                    <dd class="ps-kpi__value" data-stat="overall"><x-number :value="$stats['done']" /><small> / <x-number :value="$stats['total']" /></small></dd>
                    <dd class="ps-kpi__hint">{{ __('progression_stats.left_to_discover', ['count' => max(0, $stats['total'] - $stats['done'])]) }}</dd>
                </div>
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.open_stage') }}</dt>
                    <dd class="ps-kpi__value" data-stat="open-stage">{{ $stats['open_stage'] }}</dd>
                    <dd class="ps-kpi__hint">{{ $current['name'] ?? '' }}</dd>
                </div>
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.rank') }}</dt>
                    <dd class="ps-kpi__value" data-stat="rank">{{ __('progression_stats.rank_short', ['rank' => $stats['rank']]) }}</dd>
                    <dd class="ps-kpi__hint">{{ __('progression_stats.rank_among', ['ranked' => $ranked]) }}</dd>
                </div>
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.playtime') }}</dt>
                    <dd class="ps-kpi__value" data-stat="playtime">
                        @if ($playtimeTracked)
                            <x-duration :seconds="$playtimeTotal" />
                        @else
                            -
                        @endif
                    </dd>
                    <dd class="ps-kpi__hint">{{ $playtimeTracked ? __('progression_stats.playtime_hint') : __('progression_stats.playtime_untracked') }}</dd>
                </div>
                <div class="ps-kpi">
                    <dt>{{ __('progression_stats.adventure') }}</dt>
                    <dd class="ps-kpi__value" data-stat="adventure"><x-duration :seconds="$adventureSeconds" /></dd>
                    <dd class="ps-kpi__hint">{{ __('progression_stats.adventure_hint') }}</dd>
                </div>
            </dl>

            <div class="ps-grid">
                <section class="ps-card ps-card--wide">
                    <h2>{{ __('progression_stats.per_stage_title') }}</h2>
                    <ol class="ps-stages">
                        @foreach ($stats['stages'] as $stage)
                            @php
                                $stageRatio = $stage['total'] === 0 ? 1 : $stage['done'] / $stage['total'];
                                $isCurrent = $stage['number'] === $stats['open_stage'];
                            @endphp
                            <li class="ps-stage {{ $isCurrent ? 'is-current' : 'is-past' }}" data-stage="{{ $stage['number'] }}">
                                <span class="ps-stage__badge" aria-hidden="true">{{ $stage['number'] }}</span>
                                <div class="ps-stage__body">
                                    <div class="ps-stage__head">
                                        <h3>{{ __('progression_stats.stage_badge', ['number' => $stage['number']]) }}@if ($stage['name'] !== __('progression_stats.stage_badge', ['number' => $stage['number']]) && $stage['name'] !== (string) $stage['number']) <span>{{ $stage['name'] }}</span>@endif</h3>
                                        <span class="ps-chip {{ $isCurrent ? 'ps-chip--accent' : 'ps-chip--second' }}">{{ $isCurrent ? __('progression_stats.stage_current') : __('progression_stats.stage_passed') }}</span>
                                    </div>
                                    <div class="ps-bar" aria-hidden="true"><span style="--ps-value: {{ round($stageRatio, 4) }}"></span></div>
                                    <dl class="ps-stage__meta">
                                        <div><dt>{{ __('progression_stats.progress') }}</dt><dd data-col="progress">{{ $stage['done'] }} / {{ $stage['total'] }}</dd></div>
                                        <div><dt>{{ __('progression_stats.time') }}</dt><dd data-col="time"><x-duration :seconds="$stage['seconds']" /></dd></div>
                                        <div><dt>{{ __('progression_stats.playtime') }}</dt><dd data-col="playtime"><x-duration :seconds="$stage['playtime_seconds']" /></dd></div>
                                        @if ($stage['opened_at'] !== null)
                                            <div><dt>{{ __('progression_stats.opened') }}</dt><dd><time datetime="{{ $stage['opened_at'] }}">{{ \Illuminate\Support\Carbon::parse($stage['opened_at'])->locale($locale)->isoFormat('ll') }}</time></dd></div>
                                        @endif
                                    </dl>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section class="ps-card">
                    <h2>{{ __('progression_stats.playtime_title', ['days' => count($stats['playtime_days'])]) }}</h2>
                    @if ($playtimeTracked)
                        <ol class="ps-columns" aria-label="{{ __('progression_stats.playtime_title', ['days' => count($stats['playtime_days'])]) }}">
                            @foreach ($stats['playtime_days'] as $i => $day)
                                @php($date = \Illuminate\Support\Carbon::parse($day['day'])->locale($locale))
                                <li data-day="{{ $day['day'] }}" @class(['is-labeled' => $i % 7 === 0 || $loop->last, 'is-zero' => $day['seconds'] === 0])>
                                    <span class="ps-columns__bar" style="--ps-value: {{ round($day['seconds'] / $maxDay, 4) }}"></span>
                                    <span class="ps-columns__tip">{{ $date->isoFormat('D MMM') }}: <x-duration :seconds="$day['seconds']" /></span>
                                    <span class="ps-columns__label" aria-hidden="true">{{ $date->isoFormat('D') }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="ps-empty">
                            <svg class="ps-empty__icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                            <p class="ps-empty__title">{{ __('progression_stats.playtime_untracked') }}</p>
                            <p>{{ __('progression_stats.playtime_empty') }}</p>
                        </div>
                    @endif
                </section>

                <section class="ps-card">
                    <h2>{{ __('progression_stats.recent_title') }}</h2>
                    @if ($stats['recent'] === [])
                        <div class="ps-empty">
                            <svg class="ps-empty__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.6 5.5 6 .8-4.4 4.2 1.1 6-5.3-2.9-5.3 2.9 1.1-6L3.4 9.3l6-.8z" /></svg>
                            <p class="ps-empty__title">{{ __('progression_stats.recent_empty_title') }}</p>
                            <p>{{ __('progression_stats.recent_empty') }}</p>
                        </div>
                    @else
                        <ul class="ps-recent">
                            @foreach ($stats['recent'] as $milestone)
                                <li data-milestone="{{ $milestone['key'] }}">
                                    <span class="ps-recent__icon">
                                        @if ($milestone['item'] !== null)
                                            @php([$ns, $path] = explode(':', $milestone['item'], 2))
                                            <img src="{{ url('/icons/128/' . rawurlencode($ns) . '/' . implode('/', array_map('rawurlencode', explode('/', $path))) . '.png') }}" alt="" width="40" height="40" loading="lazy">
                                        @endif
                                    </span>
                                    <span class="ps-recent__text">
                                        <span class="ps-recent__title">{{ $milestone['title'] }}</span>
                                        <span class="ps-recent__meta">
                                            {{ __('progression_stats.stage_badge', ['number' => $milestone['stage']]) }}
                                            · <time datetime="{{ $milestone['completed_at'] }}">{{ \Illuminate\Support\Carbon::parse($milestone['completed_at'])->locale($locale)->diffForHumans() }}</time>
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="ps-card ps-card--wide">
                    <h2>{{ __('progression_stats.team_title') }}</h2>
                    <ul class="ps-ranking">
                        @foreach ($stats['members'] as $member)
                            <li>
                                @if ($member['name'] !== null)
                                    <img class="ps-ranking__avatar" src="https://minotar.net/helm/{{ rawurlencode($member['name']) }}/64" alt="" width="32" height="32" loading="lazy">
                                @else
                                    <span class="ps-ranking__avatar ps-ranking__avatar--none" aria-hidden="true">?</span>
                                @endif
                                <span class="ps-ranking__name">{{ $member['name'] ?? __('progression_stats.former_members') }}</span>
                                <span class="ps-bar ps-ranking__bar" aria-hidden="true"><span style="--ps-value: {{ round($member['completed'] / $teamTotal, 4) }}"></span></span>
                                <span class="ps-ranking__value" data-col="completed">{{ $member['completed'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>
        @endif
    </main>
@endsection
