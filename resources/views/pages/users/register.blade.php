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
                    <form action="/register" method="POST">
                        @csrf
                        <p class="overlay__title">Register:</p>

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
                            <div>
                                <x-inputText01 id="password_confirmation" name="password_confirmation" placeholder="🔒 Password Confirmation" value="{{ old('Password Confirmation') }}"></x-inputText01>
                                @error('password_confirmation')
                                    <p>{{$message}}</p>
                                @enderror
                            </div>
                        </div>

                        <div style="margin-top: 10%">
                            <div class="overlay__btn-container">
                                <x-inputSubmit01 value="Register !"></x-inputButton01>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <img class="imgBG" src="{{ asset("images/screenshots1920x1080/old_spawn.png") }}" alt="">
    </div>
</div>
@endsection

@section('script')
@endsection
