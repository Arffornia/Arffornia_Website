@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/news/allNews.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/newsSection.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="news-section">
            <div class="info">
                <p class="title-midle">Toutes les News</p>
            </div>

            <div class="content">
                @foreach ($newsList as $news)
                    <a href="news/{{ $news->id }}" title="Show news" class="block-link">
                        <div class="block">
                            <div class="img-container">
                                <img src="{{ $news->imgUrl }}" alt="{{ $news->title }}" class="block-image" />
                            </div>
                            <div class="block-text">
                                <p class="title-mini">{{ $news->title }}</p>
                                <p class="text-less description">{{ Str::limit(strip_tags($news->content), 50) }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
