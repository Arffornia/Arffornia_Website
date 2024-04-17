@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/stages.css")}}">
@endsection

@section('content')
    <div class="bg">
        <div class="canvas">
        </div>
    </div>   
@endsection
@section('script')
    <script src="{{asset("js/stages.js")}}"></script>
@endsection