<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset("css/layout.css") }}">
    <link rel="stylesheet" href="{{ asset("css/header.css") }}">
    <link rel="stylesheet" href="{{ asset("css/footer.css") }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('images/Crafting_Table100x100.png') }}">

    @yield('extraHead')

    <title>Arffornia</title>
</head>

<header>
    <div class="headerContainer">
        <div class="iconContainer">
            <a href="/"><img id=navBarIcon src="{{ asset('images/Crafting_Table100x100.png') }}" alt=""></a>
        </div>

        <div class="contentContainer">
            <a href="#">Map</a>
            <a href="#">News</a>
            <a href="#">Vote</a>
            <a href="#">Boutique</a>
            <a href="#">Règlement</a>
        </div>

        <div class="profileImageContainer">
            @if(auth()->check())
                <a href="/"><img id=profileImage src="https://minotar.net/avatar/{{ auth()->user()->name }}/50" alt=""></a> 
            @else
                <a href="/"><img id=profileImage src="https://minotar.net/avatar/The_Gost_sniper/50" alt=""></a> 
            @endif
            
        </div>
    </div>
</header>

<body>   
    @yield('content')
</body>

<footer>
    
</footer>

</html>

@yield('script')