@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href=" {{asset("css/pages/home.css")}}">
@endsection

@section('content')
    
    <div class="globalContainer">
        <div class="sloganContainer" style="background-image: url({{ asset("images/screenshots1920x1080/old_spawn.png") }})">            
            <div class="sloganLeftContainer">
                <div class="sloganLeftOverlay">
                    <div class="sloganTextContainer">
                        <p>+ 330 Mods</p>
                        <p>+ 25 Palliers</p>
                        
                    </div>
                    <div class="sloganPlayBtnContainer">
                        <a href="#"><input class="mediumPlayBtn" type="button" value="Jouer"></a>
                    </div>
                </div>
            </div>

            <div class="sloganStatueContainer"></div>
        </div>
    </div>


@endsection

@section('script')
@endsection