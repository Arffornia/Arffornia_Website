<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset("css/layout.css") }}">

    <link rel="icon" type="image/x-icon" href="{{ asset("images/Crafting_Table100x100.png") }}">

    @yield('extraHead')

    <title>Arffornia</title>
</head>

<header>
    <x-flash-message/>

    <div class="headerContainer">
        <div class="iconContainer">
            <a href="/"><img id=navBarIcon src="{{ asset("images/Crafting_Table100x100.png") }}" alt=""></a>
        </div>

        <div class="contentContainer">
            <a href="{{ route("adminPanel") }}">Admin Panel</a>
            <a href="{{ route("launcherVersions") }}">Launcher Versions</a>
            <a href="{{ route("launcherImages") }}">Launcher Images</a>
        </div>

        <div class="profileImageContainer">
            @if(auth()->check())
                <a href="/profile"><img id=profileImage src="https://minotar.net/avatar/{{ auth()->user()->name }}/50" alt=""></a>
            @else
                <a href="/login"><img id=profileImage src="https://minotar.net/avatar/Steve/50" alt=""></a>
            @endif

        </div>
    </div>
</header>

<body>
    @yield('content')
</body>

<footer>
    <div class="copyright">
        <p>© Arffornia - Tous droits réservés</p>
    </div>
</footer>

</html>

@yield('script')
