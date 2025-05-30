@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/auth.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="auth">
            <div class="overlay">
                <div class="overlay-content">
                    <div>
                        <p class="overlay__title">Login with Microsoft:</p>
                        <div style="margin-top: 10%">
                            <div class="overlay__btn-container">

                                <a href="/login/MS-Auth" class="msAuthBtn">
                                    <div class="content">
                                        <svg class="msIcon" aria-hidden="true" viewBox="0 0 25 25" fill="none"
                                            xmlns="http://www.w3.org/2000/svg" itemprop="logo" itemscope="itemscope">
                                            <path d="M11.5216 0.5H0V11.9067H11.5216V0.5Z" fill="#f25022"></path>
                                            <path d="M24.2418 0.5H12.7202V11.9067H24.2418V0.5Z" fill="#7fba00"></path>
                                            <path d="M11.5216 13.0933H0V24.5H11.5216V13.0933Z" fill="#00a4ef"></path>
                                            <path d="M24.2418 13.0933H12.7202V24.5H24.2418V13.0933Z" fill="#ffb900"></path>
                                        </svg>

                                        <p>Login !</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <img class="imgBG" src="{{ asset('images/screenshots1920x1080/old_spawn.png') }}" alt="">
        </div>
    </div>
@endsection
@section('script')
@endsection
