@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/profile.css")}}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/input01.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <h1>Launcher versions :</h1>
    </div>

    <h1 class="title">Upload file</h1>
    @if ($errors->any())
    <div>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route("launcherVersions.upload") }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file_upload">
        <x-inputSubmit01 value="Send !"></x-inputButton01>
    </form>

@endsection

@section('script')
@endsection
