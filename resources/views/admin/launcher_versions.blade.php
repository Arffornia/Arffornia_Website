@extends('admin.layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/adminPanel.css")}}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/input01.css') }}">
@endsection

@section('content')
<div class="standart-page">
    <div class="content-page">
        <div class="overlay">
            <div class="overlay-content">
                <div>
                    <form action="{{ route("launcherVersions.upload") }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <p class="overlay__title">Upload a new Launcher Version:</p>

                        <div class="overlay__entries">
                            @if ($errors->any())
                            <div>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <x-inputText01 id="launcher_version" name="launcher_version" placeholder="📜 Launcher version" value="{{ old('launcher_version') }}"></x-inputText01>
                            @error('launcher_version')
                                <p>{{$message}}</p>
                            @enderror

                            <div class="checkbox-container">
                                <label for="in_prod">✅ In production:</label>
                                <x-inputCheckbox01 id="in_prod" name="in_prod" value="1"></x-inputCheckbox01>
                            </div>
                            @error('in_prod')
                                <p>{{$message}}</p>
                            @enderror

                            <x-inputFile01 id="launcher_file" name="launcher_file" placeholder="Launcher file" value="{{ old('launcher_file') }}"></x-inputFile01>
                        </div>

                        <div style="margin-top: 10%">
                            <div class="overlay__btn-container">
                                <x-inputSubmit01 value="Send !"></x-inputButton01>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <img class="imgBG" src="{{ asset("images/screenshots1920x1080/old_spawn.png") }}" alt="">
    </div>

    <div class="versions">

        @foreach ($launcherVersions as $launcherVersion)
            <div class="item">
                <div class="content">
                    <div class="version">version: <b>{{ $launcherVersion->version }}</b></div>
                    <div class="in_prod">in prod: <input type="checkbox" onclick="return false;" {{ $launcherVersion->in_prod ? 'checked' : '' }} /></div>
                    <div class="name">name: <b>{{ basename($launcherVersion->path) }}</b></div>
                    <div class="date">date: <b>{{ $launcherVersion->created_at }}</b></div>
                </div>
            </div>
        @endforeach

    </div>
</div>
@endsection

@section('script')
@endsection
