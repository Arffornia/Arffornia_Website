@extends('admin.layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/adminPanel.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/input01.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="content-page">
            <div class="overlay">
                <div class="overlay-content">
                    <div>
                        <form action="{{ route('launcherImages.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <p class="overlay__title">Upload a new Launcher Image:</p>

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

                                <div class="checkbox-container">
                                    <label for="in_prod">✅ In production:</label>
                                    <x-inputCheckbox01 id="in_prod" name="in_prod" value="1"></x-inputCheckbox01>
                                </div>
                                @error('in_prod')
                                    <p>{{ $message }}</p>
                                @enderror

                                <x-inputText01 id="player_name" name="player_name"
                                    placeholder="Associated Player Name (Optional)"
                                    value="{{ old('player_name') }}"></x-inputText01>

                                <x-inputFile01 id="launcher_file" name="launcher_file" placeholder="Launcher file"
                                    value="{{ old('launcher_file') }}"></x-inputFile01>
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

            <img class="imgBG" src="{{ asset('images/screenshots1920x1080/old_spawn.png') }}" alt="">
        </div>

        <div class="images">
            @foreach ($launcherImages as $launcherImage)
                <div class="item">
                    <div class="content">
                        <div class="name">name: <a
                                href="/{{ $launcherImage->path }}"><b>{{ basename($launcherImage->path) }}</b></a></div>
                        <div class="in_prod">
                            <label for="prod-{{ $launcherImage->id }}">in prod:</label>
                            <input type="checkbox" class="prod-toggle-checkbox" id="prod-{{ $launcherImage->id }}"
                                data-id="{{ $launcherImage->id }}" {{ $launcherImage->in_prod ? 'checked' : '' }} />
                        </div>
                        <div class="date">date: <b>{{ $launcherImage->created_at }}</b></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/admin_launcher.js') }}" defer></script>
@endsection
