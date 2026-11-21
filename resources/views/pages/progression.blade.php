@extends('layout')

@section('extraHead')
    @vite(['resources/js/progression.ts'])
    <link rel="stylesheet" href="{{ asset('css/pages/progression.css') }}">
@endsection

@section('headerSearch')
    <button type="button" class="headerSearch" data-progression-search aria-label="{{ __('progression.search') }} (Ctrl+K)">
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
    </main>
    <script>
        window.ArfforniaProgression = @json($config);
    </script>
@endsection
