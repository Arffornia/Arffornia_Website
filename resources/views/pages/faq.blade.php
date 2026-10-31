@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/reglement.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="content">
            <div class="text legal-page">
                {{-- The text lives in lang/{en,fr}/faq.php --}}
                <h1 class="title">{{ __('faq.title') }}</h1>

                @foreach (__('faq.entries') as $entry)
                    <section id="{{ $entry['id'] }}">
                        <h2 class="sectionTitle"><a href="#{{ $entry['id'] }}">{{ $entry['question'] }}</a></h2>
                        @foreach ($entry['paragraphs'] ?? [] as $paragraph)
                            <p class="paragraph">{{ $paragraph }}</p>
                        @endforeach
                        @if (!empty($entry['steps']))
                            <ol>
                                @foreach ($entry['steps'] as $step)
                                    <li><p class="paragraph">{{ $step }}</p></li>
                                @endforeach
                            </ol>
                        @endif
                        @if (!empty($entry['notes']))
                            <ul>
                                @foreach ($entry['notes'] as $note)
                                    <li><p class="paragraph">{{ $note }}</p></li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        </div>
    </div>
@endsection
