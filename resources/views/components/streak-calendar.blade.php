@props(['calendar'])

@php($currentDay = $calendar['today_claimed'] ? $calendar['day_streak'] : $calendar['day_streak'] + 1)

<section class="streak-calendar" aria-labelledby="streak-calendar-title">
    <h2 id="streak-calendar-title" class="sectionTitle">{{ __('profile.streak.title') }}</h2>
    <p class="streak-summary">
        <span>{{ __('profile.streak.current') }} {{ trans_choice('profile.streak.days', $calendar['day_streak']) }}</span>
        <span>{{ __('profile.streak.best') }} {{ trans_choice('profile.streak.days', $calendar['best_streak']) }}</span>
    </p>
    <ol class="streak-week">
        @foreach ($calendar['week'] as $day)
            @php($done = $day['day'] <= $calendar['day_streak'])
            @php($isToday = $day['day'] === $currentDay)
            <li @class(['streak-day', 'done' => $done, 'today' => $isToday]) data-day="{{ $day['day'] }}">
                <span class="streak-day-number">{{ __('profile.streak.day', ['day' => $day['day']]) }}</span>
                @if ($done)
                    <span class="streak-day-status">{{ __('profile.streak.done') }}</span>
                @elseif ($isToday)
                    <span class="streak-day-status">{{ __('profile.streak.today') }}</span>
                @endif
                <ul class="streak-day-contents">
                    @foreach ($day['contents'] as $entry)
                        <li>{{ \App\Rewards\RewardLabel::of($entry) }}</li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ol>
</section>
