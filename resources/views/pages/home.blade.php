@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href=" {{asset("css/pages/home.css")}}">
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
        <div class="palierContainer">
            <p id="palierTitle">Paliers de progression :</p>
            <div class="palierTextContainer">
                <p class="palierText">Débloquez 2/3 des jalons menant à un palier pour pouvoir débloquer un nouveau palier.</p>
                <p class="palierText">Chaque palier vous permet d'avancer un peu plus dans les mods.</p>
                <p class="palierText">Le but, équilibré les mods entre eux, ainsi que de vous faire découvrir de nouveaux mods.</p>
            </div>
            <img class="imgBG" src="{{ asset("images/palier_progres.png") }}" alt="">          
        </div>
    </div>


@endsection

@section('script')
@endsection