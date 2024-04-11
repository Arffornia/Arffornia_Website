@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/home.css")}}">
    <link rel="stylesheet" href="{{asset("css/discord_widget.css")}}">
@endsection

@section('content')
    
    <div class="globalContainer">
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

        <div class="scoreboard">
            <div class="all-time">
                <p class="scoreboard__title">Meilleurs joueurs</p>
                <div class="players-container">
                    <div class="player">
                        <p class="player-position">#1</p>
                        <div class="player-skin">
                            <img src="{{ asset("images/mc_skins/The_Gost_sniper_3D_MC_skin.png") }}" alt="">
                        </div>
                        <div class="player-info">
                            <p class="player-name">The_Gost_sniper</p>
                            <p class="score">15666 points</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="vote">
                <p class="scoreboard__title">Meilleurs voteurs</p>
                <div class="players-container"></div>
            </div>
        </div>







        <!-- 
        <div class="info">
            <div class="info__content">
                <div class="info_player-stat">
                    <p id="info__player-stat-text">Joueur en ligne : </p>
                </div>

                <div class="info__play-btn">
                    <a href="#"><input class="mediumPlayBtn" type="button" value="Jouer"></a>
                </div>  
                
                <a href="https://discord.gg/CWH6w67">
                    <div class="info__discord">
                        <svg height="40" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M524.5 69.8a1.5 1.5 0 0 0 -.8-.7A485.1 485.1 0 0 0 404.1 32a1.8 1.8 0 0 0 -1.9 .9 337.5 337.5 0 0 0 -14.9 30.6 447.8 447.8 0 0 0 -134.4 0 309.5 309.5 0 0 0 -15.1-30.6 1.9 1.9 0 0 0 -1.9-.9A483.7 483.7 0 0 0 116.1 69.1a1.7 1.7 0 0 0 -.8 .7C39.1 183.7 18.2 294.7 28.4 404.4a2 2 0 0 0 .8 1.4A487.7 487.7 0 0 0 176 479.9a1.9 1.9 0 0 0 2.1-.7A348.2 348.2 0 0 0 208.1 430.4a1.9 1.9 0 0 0 -1-2.6 321.2 321.2 0 0 1 -45.9-21.9 1.9 1.9 0 0 1 -.2-3.1c3.1-2.3 6.2-4.7 9.1-7.1a1.8 1.8 0 0 1 1.9-.3c96.2 43.9 200.4 43.9 295.5 0a1.8 1.8 0 0 1 1.9 .2c2.9 2.4 6 4.9 9.1 7.2a1.9 1.9 0 0 1 -.2 3.1 301.4 301.4 0 0 1 -45.9 21.8 1.9 1.9 0 0 0 -1 2.6 391.1 391.1 0 0 0 30 48.8 1.9 1.9 0 0 0 2.1 .7A486 486 0 0 0 610.7 405.7a1.9 1.9 0 0 0 .8-1.4C623.7 277.6 590.9 167.5 524.5 69.8zM222.5 337.6c-29 0-52.8-26.6-52.8-59.2S193.1 219.1 222.5 219.1c29.7 0 53.3 26.8 52.8 59.2C275.3 311 251.9 337.6 222.5 337.6zm195.4 0c-29 0-52.8-26.6-52.8-59.2S388.4 219.1 417.9 219.1c29.7 0 53.3 26.8 52.8 59.2C470.7 311 447.5 337.6 417.9 337.6z"/></svg>
                        <p>Discord</p>
                    </div>
                </a>
            </div>
        </div>
        -->


@endsection

@section('script')
    <script src="{{asset("js/discord_widget.js")}}"></script>
@endsection