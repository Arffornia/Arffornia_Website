@extends('admin.layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/progression-stats.css') }}">
@endsection

@section('content')
    <main class="standart-page progression-stats">
        <h1>{{ __('progression_stats.admin_title') }}</h1>

        @if ($stats === null)
            <p>{{ __('progression_stats.no_model') }}</p>
        @else
            <p class="progression-stats__meta">{{ __('progression_stats.generated', ['version' => $stats['snapshot_version'], 'time' => $stats['generated_at']]) }}</p>

            <dl class="progression-stats__tiles">
                <div><dt>{{ __('progression_stats.progressions') }}</dt><dd data-stat="progressions">{{ $stats['progressions'] }}</dd></div>
                <div><dt>{{ __('progression_stats.active_progressions') }}</dt><dd data-stat="active">{{ $stats['active_progressions'] }}</dd></div>
                <div><dt>{{ __('progression_stats.playtime_players') }}</dt><dd data-stat="playtime-players">{{ $stats['playtime']['players'] }}</dd></div>
                <div><dt>{{ __('progression_stats.playtime_total') }}</dt><dd data-stat="playtime-total"><x-duration :seconds="$stats['playtime']['seconds']" /></dd></div>
            </dl>

            <h2>{{ __('progression_stats.stages_title') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('progression_stats.stage') }}</th>
                        <th>{{ __('progression_stats.name') }}</th>
                        <th>{{ __('progression_stats.at_stage') }}</th>
                        <th>{{ __('progression_stats.reached') }}</th>
                        <th>{{ __('progression_stats.median_time') }}</th>
                        <th>{{ __('progression_stats.samples') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stats['stages'] as $stage)
                        <tr data-stage="{{ $stage['number'] }}">
                            <td>{{ $stage['number'] }}</td>
                            <td>{{ $stage['name'] }}</td>
                            <td data-col="at">{{ $stage['progressions'] }}</td>
                            <td data-col="reached">{{ $stage['reached'] }} ({{ round($stage['reached_rate'] * 100) }} %)</td>
                            <td><x-duration :seconds="$stage['median_seconds']" /></td>
                            <td>{{ $stage['samples'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h2>{{ __('progression_stats.stalls_title') }}</h2>
            <p class="progression-stats__meta">{{ __('progression_stats.stalls_hint') }}</p>
            @if ($stats['stalls'] === [])
                <p>{{ __('progression_stats.none') }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('progression_stats.milestone') }}</th>
                            <th>{{ __('progression_stats.stage') }}</th>
                            <th>{{ __('progression_stats.waiting') }}</th>
                            <th>{{ __('progression_stats.completed') }}</th>
                            <th>{{ __('progression_stats.rate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['stalls'] as $milestone)
                            <tr data-milestone="{{ $milestone['key'] }}">
                                <td>{{ $milestone['title'] }}</td>
                                <td>{{ $milestone['stage'] }}</td>
                                <td data-col="waiting">{{ $milestone['waiting'] }}</td>
                                <td data-col="completed">{{ $milestone['completed'] }}</td>
                                <td>{{ round($milestone['rate'] * 100) }} %</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <h2>{{ __('progression_stats.activity_title') }}</h2>
            @if ($stats['activity'] === [])
                <p>{{ __('progression_stats.none') }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('progression_stats.day') }}</th>
                            <th>{{ __('progression_stats.completed') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['activity'] as $day)
                            <tr data-day="{{ $day['day'] }}">
                                <td>{{ $day['day'] }}</td>
                                <td data-col="completed">{{ $day['completed'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    </main>
@endsection
