@extends('layout')

@section('extraHead')
    @vite(['resources/js/progression.ts'])
    <link rel="stylesheet" href="{{ asset('css/pages/progression.css') }}">
@endsection

@section('content')
    <main class="progression-page">
        @if ($mode === 'fixture')
            <p class="progression-preview">{{ __('progression.preview') }}</p>
        @endif
        @guest
            <p class="progression-login">
                {{ __('progression.login_hint') }}
                <a href="{{ route('login') }}">{{ __('progression.login_cta') }}</a>
            </p>
        @endguest
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
