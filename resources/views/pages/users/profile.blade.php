@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/input01.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="profile">
            <img class="imgBG" src="{{ asset('images/screenshots1920x1080/old_spawn.png') }}" alt="">

            <div class="skinContainer">
                <div class="player-skin">
                    <canvas class="skin_viewer" data-control="true" data-username="{{ $user->name }}"></canvas>
                </div>
            </div>

            <div class="overlay">
                <div class="info">
                    <p> <span class="sectionTitle">{{ __('profile.username') }}</span> {{ $user->name }}</p>
                    <p> <span class="sectionTitle">{{ __('profile.money') }}</span> {{ $user->money }}</p>
                    <p> <span class="sectionTitle">{{ __('profile.team') }}</span> {{ $user->team->name ?? __('common.none') }}</p>
                    <p> <span class="sectionTitle">{{ __('profile.stage') }}</span> {{ $stage_number }}</p>
                    <p> <span class="sectionTitle">{{ __('profile.objective') }}</span> {{ $current_milestone_name }}</p>
                    <p> <span class="sectionTitle">{{ __('profile.progress_points') }}</span> {{ $user->progress_point }} </p>
                    <p> <span class="sectionTitle">{{ __('profile.created_at') }}</span> {{ $user->created_at }}</p>
                    <p> <span class="sectionTitle">{{ __('profile.roles') }}</span> {{ implode(', ', $user->getRoles()) }}</p>

                    <p class="lastConnexion">
                        <span class="sectionTitle">{{ __('profile.last_connection') }}</span>
                        @if ($user->last_connexion == null)
                            {{ __('profile.never_connected') }}
                        @else
                            {{ $user->last_connexion }}
                        @endif
                    </p>

                    @if (auth()->check())
                        <div class="logout">
                            <form action="/profile/logout" method="POST">
                                @csrf
                                <x-inputSubmit01 :value="__('profile.logout')"></x-inputButton01>
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
