@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/auth.css")}}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/input01.css') }}">
@endsection

@section('content')
<div class="standart-page">
    <div class="auth">
        <div class="overlay">
            <div class="overlay-content">
                <div>
                    <form action="/login" method="POST">
                        @csrf
                        <p class="overlay__title">Login:</p>

                        <div class="overlay__entries">

                            <div>
                                <x-inputText01 id="name" name="name" placeholder="👤 Pseudo Minecraft" value="{{ old('Pseudo Minecraft') }}"></x-inputText01>
                                @error('name')
                                    <p>{{$message}}</p>
                                @enderror
                            </div>
                            <div>
                                <x-inputText01 id="password" name="password" placeholder="🔒 Password" value="{{ old('Password') }}"></x-inputText01>
                                @error('password')
                                    <p>{{$message}}</p>
                                @enderror
                            </div>
                        </div>

                        <div style="margin-top: 10%">
                            <div class="overlay__btn-container">
                                <x-inputSubmit01 value="Login !"></x-inputButton01>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="create-account">
                    <a href="/register">
                        <p id="creat">Create your Account ➡️</p>
                    </a>
                </div>
                
                
            </div>
        </div>

        <img class="imgBG" src="https://cdn.discordapp.com/attachments/704424365856391168/1228693225615921265/old_spawn.png?ex=662cf8bd&is=661a83bd&hm=91edb78f11316299b58f0635225c9aca65806ca2e82c6334478f6d336528ffd9&" alt="">          
    </div>
</div>
@endsection
@section('script')
@endsection