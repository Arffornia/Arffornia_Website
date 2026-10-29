@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/reglement.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="content">
            <div class="text">
                <p class="title">{{ __('rules.title') }}</p>

                {{-- The rules text lives in lang/{en,fr}/rules.php --}}
                @foreach (__('rules.sections') as $section)
                    <p class="sectionTitle">{{ $section['title'] }}</p>
                    <ul>
                        @foreach ($section['items'] as $rule)
                            <li>
                                <p class="paragraph">{{ is_array($rule) ? $rule['text'] : $rule }}</p>
                            </li>
                            @if (is_array($rule) && !empty($rule['sub']))
                                <ul>
                                    @foreach ($rule['sub'] as $subRule)
                                        <li>
                                            <p class="paragraph">{{ $subRule }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
        </div>

    </div>
@endsection
@section('script')
@endsection
