@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/progression-stats.css') }}">
@endsection

@section('content')
    <main class="standart-page progression-stats">
        <section class="ps-hero" aria-labelledby="ps-team-name">
            <div class="ps-hero__identity">
                <img class="ps-hero__avatar" src="https://minotar.net/helm/{{ rawurlencode($playerName) }}/128" alt="" width="96" height="96">
                <div>
                    <p class="ps-eyebrow">{{ __('progression_stats.team_eyebrow') }}</p>
                    <h1 class="ps-hero__name" id="ps-team-name" data-stat="team">{{ $team['team'] ?? $playerName }}</h1>
                    <p class="ps-hero__stage">
                        <a class="ps-button" href="{{ route('progression.player.stats', ['playerName' => $playerName]) }}">{{ __('progression_stats.back_to_stats') }}</a>
                        <a class="ps-button" href="{{ route('progression.player', ['playerName' => $playerName]) }}">{{ __('progression_stats.back_to_tree') }}</a>
                    </p>
                </div>
            </div>
        </section>

        @if ($team === null)
            <div class="ps-empty">
                <p>{{ __('progression_stats.no_model') }}</p>
            </div>
        @else
            @php($locale = app()->getLocale())
            <div class="ps-grid">
                @foreach ($team['members'] as $member)
                    <section class="ps-card" data-member="{{ $member['name'] ?? '' }}">
                        <h2 class="ps-member">
                            @if ($member['name'] !== null)
                                <img class="ps-ranking__avatar" src="https://minotar.net/helm/{{ rawurlencode($member['name']) }}/64" alt="" width="32" height="32" loading="lazy">
                            @else
                                <span class="ps-ranking__avatar ps-ranking__avatar--none" aria-hidden="true">?</span>
                            @endif
                            <span class="ps-ranking__name">{{ $member['name'] ?? __('progression_stats.former_members') }}</span>
                            <span class="ps-chip ps-chip--accent" data-col="count">{{ __('progression_stats.team_milestones', ['count' => count($member['milestones'])]) }}</span>
                        </h2>
                        @if ($member['milestones'] === [])
                            <p class="ps-empty">{{ __('progression_stats.team_member_empty') }}</p>
                        @else
                            <ul class="ps-recent">
                                @foreach ($member['milestones'] as $milestone)
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
                                                · <time datetime="{{ $milestone['completed_at'] }}">{{ \Illuminate\Support\Carbon::parse($milestone['completed_at'])->locale($locale)->isoFormat('ll') }}</time>
                                            </span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </main>
@endsection
