@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/news/news.css")}}">
@endsection

@section('content')
<div class="standart-page">
    <div class="news">
        <img class="imgBG" src="{{ asset($news->imgUrl) }}" alt="">

        <div class="overlay">
            <div class="info">
                <div class="title">
                    <p>{{ $news->title }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="content">
        <p id="date">Publié le {{ $news->created_at->format('d/m/Y') }}.</p>
        <p id="text">{!! $news->content !!} </p>
    </div>


</div>
@endsection
@section('script')
@endsection
