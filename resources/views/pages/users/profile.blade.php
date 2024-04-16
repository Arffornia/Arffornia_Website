@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/profile.css")}}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/input01.css') }}">
@endsection

@section('content') 
    <div class="standart-page">
        <div class="profile">
            <img class="imgBG" src="https://media.discordapp.net/attachments/704424365856391168/1228693225615921265/old_spawn.png?ex=662cf8bd&is=661a83bd&hm=91edb78f11316299b58f0635225c9aca65806ca2e82c6334478f6d336528ffd9&=&format=webp&quality=lossless&width=1153&height=606" alt="">          
            
            <div class="skinContainer">
                <div class="player-skin">
                    <canvas class="skin_viewer" data-username="{{ $user->name }}"></canvas>
                </div>
            </div>

            <div class="overlay">
                <div class="info">
                    <p> <span class="sectionTitle">Pseudo :</span> {{ $user->name }}</p>
                    <p> <span class="sectionTitle">Argent :</span> {{ $user->money }}</p>
                    <p> <span class="sectionTitle">Stages :</span> {{ $stage_number }}</p>
                    <p> <span class="sectionTitle">Progress points :</span> {{ $user->progress_point }} </p>
                    <p> <span class="sectionTitle">Account created date :</span> {{ $user->created_at }}</p>
                    
                    <p class="lastConnexion">
                        <span class="sectionTitle">Last connexion :</span> 
                        @if ($user->last_connexion == null)
                            Never connected
                        @else
                            {{ $user->last_connexion }}
                        @endif
                    </p>
                    
                    @if (auth()->check())
                        <div class="logout">
                            <form action="/profile" method="POST">
                                @csrf
                                <x-inputSubmit01 value="Logout"></x-inputButton01>
                            </form>
                        </div>                
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @vite(['resources/js/skinviewer.js'])
@endsection