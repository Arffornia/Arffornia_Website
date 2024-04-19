<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ asset("css/layout.css") }}">

    <link rel="icon" type="image/x-icon" href="https://media.discordapp.net/attachments/704424365856391168/1228693208805150760/Crafting_Table100x100.png?ex=662cf8b8&is=661a83b8&hm=107e7b5496049ae2ab4c188328dc6a61cbdffc1ffbe54592fbe1e539ff75b88b&=&format=webp&quality=lossless&width=125&height=125">

    @yield('extraHead')

    <title>Arffornia</title>
</head>

<header>
    <x-flash-message/>

    <div class="headerContainer">
        <div class="iconContainer">
            <a href="/"><img id=navBarIcon src="https://media.discordapp.net/attachments/704424365856391168/1228693208805150760/Crafting_Table100x100.png?ex=662cf8b8&is=661a83b8&hm=107e7b5496049ae2ab4c188328dc6a61cbdffc1ffbe54592fbe1e539ff75b88b&=&format=webp&quality=lossless&width=125&height=125" alt=""></a>
        </div>

        <div class="contentContainer">
            <a href="#">Map</a>
            <a href="/news">News</a>
            <a href="#">Vote</a>
            <a href="#">Boutique</a>
            <a href="/reglement">Règlement</a>
            <a href="/stages">Paliers</a>
        </div>

        <div class="profileImageContainer">
            @if(auth()->check())
                <a href="/profile"><img id=profileImage src="https://minotar.net/avatar/{{ auth()->user()->name }}/50" alt=""></a> 
            @else
                <a href="/login"><img id=profileImage src="https://minotar.net/avatar/The_Gost_sniper/50" alt=""></a> 
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