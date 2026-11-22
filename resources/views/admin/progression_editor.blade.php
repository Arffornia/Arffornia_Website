@extends('admin.layout')

@section('extraHead')
    @vite(['resources/js/progression-editor.ts'])
    <link rel="stylesheet" href="{{ asset('css/pages/progression-editor.css') }}">
@endsection

@section('content')
    <main id="progression-editor" class="pe" aria-label="{{ __('progression_editor.title') }}">
        <p class="pe-hint">{{ __('progression_editor.loading') }}</p>
    </main>
    <script>
        window.ArfforniaEditor = @json($config);
    </script>
@endsection
