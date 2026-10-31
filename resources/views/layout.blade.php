<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/Crafting_Table100x100.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'public/css/components/notification.css'])

    @yield('extraHead')

    {{-- Translations for public/js widgets (window.ArfforniaI18n.widgets.*) --}}
    <script>
        window.ArfforniaI18n = {
            locale: @json(app()->getLocale()),
            widgets: @json(__('widgets')),
        };
    </script>

    <title>Arffornia</title>
</head>

<header>
    <div class="headerContainer">
        <div class="iconContainer">
            <a href="/"><img id=navBarIcon src="{{ asset('images/Crafting_Table100x100.png') }}"
                    alt=""></a>
        </div>

        <div class="contentContainer">
            <a href="https://map.arffornia.com/">{{ __('nav.map') }}</a>
            <a href="/news">{{ __('nav.news') }}</a>
            <a href="/vote">{{ __('nav.vote') }}</a>
            <a href="/shop">{{ __('nav.shop') }}</a>
            <a href="/reglement">{{ __('nav.rules') }}</a>
            <a href="/stages">{{ __('nav.stages') }}</a>

            @if (auth()->check() && auth()->user()->hasRole('admin'))
                <a href="{{ route('adminPanel') }}">{{ __('nav.admin_panel') }}</a>
            @endif
        </div>

        <x-localeSwitcher />

        <div class="profileImageContainer">
            @if (auth()->check())
                <a href="/profile" title="{{ __('nav.profile') }}"><img id=profileImage src="https://minotar.net/avatar/{{ auth()->user()->name }}/50"
                        alt="{{ __('nav.profile') }}"></a>
            @else
                <a href="/login" title="{{ __('nav.login') }}"><img id=profileImage src="https://minotar.net/avatar/Steve/50" alt="{{ __('nav.login') }}"></a>
            @endif

        </div>
    </div>
</header>

<body>
    @yield('content')
</body>

<footer>
    <div class="copyright">
        <p>{{ __('common.copyright') }}</p>
        <p class="minecraft-disclaimer">{{ __('common.minecraft_disclaimer') }}</p>
        <nav class="legal-links">
            <a href="{{ route('legal.notice') }}">{{ __('legal.footer.notice') }}</a>
            <a href="{{ route('legal.privacy') }}">{{ __('legal.footer.privacy') }}</a>
            <a href="{{ route('legal.terms') }}">{{ __('legal.footer.terms') }}</a>
            <a href="{{ route('faq') }}">{{ __('faq.footer') }}</a>
        </nav>
    </div>
</footer>

</html>

@if (session('message') || session('error'))
    <script type="module">
        import {
            showNotification
        } from '{{ Vite::asset('resources/js/notification.js') }}';

        document.addEventListener('DOMContentLoaded', () => {
            const message = @json(session('message') ?? session('error'));
            const type = "{{ session('message') ? 'success' : 'error' }}";

            showNotification(message, type);
        });
    </script>
@endif

@yield('script')
