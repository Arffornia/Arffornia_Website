@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/home.css")}}">
    <link rel="stylesheet" href="{{asset("css/discord_widget.css")}}">
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

            <img class="imgBG" src="{{ asset("images/screenshots1920x1080/old_spawn.png") }}" alt="">          

            <div class="sloganStatueContainer"></div>
        </div>

        <div class="discord__container">
            <div class="discord__left-container">
                <p id="dicord__title">Rejoins Notre Discord :</p>
                <p class="discord__text"></p>

                <img id=join-us-ing src="{{ asset("images/join_us.png") }}" alt="">

            </div>


            <div>
                <discord-widget class="discord-widget" id="752121854923374614" height="490px" width="400px"></discord-widget>
            </div>
        </div>

        <div class="palierContainer">
            <p id="palierTitle">Paliers de progression :</p>
            <div class="palierTextContainer">
                <p class="palierText">Débloquez 2/3 des jalons menant à un palier pour pouvoir débloquer un nouveau palier.</p>
                <p class="palierText">Chaque palier vous permet d'avancer un peu plus dans les mods.</p>
                <p class="palierText">Le but, équilibré les mods entre eux, ainsi que de vous faire découvrir de nouveaux mods.</p>
            </div>
            <img class="imgBG" src="{{ asset("images/palier_progres.png") }}" alt="">          
        </div>

        <!--
        <div class="scoreboard">
            <div class="all-time">
                <p class="scoreboard__title">Meilleurs joueurs</p>
                <div class="players-container">
                    @foreach ($bestVotePlayers as $index => $player)
                        <div class="player">
                            <p class="player-position">#{{ $index + 1 }}</p>
                            <div class="player-skin">
                                <img src="{{ asset("images/mc_skins/The_Gost_sniper_3D_MC_skin.png") }}" alt="">
                            </div>
                            <div class="player-info">
                                <p class="player-name">{{ $player['name'] }}</p>
                                <p class="score">{{ $player['vote_count'] }} votes</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="vote">
                <p class="scoreboard__title">Meilleurs voteurs</p>
                <div class="players-container">
                    @foreach ($bestAllTimePlayers as $index => $player)
                        <div class="player">
                            <p class="player-position">#{{ $index + 1 }}</p>
                            <div class="player-skin">
                                <img src="{{ asset("images/mc_skins/The_Gost_sniper_3D_MC_skin.png") }}" alt="">
                            </div>
                            <div class="player-info">
                                <p class="player-name">{{ $player->name }}</p>
                                <p class="score">{{ $player->progress_point }} points</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        -->

        <div class="podium">
            <div class="content">
                <div class="p2">
                    <p class="playerName">{{ $bestAllTimePlayers[1]->name }}</p>
                    
                    <div class="skin">
                        <img src="{{ asset("images/mc_skins/The_Gost_sniper_3D_MC_skin.png") }}" alt="">
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
                        <img src="{{ asset("images/mc_skins/The_Gost_sniper_3D_MC_skin.png") }}" alt="">
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
                        <img src="{{ asset("images/mc_skins/The_Gost_sniper_3D_MC_skin.png") }}" alt="">
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
        
        <div class="APropos">
            <div class="sloganContainer">
                <div class="sloganLeftContainer">
                    <div class="sloganLeftOverlay">
                        <div>
                            <p id="slogan__title">A propos:</p>
                            <div class="slogan__text-container">
                                <p>Arffornia est un serveur survie moddé, orienté build.</p>
                                <p>Construisiez votre nouvelle base, usine avec plus de 330 mods.</p>
                            </div>
                        </div>
                        <div class="sloganPlayBtnContainer">
                            <a href="#"><input class="mediumPlayBtn" type="button" value="Jouer"></a>
                        </div>
                    </div>
                </div>

                <img class="imgBG" src="{{ asset("images/bg.png") }}" alt="">          

                <div class="sloganStatueContainer"></div>
            </div>
        </div>

@endsection

@section('script')
    <script src="{{asset("js/discord_widget.js")}}"></script>
@endsection