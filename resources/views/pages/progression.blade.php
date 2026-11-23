@extends('layout')

@section('extraHead')
    @vite(['resources/js/progression.ts'])
    <link rel="stylesheet" href="{{ asset('css/pages/progression.css') }}">
@endsection

@section('headerSearch')
    <button type="button" class="headerSearch" data-progression-search aria-label="{{ __('progression.search') }}" aria-keyshortcuts="Control+K Meta+K">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4"/></svg>
        <span>{{ __('progression.search') }}</span>
        <kbd data-shortcut></kbd>
    </button>
@endsection

@section('content')
    <main class="progression-page">
        <div id="progression-root" aria-label="{{ $playerName ? __('progression.player_title', ['name' => $playerName]) : __('progression.title') }}">
            <p data-loading>{{ __('progression.loading') }}</p>
            <p data-error hidden>{{ __('progression.error') }}</p>
            <p data-message hidden></p>
        </div>
        @if ($playerName)
            <aside class="progression-side" data-progression-side aria-label="{{ __('progression.next_title') }}">
                @if ($privacy)
                    <label class="progression-privacy">
                        <input type="checkbox" role="switch" data-privacy-switch data-url="{{ $privacy['url'] }}" data-csrf="{{ csrf_token() }}" @checked($privacy['public'])>
                        <span>{{ __('progression.privacy_public') }}</span>
                    </label>
                    <p class="progression-privacy__hint" data-privacy-hint>{{ __('progression.privacy_hint') }}</p>
                @endif
                @if ($statsUrl)
                    <a class="progression-stats-link" href="{{ $statsUrl }}" data-stats-link>{{ __('progression.stats_link') }}</a>
                @endif
                <div data-next hidden>
                    <h2>{{ __('progression.next_title') }}</h2>
                    <ol></ol>
                </div>
            </aside>
        @endif
    </main>
    <script>
        window.ArfforniaProgression = @json($config);
    </script>
@endsection
