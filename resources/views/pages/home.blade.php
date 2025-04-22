@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/gridSection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/presentationSection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/technoSection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/discord_widget.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/news/allNews.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="sloganContainer">
            <div class="sloganLeftContainer">
                <div class="sloganLeftOverlay">
                    <div class="sloganTextContainer">
                        <p>Arffornia c'est:</p>
                        <p>+ 330 Mods</p>
                        <p>+ 25 Paliers de progression</p>
                    </div>
                    <div class="sloganPlayBtnContainer">
                        <a href="#"><input class="mediumPlayBtn" type="button" value="Jouer"></a>
                    </div>
                </div>
            </div>

            <img class="imgBG" src="{{ asset('images/screenshots1920x1080/old_spawn.png') }}" alt="">

            <div class="sloganStatueContainer"></div>
        </div>

        <x-presentationSection image-src="{{ asset('images/join_us.png') }}" image-alt="Une image cool"
            overview="Présentation" title="Mon Titre" description="<p>Ceci est une description avec HTML.</p>"
            dir="left" />

        <x-gridSection title="Ma superbe grille" description="Voici un aperçu de nos blocs dynamiques." :blocks="[
            [
                'title' => 'Premier bloc',
                'description' => 'Description 1',
                'image' => 'images/screenshots1920x1080/old_spawn.png',
                'link' => '#',
            ],
            [
                'title' => 'Deuxième bloc',
                'description' => 'Description 2',
                'image' => 'images/screenshots1920x1080/old_spawn.png',
                'link' => '',
            ],
            [
                'title' => 'Deuxième bloc',
                'description' => 'Description 2',
                'image' => 'images/screenshots1920x1080/old_spawn.png',
                'link' => '',
            ],
            [
                'title' => 'Deuxième bloc',
                'description' => 'Description 2',
                'image' => 'images/screenshots1920x1080/old_spawn.png',
                'link' => '',
            ],
            [
                'title' => 'Deuxième bloc',
                'description' => 'Description 2',
                'image' => 'images/screenshots1920x1080/old_spawn.png',
                'link' => '',
            ],
            [
                'title' => 'Deuxième bloc',
                'description' => 'Description 2',
                'image' => 'images/screenshots1920x1080/old_spawn.png',
                'link' => '',
            ],

            // etc.
        ]" />

        <x-technoSection title="Nos technos du moment" :blocks="[
            [
                'title' => 'Laravel',
                'description' => 'Framework PHP',
                'image' => 'images/screenshots1920x1080/old_spawn.png',
                'link' => 'https://laravel.com',
            ],
            ['title' => 'VueJS', 'description' => 'Frontend réactif', 'image' => 'images/screenshots1920x1080/old_spawn.png'],
            ['title' => 'AlpineJS', 'description' => 'JS léger', 'image' => 'images/screenshots1920x1080/old_spawn.png'],
            ['title' => 'TailwindCSS', 'description' => 'CSS utilitaire', 'image' => 'images/screenshots1920x1080/old_spawn.png'],
        ]" />



        <div class="discord__container homeSection">
            <div class="discord__left-container">
                <p class="default-title">Rejoins Notre Discord :</p>
                <p class="discord__text"></p>

                <img id=join-us-img src="{{ asset('images/join_us.png') }}" alt="">
            </div>


            <div class="discord__widget-container">
                <discord-widget class="discord-widget" id="752121854923374614"></discord-widget>
            </div>
        </div>

        <div class="newsContent homeSection">
            <p class="default-title">News :</p>
            <div class="newsContainer">
                @foreach ($newsList as $news)
                    <a class="news" href="news/{{ $news->id }}" title="Show news">
                        <img class="newsImg" src="{{ $news->imgUrl }}">
                        <div class="textContainer">
                            <p class="title">{{ $news->title }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="seeMoreBtnContainer">
                <a href="/news"><input class="seeMoreBtn" type="button" value="Voir plus"></a>
            </div>
        </div>

        <div class="palierContainer homeSection">
            <p class="default-title">Paliers de progression :</p>
            <div class="palierTextContainer">
                <p class="palierText">Débloquez 2/3 des jalons menant à un palier pour pouvoir débloquer un nouveau palier.
                </p>
                <p class="palierText">Chaque palier vous permet d'avancer un peu plus dans les mods.</p>
                <p class="palierText">Le but, équilibré les mods entre eux, ainsi que de vous faire découvrir de nouveaux
                    mods.</p>
            </div>
            <img class="imgBG" src="{{ asset('images/palier_progres.png') }}" alt="">
        </div>

        <div class="podium homeSection">
            <div class="content">
                <div class="p2">
                    <p class="playerName">{{ $bestAllTimePlayers[1]->name }}</p>

                    <div class="player-skin">
                        <a href="/profile/{{ $bestAllTimePlayers[1]->name }}" title="Show profile">
                            <canvas class="skin_viewer" data-username="{{ $bestAllTimePlayers[1]->name }}"></canvas>
                        </a>
                    </div>

                    <div class="shape">
                        <div class="textContainer">
                            <div class="rank">#2</div>
                            <div class="score">{{ $bestAllTimePlayers[1]->progress_point }} points</div>
                        </div>
                    </div>
                </div>

                <div class="p1">
                    <p class="playerName">{{ $bestAllTimePlayers[0]->name }}</p>

                    <div class="player-skin">
                        <a href="/profile/{{ $bestAllTimePlayers[0]->name }}" title="Show profile">
                            <canvas class="skin_viewer" data-username="{{ $bestAllTimePlayers[0]->name }}"></canvas>
                        </a>
                    </div>

                    <div class="shape">
                        <div class="textContainer">
                            <div class="rank">#1</div>
                            <div class="score">{{ $bestAllTimePlayers[0]->progress_point }} points</div>
                        </div>
                    </div>
                </div>
                <div class="p3">
                    <p class="playerName">{{ $bestAllTimePlayers[2]->name }}</p>

                    <div class="player-skin">
                        <a href="/profile/{{ $bestAllTimePlayers[2]->name }}" title="Show profile">
                            <canvas class="skin_viewer" data-username="{{ $bestAllTimePlayers[2]->name }}"></canvas>
                        </a>
                    </div>

                    <div class="shape">
                        <div class="textContainer">
                            <div class="rank">#3</div>
                            <div class="score">{{ $bestAllTimePlayers[2]->progress_point }} points</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="title">
                <p>Meilleurs joueurs</p>
            </div>
        </div>

        <div class="APropos homeSection">
            <div class="sloganContainer">
                <div class="sloganLeftContainer">
                    <div class="sloganLeftOverlay">
                        <div>
                            <p id="slogan__title">A propos:</p>
                            <div class="slogan__text-container">
                                <p>Arffornia est un serveur survie moddé,</p>
                                <p> orienté build.</p>
                                <br>
                                <p>Construisiez votre nouvelle base,</p>
                                <p>usine avec plus de 330 mods.</p>
                            </div>
                        </div>
                        <div class="sloganPlayBtnContainer">
                            <a href="#"><input class="mediumPlayBtn" type="button" value="Jouer"></a>
                        </div>
                    </div>
                </div>

                <img class="imgBG" src="{{ asset('images/bg.png') }}" alt="">

                <div class="sloganStatueContainer"></div>
            </div>
        </div>
    @endsection

    @section('script')
        <script src="{{ asset('js/discord_widget.js') }}"></script>
        @vite(['resources/js/skinviewer.js'])
    @endsection
