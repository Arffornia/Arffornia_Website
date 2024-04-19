@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/news/allNews.css")}}">
@endsection

@section('content')
<div class="standart-page">
    <div class="newsContainer">
        @foreach ($newsList as $news)
        <a class="news" href="news/{{ $news->id }}" title="Show news">
            <img class="newsImg" src="{{ $news->imgUrl }}">
            <div class="textContainer">
                <p class="title">{{ $news->title }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection
@section('script')
@endsection