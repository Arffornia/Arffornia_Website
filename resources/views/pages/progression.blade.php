@extends('layout')

@section('extraHead')
    @vite(['resources/js/progression.ts'])
    <style>
        .progression-page { display: flex; flex-direction: column; height: calc(100vh - 70px); min-height: 560px; }
        .progression-preview { margin: 0; font-family: var(--arf-font-body, Montserrat, sans-serif); padding: 6px 16px; font-size: 13px; background: var(--arf-surface-raised); color: var(--arf-text-muted); border-bottom: 1px solid var(--arf-border); }
        .progression-login { margin: 0; font-family: var(--arf-font-body, Montserrat, sans-serif); padding: 8px 16px; font-size: 14px; display: flex; gap: 12px; align-items: center; background: var(--arf-surface-raised); color: var(--arf-text-muted); border-bottom: 1px solid var(--arf-border); }
        .progression-login a { color: #fff; background: var(--arf-color-brand-orange); padding: 4px 12px; border-radius: 6px; font-weight: 650; text-decoration: none; }
        #progression-root { flex: 1; min-height: 0; }
        #progression-root > p { padding: 24px; }
    </style>
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
