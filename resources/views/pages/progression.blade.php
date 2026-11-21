@extends('layout')

@section('extraHead')
    @vite(['resources/js/progression.ts'])
    <style>
        .progression-page { display: grid; grid-template-rows: auto 1fr; height: calc(100vh - 70px); min-height: 560px; }
        .progression-preview { margin: 0; font-family: var(--arf-font-body, Montserrat, sans-serif); padding: 6px 16px; font-size: 13px; background: var(--arf-color-plum-850, #261d31); color: var(--arf-color-plum-200, #d1c4e9); }
        #progression-root { min-height: 0; }
        #progression-root > p { padding: 24px; }
    </style>
@endsection

@section('content')
    <main class="progression-page">
        <p class="progression-preview">{{ __('progression.preview') }}</p>
        <div id="progression-root" aria-label="{{ $playerName ? __('progression.player_title', ['name' => $playerName]) : __('progression.title') }}">
            <p data-loading>{{ __('progression.loading') }}</p>
            <p data-error hidden>{{ __('progression.error') }}</p>
        </div>
    </main>
    <script>
        window.ArfforniaProgression = @json($config);
    </script>
@endsection
