@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/gridSection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/homePresentationSection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/presentationSection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/technoSection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/newsSection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/discord_widget.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/news/allNews.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/dropDown.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="sloganContainer">
            <div class="sloganLeftContainer">
                <div class="sloganLeftOverlay">
                    <div class="sloganTextContainer">
                        <p>{{ __('home.slogan.intro') }}</p>
                        <p>{{ __('home.slogan.mods') }}</p>
                        <p>{{ __('home.slogan.stages') }}</p>
                    </div>
                    <div class="sloganPlayBtnContainer">
                        <x-dropDownBtn :title="__('home.download')" :items="[
                            ['label' => 'Windows', 'url' => '/download/windows'],
                            ['label' => 'Linux', 'url' => '/download/linux'],
                            ['label' => 'Mac', 'url' => '/download/mac'],
                        ]" />
                    </div>
                </div>
            </div>

            <img class="imgBG" src="{{ asset('images/screenshots1920x1080/old_spawn.png') }}" alt="">

            <div class="sloganStatueContainer"></div>
        </div>

        @php
            $newsBlocks = $newsList
                ->map(function ($news) {
                    return [
                        'title' => $news->title,
                        'description' => __('home.published_on', ['date' => $news->created_at->format('d/m/Y')]),
                        'image' => asset($news->imgUrl),
                        'link' => url('/news/' . $news->id),
                    ];
                })
                ->all();
        @endphp

        <x-newsSection :title="__('news.latest')" :blocks="$newsBlocks" see-more-link="{{ url('/news') }}" />

        <x-home-presentation-section image-src="{{ asset('images/join_us.png') }}" :image-alt="__('home.presentation.image_alt')"
            :overview="__('home.presentation.overview')" :title="__('home.presentation.title')"
            :description="__('home.presentation.description')" />

        <div class="discord__container homeSection">
            <div class="discord__left-container">
                <p class="default-title">{{ __('home.discord_title') }}</p>
                <p class="discord__text"></p>

                <img id=join-us-img src="{{ asset('images/join_us.png') }}" alt="{{ __('home.discord_title') }}">
            </div>


            <div class="discord__widget-container">
                <discord-widget class="discord-widget" id="752121854923374614"></discord-widget>
            </div>
        </div>

        <x-technoSection :title="__('home.featured_mods.title')" :blocks="[
            [
                'title' => 'Create',
                'description' => __('home.featured_mods.create'),
                'image' => 'https://media.forgecdn.net/avatars/1065/184/638598725500886388.png',
            ],
            [
                'title' => 'Applied Energistics 2',
                'description' => __('home.featured_mods.ae2'),
                'image' => 'https://media.forgecdn.net/avatars/1025/127/638548475358792693.webp',
            ],
            [
                'title' => 'Mekanism',
                'description' => __('home.featured_mods.mekanism'),
                'image' => 'https://cdn.modrinth.com/data/Ce6I4WUE/icon.png',
            ],
            [
                'title' => 'Ender IO',
                'description' => __('home.featured_mods.enderio'),
                'image' => 'https://media.forgecdn.net/avatars/thumbnails/6/770/64/64/635368290959736289.png',
            ],
        ]" />

        <x-presentationSection image-src="{{ asset('images/palier_progres.png') }}" :image-alt="__('home.stages.image_alt')"
            :overview="__('home.stages.overview')" :title="__('home.stages.title')"
            :description="__('home.stages.description')" dir="right" />

        <div class="podium homeSection">
            <div class="content">
                {{-- Podium order on screen: #2, #1, #3. Missing slots are skipped when fewer than 3 players exist. --}}
                @foreach ([1 => 'p2', 0 => 'p1', 2 => 'p3'] as $rankIndex => $podiumClass)
                    @php($podiumPlayer = $bestAllTimePlayers[$rankIndex] ?? null)
                    @if ($podiumPlayer)
                        <div class="{{ $podiumClass }}">
                            <p class="playerName">{{ $podiumPlayer->name }}</p>

                            <div class="player-skin">
                                <a href="/profile/{{ $podiumPlayer->name }}" title="{{ __('common.show_profile') }}">
                                    <canvas class="skin_viewer" data-username="{{ $podiumPlayer->name }}"></canvas>
                                </a>
                            </div>

                            <div class="shape">
                                <div class="textContainer">
                                    <div class="rank">#{{ $rankIndex + 1 }}</div>
                                    <div class="score">{{ __('common.points', ['count' => $podiumPlayer->progress_point]) }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="title">
                <p>{{ __('home.best_players') }}</p>
            </div>
        </div>

        <div class="APropos homeSection">
            <div class="sloganContainer">
                <div class="sloganLeftContainer">
                    <div class="sloganLeftOverlay">
                        <div>
                            <p id="slogan__title">{{ __('home.about.title') }}</p>
                            <div class="slogan__text-container">
                                <p>{{ __('home.about.line1') }}</p>
                                <p>{{ __('home.about.line2') }}</p>
                                <br>
                                <p>{{ __('home.about.line3') }}</p>
                                <p>{{ __('home.about.line4') }}</p>
                            </div>
                        </div>
                        <div class="sloganPlayBtnContainer">
                            <a href="#"><input class="mediumPlayBtn" type="button" value="{{ __('home.about.play') }}"></a>
                        </div>
                    </div>
                </div>

                <img class="imgBG" src="{{ asset('images/bg.png') }}" alt="">

                <div class="sloganStatueContainer"></div>
            </div>
        </div>
    @endsection

    @section('script')
        <script src="{{ asset('js/slideshow.js') }}" defer></script>
        <script src="{{ asset('js/discord_widget.js') }}"></script>
        <script src="{{ asset('js/fetch_launcher_release.js') }}"></script>
        @vite(['resources/js/skinviewer.js'])
    @endsection
