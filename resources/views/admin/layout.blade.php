<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/Crafting_Table100x100.png') }}">

    @yield('extraHead')

    <title>Arffornia</title>
</head>

<header>
    <div class="headerContainer">
        <div class="iconContainer">
            <a href="/"><img id=navBarIcon src="{{ asset('images/Crafting_Table100x100.png') }}"
                    alt=""></a>
        </div>

        <div class="contentContainer">
            <a href="{{ route('adminPanel') }}">{{ __('nav.admin_panel') }}</a>
            <a href="{{ route('launcherImages') }}">{{ __('nav.launcher_images') }}</a>
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
    </div>
</footer>

</html>

@yield('script')
