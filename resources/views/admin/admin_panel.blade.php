@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/profile.css")}}">
    <link rel="stylesheet" href="{{ asset('css/components/inputText/input01.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <h1>This is the admin panel, only admin can be here.</h1>
    </div>
@endsection

@section('script')
@endsection
