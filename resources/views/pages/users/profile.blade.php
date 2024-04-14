@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/profile.css")}}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/input01.css') }}">
@endsection

@section('content')
    <div class="profile">
        <div class="players-container">
            <div class="player">
                <div class="player-skin">
                    <img src="https://cdn.discordapp.com/attachments/704424365856391168/1228693242523160647/The_Gost_sniper_3D_MC_skin.png?ex=662cf8c1&is=661a83c1&hm=21474c5535ed8d8a1ed732e35df373fab7aedadd11643a1ad69d1e16bfa7869b&" alt="">
                </div>
            </div>
        </div>

        <div class="info">
            <p class="Pseudo">{{ $user->name }}</p>
            <p class="Stages"></p>
            <p class="Progress points"></p>
            <p class="Last connexion"></p>
            <p class="First join"></p>
            
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
@endsection

@section('script')
@endsection