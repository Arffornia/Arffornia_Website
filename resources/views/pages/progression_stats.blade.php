@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/progression-stats.css') }}">
@endsection

@section('content')
    <main class="standart-page progression-stats">
        <h1>{{ __('progression_stats.player_title', ['name' => $playerName]) }}</h1>
        <p><a href="{{ route('progression.player', ['playerName' => $playerName]) }}">{{ __('progression_stats.back_to_tree') }}</a></p>

        @if ($stats === null)
            <p>{{ __('progression_stats.no_model') }}</p>
        @else
            <dl class="progression-stats__tiles">
                <div><dt>{{ __('progression_stats.overall') }}</dt><dd data-stat="overall">{{ $stats['done'] }} / {{ $stats['total'] }} ({{ round($stats['ratio'] * 100) }} %)</dd></div>
                <div><dt>{{ __('progression_stats.open_stage') }}</dt><dd data-stat="open-stage">{{ $stats['open_stage'] }}</dd></div>
                <div><dt>{{ __('progression_stats.rank') }}</dt><dd data-stat="rank">{{ __('progression_stats.rank_value', ['rank' => $stats['rank'], 'ranked' => max($stats['ranked'], $stats['rank'])]) }}</dd></div>
            </dl>

            <h2>{{ __('progression_stats.per_stage_title') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('progression_stats.stage') }}</th>
                        <th>{{ __('progression_stats.progress') }}</th>
                        <th>{{ __('progression_stats.time') }}</th>
                        <th>{{ __('progression_stats.playtime') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stats['stages'] as $stage)
                        <tr data-stage="{{ $stage['number'] }}">
                            <td>{{ $stage['number'] }}</td>
                            <td data-col="progress">{{ $stage['done'] }} / {{ $stage['total'] }}</td>
                            <td><x-duration :seconds="$stage['seconds']" /></td>
                            <td data-col="playtime"><x-duration :seconds="$stage['playtime_seconds']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h2>{{ __('progression_stats.team_title') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('progression_stats.member') }}</th>
                        <th>{{ __('progression_stats.completed') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stats['members'] as $member)
                        <tr>
                            <td>{{ $member['name'] ?? __('progression_stats.former_members') }}</td>
                            <td data-col="completed">{{ $member['completed'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </main>
@endsection
