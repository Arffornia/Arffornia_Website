@extends('layout')

@section('extraHead')
    @vite(['resources/js/progression.ts'])
    <style>
        .progression-page { display: flex; flex-direction: column; height: calc(100vh - 70px); min-height: 560px; }
        .progression-preview { margin: 0; font-family: var(--arf-font-body, Montserrat, sans-serif); padding: 6px 16px; font-size: 13px; background: var(--arf-surface-raised); color: var(--arf-text-muted); border-bottom: 1px solid var(--arf-border); }
        #progression-root { flex: 1; min-height: 0; }
        #progression-root > p { padding: 24px; }
    </style>
@endsection

@section('content')
    <main class="progression-page">
        @if ($mode === 'fixture')
            <p class="progression-preview">{{ __('progression.preview') }}</p>
        @endif
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
