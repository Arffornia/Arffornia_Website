@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/reglement.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="content">
            <div class="text legal-page">
                {{-- The text lives in lang/{en,fr}/legal.php; $page is notice, privacy or terms --}}
                <h1 class="title">{{ __("legal.$page.title") }}</h1>
                <p class="paragraph">{{ __('legal.updated', ['date' => $updated]) }}</p>

                @foreach (__("legal.$page.sections") as $section)
                    <h2 class="sectionTitle">{{ $section['title'] }}</h2>
                    @foreach ($section['paragraphs'] ?? [] as $paragraph)
                        <p class="paragraph">{{ $paragraph }}</p>
                    @endforeach
                    @if (!empty($section['items']))
                        <ul>
                            @foreach ($section['items'] as $item)
                                <li><p class="paragraph">{{ $item }}</p></li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach

                @if ($page === 'terms')
                    <p class="paragraph"><a href="{{ url('/reglement') }}">{{ __('legal.terms.rules_link') }}</a></p>
                @endif
            </div>
        </div>
    </div>
@endsection
